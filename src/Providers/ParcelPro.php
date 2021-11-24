<?php

namespace FmTod\Shipping\Providers;

use Exception;
use FmTod\Money\Money;
use FmTod\Shipping\Contracts\ShippableAddress;
use FmTod\Shipping\Enums\LabelType;
use FmTod\Shipping\Models\Carrier;
use FmTod\Shipping\Models\Duration;
use FmTod\Shipping\Models\Label;
use FmTod\Shipping\Models\Provider;
use FmTod\Shipping\Models\Rate;
use FmTod\Shipping\Models\Service;
use FmTod\Shipping\Models\Shipment;
use FmTod\Shipping\Providers\ParcelPro\Enums\ContactType;
use FmTod\Shipping\Providers\ParcelPro\Enums\ShipmentStatus;
use FmTod\Shipping\Providers\ParcelPro\PPIContact;
use FmTod\Shipping\Providers\ParcelPro\PPIEstimatorRequest;
use FmTod\Shipping\Providers\ParcelPro\PPIQuote;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;
use RuntimeException;

class ParcelPro extends BaseProvider
{
    public const NAME = 'ParcelPro';

    protected string $apiUrl = 'https://api.parcelpro.com/v2.0/';

    /**
     * Return a new instance of ParcelPro service.
     *
     * @param array $config
     * @param array|null $parameters
     */
    public function __construct(array $config, ?array $parameters = null)
    {
        parent::__construct($config, $parameters);

        $this->carriers = collect([
            new Carrier(['name' => 'UPS', 'value' => 'UPS']),
            new Carrier(['name' => 'FedEx', 'value' => 'FedEx']),
        ]);
    }

    /**
     * Refresh access token if it's expired.
     *
     * @return array
     * @throws \Exception
     */
    #[ArrayShape(['access_token' => "array|mixed", 'expires_at' => Carbon::class])]
    private function refreshToken(): array
    {
        $response = Http::post('https://api.parcelpro.com/v2.0/auth', [
            'username' => $this->config['client_key'],
            'password' => $this->config['client_secret'],
            'grant_type' => 'password',
        ]);

        if ($response->failed()) {
            throw new Exception($response->json('Message'), $response->json('Code'));
        }

        $token = [
            'access_token' => $response->json('access_token'),
            'expires_at' => Date::now()
                ->addSeconds($response->json('expires_in'))
                ->subSeconds(30),
        ];
        Session::push('parcel_pro', $token);

        return $token;
    }

    /**
     * Get access token for sending request.
     *
     * @return array
     * @throws \Exception
     */
    private function getToken(): array
    {
        if (Session::has('parcel_pro.access_token') && Session::has('parcel_pro.expires_at')) {
            $expiration = Date::parse(Session::get('parcel_pro.expires_at'));

            if ($expiration->isFuture()) {
                return Session::get('parcel_pro');
            }
        }

        return $this->refreshToken();
    }

    /**
     * Send request to ParcelPro API.
     *
     * @param string $endpoint
     * @param array $data
     * @param string $method
     * @return \Illuminate\Http\Client\Response
     * @throws \Exception
     */
    private function request(string $endpoint, array $data = [], string $method = 'get'): Response
    {
        $allowedMethods = ['get', 'post', 'put', 'patch', 'delete'];
        $method = strtolower($method);
        if (! in_array($method, $allowedMethods)) {
            throw new Exception("The method [$method] is not allowed.");
        }

        $token = $this->getToken();

        return Http::withOptions(['base_uri' => $this->apiUrl])
            ->withToken($token['access_token'])
            ->$method($endpoint, $data)
            ->onError(function (Response $response) {
                throw new RuntimeException('API Error: '.$response->json('Message'), $response->json('Code'));
            });
    }

