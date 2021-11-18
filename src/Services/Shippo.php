<?php

namespace FmTod\Shipping\Services;

use Exception;
use FmTod\Shipping\Contracts\Shippable;
use FmTod\Shipping\Contracts\ShippingService;
use FmTod\Shipping\Models\LabelResponse;
use FmTod\Shipping\Models\Rate;
use Shippo_CarrierAccount;
use Shippo_Transaction;
use UnexpectedValueException;

class Shippo extends Service implements ShippingService
{
    /**
     * @var array List of available services
     */
    protected $services = [
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
    ];

    /**
     * Constructor function - sets object properties.
     *
     * @param array $config the configuration data
     * @param Shippable|null $shipment
     * @return void
     *@since 12/02/2012
     * @version 07/07/2015
     */
    public function __construct(array $config, Shippable $shipment = null)
    {
        parent::__construct($config, $shipment);
        \Shippo::setApiKey($config['access_token']);
    }

    /**
     * Extracts any UPS service messages from the SOAP response object.
     *
     * @param mixed $messages the alert section of the SOAP response object
     * @return array of any messages
     *
     * @version updated 12/09/2012
     * @since 12/08/2012
     */
    protected function getResponseMessages(mixed $messages): array
    {
        // initialize the output array
        $output = [];
        // make sure that $messages is not an empty array or object
        if (! empty($messages)) {
            // if there are more than one messages, $messages will be an array of objects
            if (is_array($messages)) {
                // loop through response messages
                foreach ($messages as $message) {
                    $output[] = $message->Code.': '.$message->Description;
                }
            }
            // if there is only one message, $messages will be an object
            elseif (is_object($messages)) {
                $output[] = $messages->Code.': '.$messages->Description;
            }
        }
        // return the completed array
        return $output;
    }

    /**
     * Retrieves the available accounts from the API.
     *
     * @return array
     */
    protected function retrieveCarrier(): array
    {
        $response = Shippo_CarrierAccount::all();
        foreach ($response['results'] as $carrier) {
            $carriers[] = $carrier->__toArray(true);
        }

        return $carriers ?? [];
    }

    /**
     * Retrieve a list of carriers available to the account.
     *
     * @return \FmTod\Shipping\Models\Carrier[]
     */
    public function getCarriers(): array
    {
        if (empty($this->carriers) || count($this->carriers) === 0) {
            $this->carriers = $this->retrieveCarrier();
        }

        return parent::getCarriers();
    }

