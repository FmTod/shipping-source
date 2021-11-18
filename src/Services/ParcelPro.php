<?php

namespace FmTod\Shipping\Services;

use Exception;
use FmTod\Money\Money;
use FmTod\Shipping\Contracts\Shippable;
use FmTod\Shipping\Models\Address;
use FmTod\Shipping\Models\Carrier;
use FmTod\Shipping\Models\Duration;
use FmTod\Shipping\Models\LabelResponse;
use FmTod\Shipping\Models\Provider;
use FmTod\Shipping\Models\Rate;
use FmTod\Shipping\Models\Service;
use FmTod\Shipping\Services\ParcelPro\Enums\ContactType;
use FmTod\Shipping\Services\ParcelPro\PPIContact;
use FmTod\Shipping\Services\ParcelPro\PPIEstimatorRequest;
use FmTod\Shipping\Services\ParcelPro\PPIQuote;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use JetBrains\PhpStorm\ArrayShape;
use UnexpectedValueException;

class ParcelPro extends ShippingProvider
{
    protected string $apiUrl = 'https://api.parcelpro.com/v2.0/';

    /**
     * Return a new instance of ParcelPro service.
     *
     * @param array $config
     * @param \FmTod\Shipping\Contracts\Shippable|null $shipment
     */
    public function __construct(array $config, Shippable $shipment = null)
    {
        parent::__construct($config, $shipment);

        $this->carriers = collect([
            new Carrier(['name' => 'UPS', 'value' => 'UPS']),
            new Carrier(['name' => 'FedEx', 'value' => 'UPS']),
        ]);
    }

