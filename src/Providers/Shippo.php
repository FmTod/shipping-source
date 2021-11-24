<?php

namespace FmTod\Shipping\Providers;

use FmTod\Money\Money;
use FmTod\Shipping\Enums\LabelType;
use FmTod\Shipping\Models\Carrier;
use FmTod\Shipping\Models\Duration;
use FmTod\Shipping\Models\Label;
use FmTod\Shipping\Models\Provider;
use FmTod\Shipping\Models\Rate;
use FmTod\Shipping\Models\Service;
use FmTod\Shipping\Models\Shipment;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Shippo_CarrierAccount;
use Shippo_Object;
use Shippo_Shipment;
use Shippo_Transaction;

class Shippo extends BaseProvider
{
    public const NAME = 'Shippo';

    /**
     * Constructor function - sets object properties.
     *
     * @param array $config the configuration data
     * @param array|null $shipment
     */
    public function __construct(array $config, ?array $shipment = null)
    {
        parent::__construct(array_replace([
            'dimension_unit' => 'in',
            'weight_unit' => 'lb',
        ], $config), $shipment);
        \Shippo::setApiKey($config['access_token']);
    }

    /**
     * Retrieves the available accounts from the API.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function fetchCarriers(): Collection
    {
        $response = Shippo_CarrierAccount::all();

        /* @phpstan-ignore-next-line */
        if (empty($response['results'])) {
            return collect();
        }

