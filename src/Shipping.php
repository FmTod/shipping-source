<?php

namespace FmTod\Shipping;

use FmTod\Shipping\Contracts\Shippable;
use FmTod\Shipping\Contracts\ShippingService;
use FmTod\Shipping\Models\Rate;
use FmTod\Shipping\Models\Shipment;
use Illuminate\Support\Collection;

class Shipping
{
    /**
     * Collection of shipping service providers
     *
     * @var \Illuminate\Support\Collection
     */
    protected Collection $providers;

    /**
     * Create a new instance of the specified provider.
     *
     * @param string $name
     * @param array $config
     * @param \FmTod\Shipping\Contracts\Shippable|null $shipment
     * @return ShippingService
     *
     * @throws \Throwable
     */
    public static function provider(string $name, array $config = [], Shippable $shipment = null): ShippingService
    {
        $providers = config('shipping.providers');

        throw_if(! class_exists($providers[$name]), "The provided shipping provider [$name] does not exist.");

        return new $providers[$name]($config, $shipment);
    }

    /**
     * Create new instance of the class
     *
     * @param array $configs
     * @param \FmTod\Shipping\Contracts\Shippable $shipment
     *
     * @throws \Throwable
     */
    public function __construct(array $configs, Shippable $shipment)
    {
        $this->providers = collect($configs)
            ->mapWithKeys(fn ($config, $provider) => [$provider => self::provider($provider, $config, $shipment)]);
    }

    /**
     * Get a list of all carriers.
     *
     * @param string|null $provider
     * @return \Illuminate\Support\Collection
     */
    public function carriers(?string $provider = null): Collection
    {
        if ($provider) {
            return $this->providers->get($provider)->getCarriers();
        }

        return $this->providers
            ->values()
            ->map(fn (ShippingService $provider) => $provider->getCarriers())
            ->flatten(1);
    }

    /**
     * Get a list of all carriers.
     *
     * @param string|null $provider
     * @return \Illuminate\Support\Collection
     */
    public function services(?string $provider = null): Collection
    {
        if ($provider) {
            return $this->providers->get($provider)->getServices();
        }

        return $this->providers
            ->values()
            ->map(fn (ShippingService $provider) => $provider->getServices())
            ->flatten(1);
    }

    /**
     * Get all available rates for the shipment
     *
     * @param array $parameters
     * @param string|null $provider
     * @return \Illuminate\Support\Collection
     */
    public function rates(array $parameters = [], ?string $provider = null): Collection
    {
        if ($provider) {
            return $this->providers->get($provider)->getRates($parameters);
        }

        return $this->providers
            ->values()
            ->map(fn (ShippingService $provider) => $provider->getRates($parameters))
            ->flatten(1);
    }

    /**
     * Create shipment from the provided rate
     *
     * @param \FmTod\Shipping\Models\Rate $rate
     * @return \FmTod\Shipping\Models\Shipment
     */
    public function shipment(Rate $rate): Shipment
    {
        return $this->providers->get($rate->provider->name)->createShipment($rate);
    }
}
