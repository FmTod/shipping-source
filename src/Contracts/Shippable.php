<?php

namespace FmTod\Shipping\Contracts;

use FmTod\Shipping\Models\Address;
use FmTod\Shipping\Models\Package;

interface Shippable
{
    /**
     * Return the Address to which this shipment is being sent.
     * @return Address
     */
    public function getShipToAddress(): Address;

    /**
     * Returns the Address from which the shipment is being sent if different from the shipper's address.
     * @return Address or possibly null if ship from address not specified
     */
    public function getShipFromAddress(): Address;

    /**
     * Add a package to this shipment.
     * @param Package $package
     */
    public function addPackage(Package $package);

    /**
     * @return array<Package> All Packages in this shipment
     */
    public function getPackages(): array;
}