        /* @phpstan-ignore-next-line */
        return collect($response['results'])->map(function (Shippo_Object $account) {
            return new Carrier([
                'name' => match (true) {
                    stripos($account['carrier'], "fedex") !== false => 'FedEx',
                    stripos($account['carrier'], "ups") !== false => 'UPS',
                    stripos($account['carrier'], "usps") !== false => 'USPS',
                    default => $account['carrier'],
                },
                'value' => $account['object_id'],
                'data' => $account->__toArray(true),
            ]);
        });
    }

    /**
     * Retrieves the available services from the API.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function fetchServices(): Collection
    {
        $carrierNames = $this->getCarriers()
            ->pluck('name')
            ->map(fn (string $carrier) => strtolower($carrier))
            ->toArray();

        return collect([
            'usps_priority',
            'usps_priority_express',
            'usps_first',
            'usps_parcel_select',
            'usps_media_mail',
            'usps_priority_mail_international',
            'usps_priority_mail_express_international',
            'usps_first_class_package_international_service',
            'fedex_ground',
            'fedex_home_delivery',
            'fedex_smart_post',
            'fedex_2_day',
            'fedex_2_day_am',
            'fedex_express_saver',
            'fedex_standard_overnight',
            'fedex_priority_overnight',
            'fedex_first_overnight',
            'fedex_freight_priority',
            'fedex_next_day_freight',
            'fedex_freight_economy',
            'fedex_first_freight',
            'fedex_international_economy',
            'fedex_international_priority',
            'fedex_international_first',
            'fedex_europe_first_international_priority',
            'fedex_international_priority_express',
            'international_economy_freight',
            'international_priority_freight',
            'ups_standard',
            'ups_ground',
            'ups_saver',
            'ups_3_day_select',
            'ups_second_day_air',
            'ups_second_day_air_am',
            'ups_next_day_air',
            'ups_next_day_air_saver',
            'ups_next_day_air_early_am',
            'ups_mail_innovations_domestic',
            'ups_surepost',
            'ups_surepost_lightweight',
            'ups_express',
            'ups_express_1200',
            'ups_express_plus',
            'ups_expedited',
        ])
            ->filter(fn (string $service) => Str::startsWith($service, $carrierNames))
            ->map(function (string $service) {
                return new Service([
                    'name' => Str::title(Str::after($service, '_')),
                    'value' => $service,
                    'carrier' => match (Str::before($service, '_')) {
                        'ups' => 'UPS',
                        'fedex' => 'FedEx',
                        'usps' => 'USPS',
                        default => Str::title(Str::before($service, '_'))
                    },
                ]);
            });
    }

    /**
     * Retrieve a list of carriers available to the account.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getCarriers(): Collection
    {
        if ($this->carriers->count() === 0) {
            $this->carriers = $this->fetchCarriers();
        }

        return parent::getCarriers();
    }

    /**
     * Retrieve a list of carriers available to the account.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getServices(): Collection
    {
        if ($this->services->count() === 0) {
            $this->services = $this->fetchServices();
        }

        return parent::getServices();
    }

    /**
     * Compiles the required information for obtaining a shipping rate quote into the UPS array and using sendRequest()
     *      sends the request to the UPS API and returns a RateResponse object.
     *
     * @param \FmTod\Shipping\Models\Carrier|string $carrier
     * @param \FmTod\Shipping\Models\Service|string $service
     * @param array $parameters
     * @return Rate Rate object
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

        $rate = $this->getRates($parameters)
            ->filter(function (Rate $rate) use ($service, $carrier) {
                return $rate->carrier->value === $carrier && $rate->service->value === $service;
            })
            ->first();

        throw_if(! $rate, 'There was an error retrieving the rates.');

        return $rate;
    }

    /**
     * Compiles the required information for obtaining a shipping rate quote into the UPS array and using sendRequest()
     *      sends the request to the UPS API and returns a RateResponse object.
     *
     * @param array $parameters
     * @return \Illuminate\Support\Collection Rate list
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

        $data = [
            'address_from' => [
                'name' => $consignor->getFullName(),
                'company' => $consignor->getCompanyName(),
                'street1' => $consignor->getStreetAddress1(),
                'street2' => $consignor->getStreetAddress2(),
                'city' => $consignor->getCity(),
                'state' => $consignor->getState(),
                'zip' => $consignor->getPostalCode(),
                'country' => $consignor->getCountryCode(),
                'phone' => $consignor->getPhoneNumber(),
                'email' => $consignor->getEmail(),
                'is_residential' => $consignor->getIsResidential(),
            ],
            'address_to' => [
                'name' => $consignee->getFullName(),
                'company' => $consignee->getCompanyName(),
                'street1' => $consignee->getStreetAddress1(),
                'street2' => $consignee->getStreetAddress2(),
                'city' => $consignee->getCity(),
                'state' => $consignee->getState(),
                'zip' => $consignee->getPostalCode(),
                'country' => $consignee->getCountryCode(),
                'phone' => $consignee->getPhoneNumber(),
                'email' => $consignee->getEmail(),
                'is_residential' => $consignee->getIsResidential(),
            ],
            'parcels' => [
                [
                    'weight' => $package->getWeight($this->config['weight_unit']),
                    'length' => $package->getLength($this->config['dimension_unit']),
                    'width' => $package->getWidth($this->config['dimension_unit']),
                    'height' => $package->getHeight($this->config['dimension_unit']),
                    'distance_unit' => $this->config['dimension_unit'],
                    'mass_unit' => $this->config['weight_unit'],
                ],
            ],
            'async' => false,
        ];

        if (isset($parameters['insurance'])) {
            $data['extra']['insurance'] = [
                'currency' => config('money.defaultCurrency'),
                'amount' => $parameters['insurance'],
            ];
        }

        if (isset($parameters['adult_signature']) && $parameters['adult_signature'] === true) {
            $data['extra']['signature_confirmation'] = 'ADULT';
        }

        if (isset($parameters['saturday_delivery'])) {
            $data['extra']['saturday_delivery'] = $parameters['saturday_delivery'];
        }

        $response = Shippo_Shipment::create($data);

        throw_if($response['status'] !== 'SUCCESS', 'There was an error retrieving the rates');

        return collect($response['rates'])->map(fn ($rate) => new Rate([
            'id' => $rate->object_id,
            'provider' => new Provider([
                'name' => self::NAME,
                'class' => Shippo::class,
            ]),
            'carrier' => new Carrier([
                'name' => $rate['provider'],
                'value' => $rate['carrier_account'],
            ]),
            'service' => new Service([
                'name' => $rate['servicelevel']['name'],
                'value' => $rate['servicelevel']['token'],
            ]),
            'duration' => new Duration([
                'days' => $rate['estimated_days'],
                'terms' => $rate['servicelevel']['terms'],
            ]),
            'amount' => Money::parse($rate['amount'], $rate['currency']),
            'messages' => $rate['messages'],
            'attributes' => $rate['attributes'],
        ]));
    }

    /**
     * Compiles the required information for obtaining a shipping rate quote into the UPS array and using sendRequest()
     *      sends the request to the UPS API and returns a RateResponse object.
     *
     * @param Rate $rate parameters for label creation
     * @return \FmTod\Shipping\Models\Shipment
     *
     * @throws \Throwable
     */
    public function createShipment(Rate $rate): Shipment
    {
        $transaction = Shippo_Transaction::create([
            'rate' => $rate->id,
            'label_file_type' => 'PDF',
            'async' => false,
        ]);

        throw_if($transaction['status'] !== 'SUCCESS', 'There was an error retrieving the rates');

        return new Shipment([
            'provider' => $rate->provider,
            'carrier' => $rate->carrier,
            'service' => $rate->service,
            'duration' => $rate->duration,
            'amount' => $rate->amount,
            'tracking_number' => $transaction['tracking_number'],
            'labels' => collect([new Label([
                'name' => $transaction['tracking_number'],
                'content' => $transaction['label_url'],
                'type' => LabelType::Url,
            ])]),
            'data' => $transaction,
        ]);
    }
}
