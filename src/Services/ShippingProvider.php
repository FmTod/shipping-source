<?php

namespace FmTod\Shipping\Services;

use FmTod\Shipping\Contracts\Shippable;
use FmTod\Shipping\Contracts\ShippingService;
use Illuminate\Support\Collection;

abstract class ShippingProvider implements ShippingService
{
    /**
     * @var array holder for config data (from includes/config.php)
     */
    protected array $config = [];

    /**
     * @var Collection List of available carriers
     */
    protected Collection $carriers;

    /**
     * @var Collection List of available services
     */
    protected Collection $services;

    /**
     * @var Shippable|null The Shipment object to process which contains Package object(s)
     */
    protected Shippable|null $shipment = null;

    /**
     * Constructor function - sets object properties.
     *
     * @param array $config the configuration data
     * @param Shippable|null $shipment
     */
    public function __construct(array $config, Shippable $shipment = null)
    {
        $this->carriers = collect([]);
        $this->services = collect([]);

        // set the config array property
        $this->setConfig($config);

        // set the local reference of the Shipment object
        if ($shipment !== null) {
            $this->setShipment($shipment);
        }
    }

    /**
     * Validate the config array and sets it as an object property.
     *
     * @param array $config
     * @throws \InvalidArgumentException
     * @version 04/19/2013
     * @since 04/19/2013
     */
    public function setConfig(array $config = []): void
    {
        // validate the config array
        if (! is_array($config) || empty($config)) {
            throw new \InvalidArgumentException('Config array is not valid or empty.');
        }
        // set the object config array
        $this->config = $config;
    }

    /**
     * Sets the IShipment object for which rates or labels will be generated.
     *
     * @param Shippable $shipment
     * @version 07/07/2015
     * @since 04/19/2013
     */
    public function setShipment(Shippable $shipment): void
    {
        $this->shipment = $shipment;
    }

    /**
     * Retrieves the shipment object.
     *
     * @return \FmTod\Shipping\Contracts\Shippable|null shipment
     * @version updated 12/09/2012
     * @since 12/08/2012
     */
    public function getShipment(): ?Shippable
    {
        return $this->shipment;
    }

    /**
     * Retrieves a list of available carriers.
     *
     * @return \FmTod\Shipping\Models\Carrier[]
     */
    public function getCarriers(): Collection
    {
        return $this->carriers;
    }

    /**
     * Retrieves a list of available services.
     *
     * @return \FmTod\Shipping\Models\Service[]
     */
    public function getServices(): Collection
    {
        return $this->services;
    }
}