    /**
     * Retrieves the available services form the API.
     *
     * @param string|Carrier|null $carrier
     * @param bool|null $domestic
     * @return Collection
     *
     * @throws \Exception
     */
    private function fetchServices(Carrier|string $carrier = null, bool $domestic = null): Collection
    {
        if ($carrier === null) {
            return collect($this->getCarriers())
                ->map(fn (Carrier $singleCarrier) => $this->fetchServices($singleCarrier, $domestic))
                ->flatten(1);
        }

        if ($carrier instanceof Carrier) {
            $carrier = $carrier->value;
        }

        if ($domestic === null) {
            return collect([
                    $this->fetchServices($carrier, true),
                    $this->fetchServices($carrier, false),
                ])
                ->flatten(1);
        }

        return $this->request('carriers/services', [
            'isDomestic' => $domestic ? 'true' : 'false',
            'CarrierCode' => $carrier,
        ])
            ->collect()
            ->map(fn (array $service) => new Service([
                'carrier' => $service['CarrierCode'],
                'name' => $service['ServiceCodeDesc'],
                'value' => $service['ServiceCode'],
                'data' => $service,
            ]));
    }

    /**
     * Get transit time in number of days.
     *
     * @param array $rate
     * @return string
     */
    private function getTransitTime(array $rate): string
    {
        return $rate['ServiceCode'] !== '03-DOM'
            ? $rate['BusinessDaysInTransit']
            : 2;
    }

    /**
     * Transform estimator array response to Rate object.
     *
     * @param array $rate
     * @param array $parameters
     * @return \FmTod\Shipping\Models\Rate
     * @throws \Throwable
     */
    private function parseRate(array $rate, array $parameters = []): Rate
    {
        $carrierValue = match (strtolower($rate['CarrierCode'])) {
            'fedex' => 'FedEx',
            'ups' => 'UPS',
            default => $rate['CarrierCode']
        };

        $carrier = $this->getCarriers()->where('value', $carrierValue)->first();
        throw_if(! $carrier, 'Could not identify the carrier from the retrieved quote.');

        $service = $this->getServices()->where('value', $rate['ServiceCode'])->first();
        throw_if(! $service, 'Could not identify the service from the retrieved quote.');

        return new Rate([
            'id' => $rate['QuoteID'],
            'provider' => new Provider([
                'name' => self::NAME,
                'class' => __CLASS__,
            ]),
            'carrier' => $carrier,
            'service' => $service,
            'duration' => new Duration([
                'days' => $this->getTransitTime($rate),
                'delivery_by' => $rate['DeliveryByTime'] ?? null,
            ]),
            'amount' => Money::parse($rate['TotalCharges'], 'USD'),
            'messages' => $rate['EstimatorDetail'] ?? [],
            'parameters' => $parameters,
        ]);
    }

    /**
     * Build PPIContact from Shipping address object.
     *
     * @param \FmTod\Shipping\Contracts\ShippableAddress $address
     * @return PPIContact
     */
    private function buildContact(ShippableAddress $address): PPIContact
    {
        return new PPIContact([
            'ContactType' => ContactType::AddressBook,
            'FirstName' => $address->getFirstName(),
            'LastName' => $address->getLastName(),
            'CompanyName' => $address->getCompanyName() ?? '',
            'StreetAddress' => $address->getStreetAddress1(),
            'ApartmentSuite' => $address->getStreetAddress2() ?? '',
            'City' => $address->getCity(),
            'State' => $address->getState(),
            'Zip' => str_replace(' ', '', Str::before($address->getPostalCode(), '-')),
            'Country' => $address->getCountryCode(),
            'Email' => $address->getEmail(),
            'TelephoneNo' => preg_replace('/\D/', '', $address->getPhoneNumber()),
            'IsResidential' => $address->getIsResidential(),
        ]);
    }

