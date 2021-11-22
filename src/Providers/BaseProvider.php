<?php

namespace FmTod\Shipping\Providers;

use FmTod\Shipping\Contracts\Shippable;
use FmTod\Shipping\Contracts\ShippingService;
use Illuminate\Support\Collection;
use InvalidArgumentException;

abstract class BaseProvider implements ShippingService
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

    /**
     * The Shipment object to process which contains Package object(s).
     *
     * @var Shippable|null
     */
    protected Shippable|null $shippable = null;

    /**
     * Constructor function - sets object properties.
     *
     * @param array $config the configuration data
     * @param Shippable|null $shippable
     */
    public function __construct(array $config, Shippable $shippable = null)
    {
        $this->carriers = collect([]);
        $this->services = collect([]);

        // set the config array property
        $this->setConfig($config);

        // set the local reference of the Shipment object
        if ($shippable !== null) {
            $this->setShippable($shippable);
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
        if (! is_array($config) || empty($config)) {
            throw new InvalidArgumentException('Config array is not valid or empty.');
        }
        // set the object config array
        $this->config = $config;

        return $this;
    }

    /**
     * Sets the IShipment object for which rates or labels will be generated.
     *
     * @param Shippable $shipment
     * @return static
     */
    public function setShippable(Shippable $shipment): static
    {
        $this->shippable = $shipment;

        return $this;
    }

    /**
     * Retrieves the shipment object.
     *
     * @return \FmTod\Shipping\Contracts\Shippable|null shipment
     * @version updated 12/09/2012
     * @since 12/08/2012
     */
    public function getShippable(): ?Shippable
    {
        return $this->shippable;
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
}
