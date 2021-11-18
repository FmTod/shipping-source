<?php

namespace FmTod\Shipping;

use FmTod\Shipping\Contracts\ShippingService;
use FmTod\Shipping\Models\Address;
use FmTod\Shipping\Models\Shipment;
use FmTod\Shipping\Packers\DefaultPacker;
use FmTod\Shipping\Services\ParcelPro;
use FmTod\Shipping\Services\Shippo;

class Shipping
{
    public static array $providers = [
        ['name' => 'ParcelPro', 'class' => ParcelPro::class],
        ['name' => 'Shippo', 'class' => Shippo::class],
    ];

    /**
     * Create a new instance of the specified provider.
     *
     * @param $provider_key
     * @param array $config
     * @return ShippingService
     */
    public static function provider($provider_key, $config = [])
    {
        return new self::$providers[$provider_key]['class']($config);
    }

    /**
     * Get a list of all carriers.
     *
     * @return Carrier[]|\Illuminate\Database\Eloquent\Collection
     */
    public function carrier_list()
    {
        return Carrier::all();
    }

    /**
     * Get a specific carrier by its id.
     *
     * @param $carrier_id
     * @return Carrier|Carrier[]|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     */
    public function carrier($carrier_id)
    {
        return Carrier::with('services')->find($carrier_id);
    }

    /**
     * Get the shipping rules for the user.
     *
     * @return ShippingRule[]
     */
    public function rule_list()
    {
        return ShippingRule::where('user_id', Auth::user()->id)->get();
    }