    /**
     * Compiles the required information for obtaining a shipping rate quote into the UPS array and using sendRequest()
     *      sends the request to the UPS API and returns a RateResponse object.
     *
     * @param $carrier
     * @param $service
     * @param array $parameters
     * @return Rate Rate object
     *
     * @throws Exception
     * @since 12/02/2012
     * @version 07/07/2015
     */
    public function getRate($carrier, $service, array $parameters = []): Rate
    {
        $shipment_from = $this->shipment->getShipFromAddress();
        $shipment_to = $this->shipment->getShipToAddress();

        $data = [
            'address_from'=> [
                'name' => $shipment_from->get('name'),
                'company' => $shipment_from->get('company'),
                'street1' => $shipment_from->get('address1'),
                'street2' => $shipment_from->get('address2'),
                'city' => $shipment_from->get('city'),
                'state' => $shipment_from->get('state'),
                'zip' => $shipment_from->get('postal_code'),
                'country' => $shipment_from->get('country_code'),
                'phone' => $shipment_from->get('phone'),
                'email' => $shipment_from->get('email'),
                'is_residential' => $shipment_from->get('is_residential'),
            ],
            'address_to'=> [
                'name' => $shipment_to->get('name'),
                'company' => $shipment_to->get('company'),
                'street1' => $shipment_to->get('address1'),
                'street2' => $shipment_to->get('address2'),
                'city' => $shipment_to->get('city'),
                'state' => $shipment_to->get('state'),
                'zip' => $shipment_to->get('postal_code'),
                'country' => $shipment_to->get('country_code'),
                'phone' => $shipment_to->get('phone'),
                'email' => $shipment_to->get('email'),
                'is_residential' => $shipment_to->get('is_residential'),
            ],
            'async'=> false,
        ];

        foreach ($this->shipment->getPackages() as $package) {
            $data['parcels'][] = [
                'length'=> ($package->get('length')),
                'width'=> ($package->get('width')),
                'height'=> ($package->get('height')),
                'distance_unit'=> strtolower($this->config['dimension_unit']),
                'weight'=> ($package->get('weight')),
                'mass_unit'=> strtolower($this->config['weight_unit']),
            ];
        }

        if (isset($parameters['insurance'])) {
            $data['extra']['insurance'] = [
                'currency' => config('money.defaultCurrency'),
                'amount' => $parameters['insurance'],
            ];
        }

        if (isset($parameters['adult_signature']) && $parameters['adult_signature'] === true) {
            $data['extra']['signature_confirmation'] = 'ADULT';
        }

        if (isset($parameters['saturday_delivery']) && $parameters['saturday_delivery'] === true) {
            $data['extra']['saturday_delivery'] = true;
        }

        // Get rates for all the available services since Shippo doesn't have a way to retrieve just one rate without creating the label
        $shipment = \Shippo_Shipment::create($data);

        if ($shipment->status !== 'SUCCESS') {
            throw new Exception('There was an error retrieving the rates');
        }
        // Go through all the rates and find the correct one if provided
        foreach ($shipment->rates as $rate) {
            if ($rate->carrier_account === $carrier && $rate->servicelevel->token === $service) {
                $selected_rate = $rate;
            }
        }

        // If rate was not found throw an exception
        if (empty($selected_rate)) {
            throw new Exception('Service is not available for the shipment you provided');
        }
        //return $selected_rate;
        return new Rate([
            'id' => $selected_rate->object_id,
            'provider' => [
                'name' => 'Shippo',
                'class' => 'Shippo',
                'access_token' => $this->config['access_token'],
            ],
            'carrier' => [
                'name' => $selected_rate->provider,
                'value' => $selected_rate->carrier_account,
            ],
            'service' => [
                'name' => $selected_rate->servicelevel->name,
                'value' => $selected_rate->servicelevel->token,
            ],
            'duration_estimated' => $selected_rate->estimated_days,
            'duration_terms' => $selected_rate->servicelevel->terms,
            'attributes' => $selected_rate->attributes,
            'messages' => $this->getResponseMessages($selected_rate->messages),
            'amount' => [
                'currency_code' => $selected_rate->currency,
                'value' => $selected_rate->amount,
            ],
        ]);
    }

