<?php

namespace FmTod\Shipping;

use FmTod\Shipping\Contracts\ShippingProvider;
use FmTod\Shipping\Models\Rate;
use FmTod\Shipping\Models\Shipment;
use Illuminate\Support\Collection;

class Shipping
{
    protected Collection $providers;

    protected ?array $parameters = null;

    /**
     * Create a new instance of the specified provider.
     *
     * @param string $name
     * @param array $config
     * @param array|null $parameters
     * @return ShippingProvider
     *
     * @throws \Throwable
     */
    public static function provider(string $name, array $config, ?array $parameters = null): ShippingProvider
    {
        $providers = config('shipping.providers');

        throw_if(! class_exists($providers[$name]), "The provided shipping provider [$name] does not exist.");

        return new $providers[$name]($config, $parameters);
    }

    /**
     * Initialize class statically with the provided shipping providers
     *
     * @throws \Throwable
     */
    public static function providers(array $configs, ?array $parameters = null): static
    {
        return new static($configs, $parameters);
    }

    /**
     * Create new instance of the class
     *
     * @param array $configs
     * @param array|null $parameters
     *
     * @throws \Throwable
     */
    final public function __construct(array $configs, ?array $parameters = null)
    {
        $this->parameters = $parameters;

        $this->providers = collect($configs)
            ->mapWithKeys(fn ($config, $provider) => [
                $provider => self::provider($provider, $config, $this->parameters),
            ]);
    }

    /**
     * Set parameters for all providers.
     *
     * @param array $parameters
     * @return $this
     */
    public function setParameters(array $parameters): static
    {
        $this->parameters = $parameters;

        $this->providers->each(function (ShippingProvider $provider) {
            if (isset($this->parameters['consignor'])) {
                $provider->setConsignor($this->parameters['consignor']);
            }

            if (isset($this->parameters['consignee'])) {
                $provider->setConsignee($this->parameters['consignee']);
            }

            if (isset($this->parameters['package'])) {
                $provider->setPackage($this->parameters['package']);
            }
        });

        return $this;
    }

    /**
     * Add new provider to the already existing providers.
     *
     * @param string $name
     * @param array $config
     * @return $this
     * @throws \Throwable
     */
    public function addProvider(string $name, array $config): static
    {
        $this->providers->put($name, self::provider($name, $config, $this->parameters));

        return $this;
    }

    /**
     * Get provider from the list of initialized providers
     *
     * @param string $name
     * @return $this
     */
    public function getProvider(string $name): static
    {
        return $this->providers->get($name);
    }

    /**
     * Update provider configs and parameters.
     *
     * @param string $name
     * @param array $config
     * @return $this
     * @throws \Throwable
     */
    public function updateProvider(string $name, array $config): static
    {
        $provider = $this->providers->get($name);

        $provider->setConfig($config);
        $provider->setConfig($this->parameters);

        return $this;
    }

    /**
     * Remove provider from the list of initialized providers.
     *
     * @param string $name
     * @return $this
     */
    public function removeProvider(string $name): static
    {
        $this->providers->forget($name);

        return $this;
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

        return $this->providers->values()
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

        return $this->providers->values()
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

        return $this->providers->values()
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