    /**
     * Get user rule with the specified id.
     *
     * @param $setting_id
     * @return ShippingRule|ShippingRule[]|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     */
    public function rule($setting_id)
    {
        $setting = ShippingRule::with([
            'carrier',
            'shipping_provider',
            'carrier_services',
        ])->where('user_id', Auth::user()->id)->find($setting_id);

        $setting->setHidden([
            'user_id',
            'shipping_provider_id',
            'carrier_id',
            'services',
            'insurance_provider_id',
        ]);

        return $setting;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function rates_list(Request $request)
    {
        $user = User::find(Auth::user()->id);

        $items = $request->json('packages');
        $ship_from = new Address($request->json('shipper'));
        $ship_to = new Address($request->json('consignee'));
        $options = $request->json('options');
        $total_value = $request->json('total_value');

        $shippers = [];
        $rates = [];
        foreach ($user->api_auths as $api_auth) {
            if ($api_auth->type === 'App\Models\ShippingProvider') {
                $max_package_weight = 150;
                $max_package_length = 108;
                $max_package_size = 165;

                // The default packing implementation provided packs all catalog separately, but the packing
                // algorithm can be changed simply by changing this line to use a different IPacker implementation.
                $packer = new DefaultPacker($max_package_weight, $max_package_length, $max_package_size, false, ['weight_unit' => 'LB', 'dimension_unit' => 'IN']);

                // Add UPS packer to array
                $shippers[$api_auth->provider->class] = [
                    'name'=>$api_auth->provider->name,
                    'packer'=>$packer,
                    'config' => [
                        'weight_unit' => 'LB',
                        'dimension_unit' => 'IN',
                        'currency_code' => 'USD',
                        'access_token' => $api_auth->access_token,
                    ],
                ];

                if (isset($api_auth->client_key)) {
                    $shippers[$api_auth->provider->class]['config']['client_key'] = $api_auth->client_key;
                }
                if (isset($api_auth->client_secret)) {
                    $shippers[$api_auth->provider->class]['config']['client_secret'] = $api_auth->client_secret;
                }
                if (isset($api_auth->refresh_token)) {
                    $shippers[$api_auth->provider->class]['config']['refresh_token'] = $api_auth->refresh_token;
                }
                if (isset($api_auth->client_realm)) {
                    $shippers[$api_auth->provider->class]['config']['client_realm'] = $api_auth->client_realm;
                }
            }
        }

        foreach ($shippers as $key => $shipper) {
            // Stores any catalog that could not be packed so we can display an error message to the user
            $not_packed = [];
            try {
                // Make the actual packages to ship
                $packages = $shipper['packer']->makePackages($items, $not_packed);
                // If all catalog could be packed, create the shipment and fetch rates
                if (empty($not_packed)) {
                    // create a Shipment object with the provided addresses and packed catalog
                    $shipment = new Shipment($ship_to, $ship_from, $packages);
                    // create the shipper object and pass it the Shipment object and config data array
                    $plugin_class = "\\App\Modules\\Shipping\\APIs\\$key";
                    $plugin = new $plugin_class($shipper['config'], $shipment);
                    // calculate rates for shipment - returns an instance of RatesResponse
                    $plugin_rates = $plugin->getRate($options);

                    if (isset($total_value) && count($user->RulesByPrice($total_value)) > 0) {
                        foreach ($user->RulesByPrice($total_value) as $shipping_rule) {
                            foreach ($plugin_rates->services as $plugin_service) {
                                //return new JsonResponse($shipping_rule->carrier_services->toArray());
                                if ($plugin_service['carrier'] == $shipping_rule->carrier->name
                                    && in_array($plugin_service['service_description'], array_column($shipping_rule->carrier_services->toArray(), 'name'))) {
                                    $rates[] = $plugin_service;
                                }
                            }
                        }
                    } else {
                        $rates = array_merge($rates, $plugin_rates->services);
                    }
                } else {
                    // Display error message if any catalog could not be packed - these need special attention
                    // or may not even be shippable. Customer may still order other catalog after removing the
                    // catalog listed here from their cart.
                    $not_shipped = '';
                    foreach ($not_packed as $p) {
                        $not_shipped .= (empty($p['error']) ? '' : '  '.$p['error']).PHP_EOL;
                    }
                    $rates[$key]['error'] = "The following catalog are not eligible for shipping via $shipper[name]: ".PHP_EOL." $not_shipped";
                }
            } catch (\Exception $e) {
                $rates[$key]['error'] = 'Error: '.$e->getMessage();
            }
        }

        return new JsonResponse($rates);
    }

    public function label(Request $request)
    {
        $user = User::find(Auth::user()->id);

        $selected_rate = $request->json()->all();

        $max_package_weight = 150;
        $max_package_length = 108;
        $max_package_size = 165;

        // The default packing implementation provided packs all catalog separately, but the packing
        // algorithm can be changed simply by changing this line to use a different IPacker implementation.
        $packer = new DefaultPacker($max_package_weight, $max_package_length, $max_package_size, false, ['weight_unit' => 'LB', 'dimension_unit' => 'IN']);
        // Add UPS packer to array
        $shipper = [
            'name'=> $selected_rate['class_name'],
            'packer' => $packer,
            'config' => [
                'weight_unit' => 'LB',
                'dimension_unit' => 'IN',
                'currency_code' => 'USD',
                'access_token' => $selected_rate['access_token'],
            ],
        ];

        $plugin_class = "\\App\Modules\\Shipping\\APIs\\$selected_rate[class_name]";
        $plugin = new $plugin_class($shipper['config']);

        try {
            $label = $plugin->createLabel($selected_rate);
        } catch (\Exception $e) {
            $label['error'] = 'Error: '.$e->getMessage();
        }

        return new JsonResponse($label);
    }

    public function test(Request $request)
    {
        $user = User::find(Auth::user()->id);

        $items = $request->json('packages');
        $ship_from = new Address($request->json('shipper'));
        $ship_to = new Address($request->json('consignee'));
        $options = $request->json('options');
        $total_value = $request->json('total_value');

        $shippers = [];
        $rates = [];
        foreach ($user->api_auths as $api_auth) {
            if ($api_auth->type === 'App\Models\ShippingProvider') {
                $max_package_weight = 150;
                $max_package_length = 108;
                $max_package_size = 165;

                // The default packing implementation provided packs all catalog separately, but the packing
                // algorithm can be changed simply by changing this line to use a different IPacker implementation.
                $packer = new DefaultPacker($max_package_weight, $max_package_length, $max_package_size, false, ['weight_unit' => 'LB', 'dimension_unit' => 'IN']);

                // Add UPS packer to array
                $shippers[$api_auth->provider->class] = [
                    'name'=>$api_auth->provider->name,
                    'packer'=>$packer,
                    'config' => [
                        'weight_unit' => 'LB',
                        'dimension_unit' => 'IN',
                        'currency_code' => 'USD',
                        'access_token' => $api_auth->access_token,
                    ],
                ];

                if (isset($api_auth->client_key)) {
                    $shippers[$api_auth->provider->class]['config']['client_key'] = $api_auth->client_key;
                }
                if (isset($api_auth->client_secret)) {
                    $shippers[$api_auth->provider->class]['config']['client_secret'] = $api_auth->client_secret;
                }
                if (isset($api_auth->refresh_token)) {
                    $shippers[$api_auth->provider->class]['config']['refresh_token'] = $api_auth->refresh_token;
                }
                if (isset($api_auth->client_realm)) {
                    $shippers[$api_auth->provider->class]['config']['client_realm'] = $api_auth->client_realm;
                }
            }
        }

        foreach ($shippers as $key => $shipper) {
            // Stores any catalog that could not be packed so we can display an error message to the user
            $not_packed = [];
            try {
                // Make the actual packages to ship
                $packages = $shipper['packer']->makePackages($items, $not_packed);
                // If all catalog could be packed, create the shipment and fetch rates
                if (empty($not_packed)) {
                    // create a Shipment object with the provided addresses and packed catalog
                    $shipment = new Shipment($ship_to, $ship_from, $packages);
                    // create the shipper object and pass it the Shipment object and config data array
                    $plugin_class = "\\App\Modules\\Shipping\\APIs\\$key";
                    $plugin = new $plugin_class($shipper['config'], $shipment);

                    // calculate rates for shipment - returns an instance of RatesResponse
                    $plugin_rates = $plugin->getRate($options);

                    if (isset($total_value) && count($user->RulesByPrice($total_value)) > 0) {
                        foreach ($user->RulesByPrice($total_value) as $shipping_rule) {
                            foreach ($plugin_rates->services as $plugin_service) {
                                //return new JsonResponse($shipping_rule->carrier_services->toArray());
                                if ($plugin_service['carrier'] == $shipping_rule->carrier->name
                                    && array_contains($plugin_service['service_description'], array_column($shipping_rule->carrier_services->toArray(), 'name'))) {
                                    $rates[] = $plugin_service;
                                }
                            }
                        }
                    } else {
                        $rates = array_merge($rates, $plugin_rates->services);
                    }
                } else {
                    // Display error message if any catalog could not be packed - these need special attention
                    // or may not even be shippable. Customer may still order other catalog after removing the
                    // catalog listed here from their cart.
                    $not_shipped = '';
                    foreach ($not_packed as $p) {
                        $not_shipped .= (empty($p['error']) ? '' : '  '.$p['error']).PHP_EOL;
                    }
                    $rates[$key]['error'] = "The following catalog are not eligible for shipping via $shipper[name]: ".PHP_EOL." $not_shipped";
                }
            } catch (\Exception $e) {
                $rates[$key]['error'] = 'Error: '.$e->getMessage();
            }
        }

        return new JsonResponse($rates);
    }
}