    /**
     * Compiles the required information for obtaining a shipping rate quote into the UPS array and using sendRequest()
     *      sends the request to the UPS API and returns a RateResponse object.
     *
     * @param array $options
     * @return Rate[] Rate list
     *
     * @throws Exception
     * @since 12/02/2012
     * @version 07/07/2015
     */
    public function getRates(array $options = []): array
    {
        $shipment_from = $this->shipment->getShipFromAddress();
        $shipment_to = $this->shipment->getShipToAddress();

        $data = [
            'address_from'=> [
                'name' => $shipment_from->get('name'),
                'company' => $shipment_from->get('company'),
                'street1' => $shipment_from->get('address1'),
                'street2' => $shipment_from->get('address2'),
                'city' => $shipment_from->get('city'),
                'state' => $shipment_from->get('state'),
                'zip' => $shipment_from->get('postal_code'),
                'country' => $shipment_from->get('country_code'),
                'phone' => $shipment_from->get('phone'),
                'email' => $shipment_from->get('email'),
                'is_residential' => $shipment_from->get('is_residential'),
            ],
            'address_to'=> [
                'name' => $shipment_to->get('name'),
                'company' => $shipment_to->get('company'),
                'street1' => $shipment_to->get('address1'),
                'street2' => $shipment_to->get('address2'),
                'city' => $shipment_to->get('city'),
                'state' => $shipment_to->get('state'),
                'zip' => $shipment_to->get('postal_code'),
                'country' => $shipment_to->get('country_code'),
                'phone' => $shipment_to->get('phone'),
                'email' => $shipment_to->get('email'),
                'is_residential' => $shipment_to->get('is_residential'),
            ],
            'async'=> false,
        ];

        foreach ($this->shipment->getPackages() as $package) {
            $data['parcels'][] = [
                'length'=> ($package->get('length')),
                'width'=> ($package->get('width')),
                'height'=> ($package->get('height')),
                'distance_unit'=> strtolower($this->config['dimension_unit']),
                'weight'=> ($package->get('weight')),
                'mass_unit'=> strtolower($this->config['weight_unit']),
            ];
        }

        if (isset($options['insurance'])) {
            $data['extra']['insurance'] = [
                'currency' => $this->config['currency_code'] ?? 'USD',
                'amount' => $options['insurance'],
            ];
        }

        if (isset($options['adult_signature']) && $options['adult_signature'] === true) {
            $data['extra']['signature_confirmation'] = 'ADULT';
        }

        if (isset($options['saturday_delivery']) && $options['saturday_delivery'] === true) {
            $data['extra']['saturday_delivery'] = true;
        }

        $shipment = \Shippo_Shipment::create($data);

        if ($shipment->status !== 'SUCCESS') {
            throw new Exception('There was an error retrieving the rates');
        }
        // Go through all the rates and find the correct one if provided
        foreach ($shipment->rates as $rate) {
            $rates[] = new Rate([
                'id' => $rate->object_id,
                'provider' => [
                    'name' => 'Shippo',
                    'class' => 'Shippo',
                    'access_token' => $this->config['access_token'],
                ],
                'carrier' => [
                    'name' => $rate->provider,
                    'value' => $rate->carrier_account,
                ],
                'service' => [
                    'name' => $rate->servicelevel->name,
                    'value' => $rate->servicelevel->token,
                ],
                'duration_estimated' => $rate->estimated_days,
                'duration_terms' => $rate->servicelevel->terms,
                'attributes' => $rate->attributes,
                'messages' => $this->getResponseMessages($rate->messages),
                'amount' => [
                    'currency_code' => $rate->currency,
                    'value' => $rate->amount,
                ],
            ]);
        }

        // If rate was not found throw an exception
        if (! isset($rates) || count($rates) === 0) {
            throw new Exception('Service is not available for the shipment you provided');
        }
        //return $selected_rate;
        return $rates;
    }

    /**
     * Compiles the required information for obtaining a shipping rate quote into the UPS array and using sendRequest()
     *      sends the request to the UPS API and returns a RateResponse object.
     *
     * @param Rate $rate parameters for label creation
     * @return \FmTod\Shipping\Models\LabelResponse
     *
     * @throws \Exception
     * @since 12/09/2012
     * @version 07/07/2015
     */
    public function createLabel(Rate $rate): LabelResponse
    {
        $transaction = Shippo_Transaction::create([
            'rate' => $rate->id,
            'label_file_type' => 'PDF',
            'async' => false,
        ]);

        // build parameter for RatesResponse object
        $status = ucfirst(strtolower($transaction['status']));
        // if there was an error, throw an exception
        if ($status !== 'Success') {
            throw new Exception('There was an error creating the label.');
        }

        // as long as the request was successful, create the RateResponse object and fill it
        $response = new LabelResponse();
        $response->provider = $rate->provider->name;
        $response->carrier = $rate->carrier->name;
        $response->service = $rate->service->name;
        $response->estimated_days = $rate->duration_estimated;
        $response->shipment_cost = $rate->amount->value;
        $response->master_tracking_number = $transaction['tracking_number'];
        $response->labels = $this->getLabels($rate->id);

        // return LabelResponse object
        return $response;
    }

    /**
     * Extracts the label(s) information from the SOAP response object.
     *
     * @param $rateId
     * @return array with the label(s) data
     *
     * @version updated 01/08/2013
     * @since 01/08/2013
     */
    protected function getLabels($rateId): array
    {
        $transactions = Shippo_Transaction::all(['rate' => $rateId]);

        // extract the rates from the SOAP response object
        $labels = $transactions['results'];
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
     *
     * @version updated 01/17/2013
     * @since 01/08/2013
     */
    protected function getResponseLabelsWorker($label): array
    {
        // (re)initialize the array holder for the loop
        $array = [];
        // build an array for this rate's information
        $array['tracking_number'] = $label->tracking_number;
        $array['label_url'] = $label->label_url;
        $array['label_file_type'] = 'pdf';
        // return the array
        return $array;
    }
}