    /**
     * Get all the available services for the user.
     *
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public function getServices(): Collection
    {
        if (count($this->services) === 0) {
            $this->services = $this->fetchServices();
        }

        return parent::getServices();
    }

    /**
     * Get a rate for the specified carrier and service.
     *
     * @param Carrier|string $carrier
     * @param Service|string $service
     * @param array $parameters
     * @return Rate
     *
     * @throws \Throwable
     */
    public function getRate(Carrier|string $carrier, Service|string $service, array $parameters = []): Rate
    {
        if ($carrier instanceof Carrier) {
            $carrier = $carrier->value;
        }

        if ($service instanceof Service) {
            $service = $service->value;
        }

        $consignor = $this->getConsignor();
        $consignee = $this->getConsignee();
        $package = $this->getPackage();

        throw_if(! $consignor, 'A consignor must be provided in order to request a rate.');
        throw_if(! $consignee, 'A consignee must be provided in order to request a rate.');
        throw_if(! $package, 'A package must be provided in order to request a rate.');

        $international = $consignee->getCountryCode() !== 'US';

        $quote = new PPIQuote([
            'CarrierCode' => match (strtolower($carrier)) {
                'ups' => 1,
                'fedex' => 2,
                default => $carrier
            },
            'ServiceCode' => $service,
            'ShipFrom' => $this->buildContact($consignor),
            'ShipTo' => $this->buildContact($consignee),
            'Length' => ceil($package->getLength()),
            'Width' => ceil($package->getWidth()),
            'Height' => ceil($package->getHeight()),
            'Weight' => ceil($package->getWeight()),
            'InsuredValue' => $parameters['insurance'] ?? 1,
            'IsDeliveryConfirmation' => $parameters['adult_signature'] ?? false,
            'IsSaturdayDelivery' => $parameters['saturday_delivery'] ?? false,
            'ReferenceNumber' => $parameters['reference'] ?? '',
            'CustomerReferenceNumber' => $parameters['reference'] ?? '',

            // International Shipment
            'IsInternational' => $international,
            'IsCommercialInvoice' => $international,
            'ShipmentPurpose' => $parameters['purpose'] ?? '',
            'PackageContent' => $parameters['content'] ?? '',
            'Commodities' => $parameters['commodities'] ?? [],
        ]);

        $rate = $this->request('quotes', $quote->toArray(), 'POST')
            ->collect('Estimator')
            ->first();

        throw_if(! $rate, 'There was an error retrieving the rates.');

        return $this->parseRate($rate, $parameters);
    }

    /**
     * Compiles the required information for obtaining a shipping rate quote into the array and using sendRequest()
     *      sends the request to the UPS API and returns a RateResponse object.
     *
     * @param array $parameters
     * @return Collection
     *
     * @throws \Throwable
     */
    public function getRates(array $parameters = []): Collection
    {
        $consignor = $this->getConsignor();
        $consignee = $this->getConsignee();
        $package = $this->getPackage();

        throw_if(! $consignor, 'A consignor must be provided in order to request a rate.');
        throw_if(! $consignee, 'A consignee must be provided in order to request a rate.');
        throw_if(! $package, 'A package must be provided in order to request a rate.');

        $request = new PPIEstimatorRequest([
            'ShipFrom' => $this->buildContact($consignor),
            'ShipTo' => $this->buildContact($consignee),
            'Length' => ceil($package->getLength()),
            'Width' => ceil($package->getWidth()),
            'Height' => ceil($package->getHeight()),
            'Weight' => ceil($package->getWeight()),
            'InsuredValue' => $parameters['insurance'] ?? 1,
            'IsDeliveryConfirmation' => $parameters['adult_signature'] ?? false,
            'IsSaturdayDelivery' => $parameters['saturday_delivery'] ?? false,
        ]);

        return $this->request('estimator', $request->toArray(), 'POST')
            ->collect('Estimator')
            ->map(fn ($rate) => $this->parseRate($rate));
    }

    /**
     * Compiles the required information for obtaining a shipping rate quote into the UPS array and using sendRequest()
     *      sends the request to the UPS API and returns a RateResponse object.
     *
     * @param Rate $rate
     * @return \FmTod\Shipping\Models\Shipment
     * @throws \Throwable
     */
    public function createShipment(Rate $rate): Shipment
    {
        $quote = $this->getRate($rate->carrier, $rate->service, $rate->parameters);

        $shipment = $this->request("shipments/$quote->id", [], 'POST')->json();

        if (ShipmentStatus::getKey($shipment['Status']) === 'Exception') {
            throw new Exception('There was an error creating the label.');
        }

        return new Shipment([
            'provider' => $rate->provider,
            'carrier' => $rate->carrier,
            'service' => $rate->service,
            'duration' => $rate->duration,
            'amount' => $rate->amount,
            'tracking_number' => $shipment['TrackingNumber'],
            'labels' => collect([new Label([
                'name' => $shipment['TrackingNumber'],
                'content' => $shipment['LabelImageFull'],
                'type' => LabelType::Base64,
            ])]),
            'data' => $shipment,
        ]);
    }
}
