<?php

namespace FmTod\Shipping\Providers;

use FmTod\Shipping\Contracts\ShippableAddress;
use FmTod\Shipping\Contracts\ShippablePackage;
use FmTod\Shipping\Contracts\ShippingProvider;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * @method static static config(array $config = [])
 * @method static static consignor(ShippableAddress $config = [])
 * @method static static consignee(ShippableAddress $config = [])
 * @method static static package(ShippablePackage $config = [])
 */
abstract class BaseProvider implements ShippingProvider
{
    /**
     * Provider config
     *
     * @var array
     */
    protected array $config = [];

    /**
     * List of available carriers.
     *
     * @var Collection
     */
    protected Collection $carriers;

    /**
     * List of available services.
     *
     * @var Collection
     */
    protected Collection $services;

    protected ShippableAddress|null $consignor = null;

    protected ShippableAddress|null $consignee = null;

    protected ShippablePackage|null $package = null;

    /**
     * Constructor function - sets object properties.
     *
     * @param array $config the configuration data
     * @param array|null $parameters
     */
    public function __construct(array $config = [], ?array $parameters = null)
    {
        $this->carriers = collect([]);
        $this->services = collect([]);

        // set the config array property
        $this->setConfig($config);

        if (isset($parameters['consignor']) && $parameters['consignor'] instanceof ShippableAddress) {
            $this->consignor = $parameters['consignor'];
        }

        if (isset($parameters['consignee']) && $parameters['consignee'] instanceof ShippableAddress) {
            $this->consignee = $parameters['consignee'];
        }

        if (isset($parameters['package']) && $parameters['package'] instanceof ShippablePackage) {
            $this->package = $parameters['package'];
        }
    }

    /**
     * Validate the config array and sets it as an object property.
     *
     * @param array $config
     * @return static
     */
    public function setConfig(array $config = []): static
    {
        // validate the config array
        if (! is_array($config)) {
            throw new InvalidArgumentException('Config array is not valid or empty.');
        }
        // set the object config array
        $this->config = $config;

        return $this;
    }

    /**
     * Set the consignor for the shipping provider
     *
     * @param \FmTod\Shipping\Contracts\ShippableAddress $address
     * @return $this
     */
    public function setConsignor(ShippableAddress $address): static
    {
        $this->consignor = $address;

        return $this;
    }

    /**
     * Set consignee for the shipping provider
     *
     * @param \FmTod\Shipping\Contracts\ShippableAddress $address
     * @return $this
     */
    public function setConsignee(ShippableAddress $address): static
    {
        $this->consignee = $address;

        return $this;
    }

    /**
     * Set package for the shipping provider
     *
     * @param \FmTod\Shipping\Contracts\ShippablePackage $package
     * @return $this
     */
    public function setPackage(ShippablePackage $package): static
    {
        $this->package = $package;

        return $this;
    }

    /**
     * Get the consignor for the shipping provider
     *
     * @return \FmTod\Shipping\Contracts\ShippableAddress|null
     */
    public function getConsignor(): ?ShippableAddress
    {
        return $this->consignor;
    }

    /**
     * Get the consignee for the shipping provider
     *
     * @return \FmTod\Shipping\Contracts\ShippableAddress|null
     */
    public function getConsignee(): ?ShippableAddress
    {
        return $this->consignee;
    }

    /**
     * Get the package for the shipping provider
     *
     * @return \FmTod\Shipping\Contracts\ShippablePackage|null
     */
    public function getPackage(): ?ShippablePackage
    {
        return $this->package;
    }

    /**
     * Retrieves a list of available carriers.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getCarriers(): Collection
    {
        return $this->carriers;
    }

    /**
     * Retrieves a list of available services.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    /**
     * Handle dynamic static method calls into the method.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public static function __callStatic(string $method, array $parameters)
    {
        $instance = new static();

        $methodName = 'set'.ucfirst($method);

        return call_user_func_array([$instance, $methodName], $parameters);
    }
}
