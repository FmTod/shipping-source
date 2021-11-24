<?php

namespace FmTod\Shipping;

use FmTod\Shipping\Contracts\ShippingProvider;
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
     * @param array $parameters
     * @return ShippingProvider
     *
     * @throws \Throwable
     */
    public static function provider(string $name, array $config = [], array $parameters = []): ShippingProvider
    {
        $providers = config('shipping.providers');

        throw_if(! class_exists($providers[$name]), "The provided shipping provider [$name] does not exist.");

        return new $providers[$name]($config, $parameters);
    }

    /**
     * Create new instance of the class
     *
     * @param array $configs
     * @param array $parameters
     *
     * @throws \Throwable
     */
    public function __construct(array $configs, array $parameters)
    {
        $this->providers = collect($configs)
            ->mapWithKeys(fn ($config, $provider) => [$provider => self::provider($provider, $config, $parameters)]);
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
            ->map(fn (ShippingProvider $provider) => $provider->getCarriers())
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
            ->map(fn (ShippingProvider $provider) => $provider->getServices())
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
            ->map(fn (ShippingProvider $provider) => $provider->getRates($parameters))
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
