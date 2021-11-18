<?php
/**
 * Interface for any algorithm which packs products or smaller packages into packages for shipment.
 *
 * @author Brian Sandall
 * @copyright (c) 2015 Brian Sandall
 * @version 07/07/2015 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and
 *      there is plenty of room for improvement.  Use at your own risk.
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */

namespace FmTod\Shipping\Contracts;

interface Packer
{
    /**
     * Packs requested catalog into shippable packages.
     * @param array $items All catalog to be packaged; at a minimum, each entry must be able to provide
     *                     its weight, dimensions (length, width, height), and usually quantity
     * @param array &$notPacked Any catalog which can not be packed will be stored in this array
     * @return array of Package objects, possibly empty if no catalog could be packaged
     */
    public function makePackages(array $items, array &$notPacked = []): array;
}
