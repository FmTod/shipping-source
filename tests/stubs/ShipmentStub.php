<?php
/**
 * Default implementation of IShipment replaces Alex Fraundorf's original Awsp\Shipment class.
 *
 * @author Brian Sandall
 * @copyright (c) 2015 Brian Sandall
 * @version 07/07/2015 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and
 *      there is plenty of room for improvement.  Use at your own risk.
 * @since 12/02/2012
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */

namespace FmTod\Shipping\Tests\stubs;

use FmTod\Shipping\Contracts\Shippable;
use FmTod\Shipping\Models\Address;
use FmTod\Shipping\Models\Package;
use InvalidArgumentException;
use UnexpectedValueException;

class ShipmentStub implements Shippable
{
    protected Address $shipping_address;

    protected ?Address $shipper_address;

    protected array $packages = [];

    /**
     * @param \FmTod\Shipping\Models\Address $to Address to which the shipment is being delivered
     * @param \FmTod\Shipping\Models\Address|null $from Leave as null to use the default shipper's address as set in the config,
     *              or pass in a valid Address object to use a different address
     * @param array $packages Optional array of Package objects in this shipment;
     *                  if empty, packages must be added one at a time via #addPackage
     */
    public function __construct(Address $to, Address $from = null, array $packages = [])
    {
        $this->shipping_address = $to;
        if ($from !== null && ! ($from instanceof Address)) {
            throw new InvalidArgumentException("Expected from address to be of type Address; received ".gettype($from));
        }
        $this->shipper_address = $from;
        foreach ($packages as $package) {
            if (! ($package instanceof Package)) {
                throw new InvalidArgumentException("Expected package to be of type Package; received ".gettype($package));
            }
            $this->packages[] = $package;
        }
    }

    /**
     * @return object Address for the destination address
     */
    public function getShipToAddress(): Address
    {
        return $this->shipping_address;
    }

    /**
     * @return object Address for the address of origin or null if it was not specified
     */
    public function getShipFromAddress(): ?Address
    {
        return $this->shipper_address;
    }

    /**
     * Adds a Package to this shipment.
     * @param Package $package
     */
    public function addPackage(Package $package): void
    {
        $this->packages[] = $package;
    }

    /**
     * Returns the array containing the package(s) object(s) or throws an exception if there are none.
     * @throws UnexpectedValueException if the packages array is empty
     * @return array containing all package object(s) that belong to this Shipment
     */
    public function getPackages(): array
    {
        if (empty($this->packages)) {
            throw new UnexpectedValueException('There is no data in the packages array.');
        }

        return $this->packages;
    }
}