    /**
     * Refresh access token if it's expired.
     *
     * @return array
     * @throws \Exception
     */
    #[ArrayShape(['access_token' => 'array|mixed', 'expires_at' => Carbon::class])]
    private function refreshToken(): array
    {
        $response = Http::post('https://api.parcelpro.com/v2.0/auth', [
            'username' => $this->config['client_key'],
            'password' => $this->config['client_secret'],
            'grant_type' => 'password',
        ]);

        if ($response->failed()) {
            throw new \Exception($response->json('Message'), $response->json('Code'));
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
            throw new \Exception("The method [$method] is not allowed.");
        }

        $token = $this->getToken();

        return Http::withOptions(['base_uri' => $this->apiUrl])
            ->withToken($token['access_token'])
            ->$method($endpoint, $data)
            ->onError(function (Response $response) {
                throw new \Exception('API Error: '.$response->json('Message'), $response->json('Code'));
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
     * Get all the available services for the user.
     *
     * @return Service[]
     * @throws Exception
     */
    public function getServices(): Collection
    {
        if (count($this->services) === 0) {
            $this->services = $this->fetchServices();
        }

        return parent::getServices();
    }

    /**
     * Get service details for the specified service code.
     *
     * @param $serviceCode
     * @return Service|bool
     *
     * @throws \Exception
     */
    private function getServiceDetails($serviceCode): Service|bool
    {
        foreach ($this->getServices() as $service) {
            if ($service->value === (string) $serviceCode) {
                return $service;
            }
        }

        return false;
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
     * @param $carrierCode
     * @return \FmTod\Shipping\Models\Rate
     * @throws \Exception
     */
    private function parseRate(array $rate, $carrierCode): Rate
    {
        $serviceDetails = $this->getServiceDetails($rate['ServiceCode']);

        $carrierName = match (strtolower($rate['CarrierCode'])) {
            'fedex' => 'FedEx',
            'ups' => 'UPS',
            default => $rate['CarrierCode']
        };

        return new Rate([
            'id' => $rate['QuoteID'],
            'provider' => new Provider([
                'name' => 'ParcelPro',
                'class' => self::class,
            ]),
            'carrier' => new Carrier([
                'name' => $carrierName,
                'value' => $carrierCode,
            ]),
            'service' => new Service([
                'name' => "$carrierName {$serviceDetails['ServiceCodeDesc']}",
                'value' => $rate['ServiceCode'],
            ]),
            'duration' => new Duration([
                'days' => $this->getTransitTime($rate),
                'delivery_by' => $rate['DeliveryByTime'] ?? null,
            ]),
            'amount' => Money::parse($rate['TotalCharges'], 'USD'),
            'attributes' => [],
            'messages' => $rate['EstimatorDetail'],
        ]);
    }

    /**
     * Build PPIContact from Shipping address object.
     *
     * @param Address $address
     * @return PPIContact
     */
    private function buildContact(Address $address): PPIContact
    {
        if (empty($address->full_name)) {
            $address->full_name = $address->company;
        }

        if (empty($address->last_name)) {
            $address->last_name = $address->first_name;
        }

        return new PPIContact([
            'FirstName' => $address->first_name,
            'LastName' => $address->last_name,
            'CompanyName' => $address->company,
            'StreetAddress' => $address->address1,
            'ApartmentSuite' => $address->address2,
            'City' => $address->city,
            'State' => $address->state,
            'Zip' => (str_contains($address->postal_code, '-') ? substr($address->postal_code, 0, strpos($address->postal_code, '-')) : $address->postal_code),
            'Country' => $address->country_code,
            'Email' => $address->email,
            'TelephoneNo' => preg_replace('/\D/', '', $address->phone),
            'ContactType' => ContactType::AddressBook,
            'IsResidential' => (! empty($address->is_residential) ? $address->is_residential : false),
        ]);
    }

    //<editor-fold desc="Rates">

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
        $shipment = $this->getShipment();

        throw_if(! $shipment, 'No shipment was provided.');

        $shipFrom = $shipment->getShipFromAddress();
        $shipTo = $shipment->getShipToAddress();
        $packages = $shipment->getPackages();

        if ($carrier instanceof Carrier) {
            $carrier = $carrier->value;
        }

        if ($service instanceof Service) {
            $service = $service->value;
        }

        $quote = new PPIQuote([
            'CarrierCode' => $carrier,
            'ServiceCode' => $service,
            'ShipFrom' => $this->buildContact($shipFrom),
            'ShipTo' => $this->buildContact($shipTo),
            'Length' => ceil($packages[0]->get('length')),
            'Width' => ceil($packages[0]->get('width')),
            'Height' => ceil($packages[0]->get('height')),
            'Weight' => ceil($packages[0]->get('weight')),
        ]);

        if (isset($parameters['insurance'])) {
            $quote->InsuredValue = $parameters['insurance'];
        }

        if (isset($parameters['adult_signature']) && $parameters['adult_signature'] === true) {
            $quote->IsDeliveryConfirmation = true;
        }

        if (isset($parameters['saturday_delivery']) && $parameters['saturday_delivery'] === true) {
            $quote->IsSaturdayDelivery = true;
        }

        $response = $this->request('quotes', $quote->toArray(), 'POST')->json();

        if (empty($response['Estimator']) || count($response['Estimator']) === 0) {
            throw new Exception('There was an error retrieving the rates.');
        }

        $rate = $response['Estimator'][0];

        return $this->parseRate($rate, $response['CarrierCode']);
    }

    /**
     * Compiles the required information for obtaining a shipping rate quote into the array and using sendRequest()
     *      sends the request to the UPS API and returns a RateResponse object.
     *
     * @param array $options
     * @return Rate[]
     * @throws Exception
     * @version 07/07/2015
     * @since 12/02/2012
     */
    public function getRates(array $options = []): array
    {
        $shipment = $this->getShipment();

        throw_if(! $shipment, 'No shipment was provided.');

        $shipFrom = $shipment->getShipFromAddress();
        $shipTo = $shipment->getShipToAddress();
        $packages = $shipment->getPackages();

        $request = new PPIEstimatorRequest();
        $request->ShipFrom = (array) $shipFrom;
        $request->ShipTo = (array) $shipTo;
        $request->Length = ceil($packages[0]->get('length'));
        $request->Width = ceil($packages[0]->get('width'));
        $request->Height = ceil($packages[0]->get('height'));
        $request->Weight = ceil($packages[0]->get('weight'));

        if (isset($options['insurance'])) {
            $request->InsuredValue = $options['insurance'];
        }

        if (isset($options['adult_signature']) && $options['adult_signature'] === true) {
            $request->IsDeliveryConfirmation = true;
        }

        if (isset($options['saturday_delivery']) && $options['saturday_delivery'] === true) {
            $request->IsSaturdayDelivery = true;
        }

        $result = $this->request('estimator', $request, 'POST');

        // Get the shipment object
        $this->Response = json_decode($result);

        // check on the response status
        if ($this->getResponseStatus() !== 'Success') {
            throw new Exception('There was an error retrieving the rates.');
        }
        // fill the RateResponse object with package details for each shipment method
        return $this->getResponseRates((array) $request);
    }

    /**
     * Extracts and returns rates for the services from the SOAP response object.
     *
     * @param $params
     * @return Rate[] an array of Rate objects containing the rate data for each service
     * @throws Exception
     * @version updated 12/09/2012
     * @since 12/08/2012
     */
    protected function getResponseRates($params): array
    {
        // extract the rates from the SOAP response object
        $rates = $this->Response->Estimator;
        // make sure that $rates is not empty
        if (empty($rates)) {
            throw new UnexpectedValueException('Failed to retrieve shipping rates from API.');
        }
        // initialize the output array
        $output = [];

        // if there are more than one rates, $rates will be an array of objects
        foreach ($rates as $rate) {
            $output[] = $this->getResponseRatesWorker($rate, $params);
        }

        // return the completed array
        return $output;
    }

    /**
     * Extracts the data for a single rate service (used by getResponseRates).
     *
     * @param object $rate is an object containing data for a single rate service
     * @param array $params
     * @return Rate
     * @throws Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @since 12/08/2012
     * @version updated 12/09/2012
     */
    protected function getResponseRatesWorker($rate, $params = []): Rate
    {
        // If it is a multiple rate request, assign a quoteId to each rate instead of returning the same QuoteId for every rate
        $params = array_merge($params, [
            'CarrierCode' => FmTod\Shipping\APIs\ParcelPro\Enums\Carriers::getValue($rate->CarrierCode),
            'ServiceCode' => $rate->ServiceCode,
            'PackageCode' => '02',
            'ShipDate' => date('Y-m-d'),
        ]);

        // Due to Parcel Pro not assigning a unique Id to every quote(Estimator) we need to query each quote individually and use that quote id instead
        $result = $this->sendRequest('quote', $params, false, 'POST');
        $quotes = json_decode($result, true);
        $quote = $quotes['Estimator'][0];

        // (re)initialize the array holder for the loop
        $array = [];
        // build an array for this rate's information
        $service_text = ucwords(strtolower($rate['ServiceCodeDescription']));
        $duration_estimated = ($quote['ServiceCode'] === '03-DOM' ? 2 : $rate['BusinessDaysInTransit']);

        if ($rate['CarrierCode'] === 'Fedex') {
            $carrier_code = 'FedEx';
        } elseif ($rate['CarrierCode'] === 'Ups') {
            $carrier_code = 'UPS';
        } else {
            $carrier_code = $rate['CarrierCode'];
        }

        return new Rate([
            'id' => $quote['QuoteID'],
            'provider' => [
                'name' => 'ParcelPro',
                'class' => 'ParcelPro',
                'access_token' => $this->config['session_id'],
            ],
            'carrier' => [
                'name' => $carrier_code,
                'value' => FmTod\Shipping\APIs\ParcelPro\Enums\Carriers::getValue($rate['CarrierCode']),
            ],
            'service' => [
                'name' => ($carrier_code).' '.str_replace('Fedex ', '', $service_text),
                'value' => $rate['ServiceCode'],
            ],
            'duration_estimated' => $duration_estimated,
            'duration_terms' => (! empty($rate['DeliveryByTime']) ? "Delivered by {$rate['DeliveryByTime']}" : $duration_estimated.' Transit Day'.($duration_estimated > 1 ? 's' : '')),
            'attributes' => [],
            'messages' => $rate['EstimatorDetail'],
            'amount' => [
                'currency_code' => 'USD',
                'value' => $rate['TotalCharges'],
            ],
        ]);
    }

    //</editor-fold>

    //<editor-fold desc="Label">

    /**
     * Compiles the required information for obtaining a shipping rate quote into the UPS array and using sendRequest()
     *      sends the request to the UPS API and returns a RateResponse object.
     *
     * @param Rate $rate
     * @return LabelResponse
     * @throws Exception*@throws \GuzzleHttp\Exception\GuzzleException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @version 07/07/2015
     * @since 12/09/2012
     */
    public function createLabel(Rate $rate): LabelResponse
    {
        $this->config['session_id'] = $rate->provider->access_token;

        $transaction = $this->sendRequest('shipment/'.$rate->id, [], false, 'POST');

        $this->Response = json_decode($transaction, true);

        // build parameter for RatesResponse object
        $status = FmTod\Shipping\APIs\ParcelPro\Enums\ShipmentStatus::getKey($this->Response->Status);
        // if there was an error, throw an exception
        if ($status === 'Exception') {
            throw new Exception('There was an error creating the label.');
        }

        //return $this->Response->__toArray(true);
        // as long as the request was successful, create the RateResponse object and fill it
        $Response = new LabelResponse();
        $Response->provider = $rate->provider->name;
        $Response->carrier = $rate->carrier->name;
        $Response->estimated_days = $rate->duration_estimated;
        $Response->service = $rate->service->name;
        $Response->shipment_cost = $rate->amount->value;
        $Response->master_tracking_number = $this->Response->TrackingNumber;
        //$Response->shipment_id = $this->Response->ShipmentID;
        $Response->labels = $this->getResponseLabels();
        // return LabelResponse object
        return $Response;
    }

    /**
     * Extracts the label(s) information from the SOAP response object.
     *
     * @return array with the label(s) data
     * @throws UnexpectedValueException
     * @version updated 01/08/2013
     * @since 01/08/2013
     */
    protected function getResponseLabels(): array
    {
        // extract the rates from the SOAP response object
        $labels = $this->Response;
        // make sure that $rates is not empty
        if (empty($labels)) {
            throw new UnexpectedValueException('Failed to retrieve shipping labels from API.');
        }
        // initialize the output array
        $output = [];
        // if there are more than one rates, $rates will be an array of objects
        if (is_array($labels)) {
            // loop through rates
            foreach ($labels as $label) {
                // append each label array to the output
                $output[] = $this->getResponseLabelsWorker($label);
            }
        } else {
            // there is only one label
            $output[] = $this->getResponseLabelsWorker($labels);
        }
        // return the labels array
        return $output;
    }

    /**
     * Extracts the data for an individual label from the SOAP response object (used by getResponseLabels).
     *
     * @param $label
     * @return array with the label's data
     * @since 01/08/2013
     * @version updated 01/17/2013
     */
    protected function getResponseLabelsWorker($label): array
    {
        // (re)initialize the array holder for the loop
        $array = [];
        // build an array for this rate's information
        $array['tracking_number'] = $label['TrackingNumber'];
        $array['label_url'] = "http://parcelpro.com/printthermal.aspx?shipmentId={$label['ShipmentId']}&sessionId=".$this->config['session_id'];
        $array['label_file_type'] = 'pdf';
        // return the array
        return $array;
    }

    //</editor-fold>
}
