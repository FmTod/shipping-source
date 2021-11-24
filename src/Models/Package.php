<?php
/**
 * The package class creates an object for each package being shipped.
 *
 * @authors Alex Fraundorf - AlexFraundorf.com, Brian Sandall
 * @copyright (c) 2017, Brian Sandall
 * @copyright (c) 2012-2013, Alex Fraundorf and AffordableWebSitePublishing.com LLC
 * @version 03/16/2017 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and
 *      there is plenty of room for improvement.  Use at your own risk.
 * @since 12/02/2012
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */

namespace FmTod\Shipping\Models;

use FmTod\Shipping\Concerns\ConvertsUnits;
use FmTod\Shipping\Contracts\ShippablePackage;

class Package implements ShippablePackage
{
    use ConvertsUnits;

    protected int|float $weight;

    protected int|float $length;

    protected int|float $width;

    protected int|float $height;

    /**
     * Constructor sets class properties and delegates calculation of the package size.
     *
     * @param float|int $weight the weight of the package in pounds
     * @param array $dimensions array elements can be integers or floats in inches
     */
    public function __construct(float|int $weight, array $dimensions, $massUnit = 'lb', $distanceUnit = 'in')
    {
        $this->weight = $this->convertWeight($weight, $massUnit);

        rsort($dimensions, SORT_NUMERIC);
        $this->length = round($this->convertLength($dimensions[0], $distanceUnit), 2);
        $this->width = round($this->convertLength($dimensions[1], $distanceUnit), 2);
        $this->height = round($this->convertLength($dimensions[2], $distanceUnit), 2);
    }

    /**
     * Get package weight in the specified unit.
     *
     * @param string $unit
     * @return float
     */
    public function getWeight(string $unit = 'lb'): float
    {
        return $this->convertWeight($this->weight, $unit);
    }

    /**
     * Get package height in the specified unit.
     *
     * @param string $unit
     * @return float
     */
    public function getHeight(string $unit = 'in'): float
    {
        return $this->convertLength($this->height, $unit);
    }

    /**
     * Get package length in the specified unit.
     *
     * @param string $unit
     * @return float
     */
    public function getLength(string $unit = 'in'): float
    {
        return $this->convertLength($this->length, $unit);
    }

    /**
     * Get package width in the specified unit.
     *
     * @param string $unit
     * @return float
     */
    public function getWidth(string $unit = 'in'): float
    {
        return $this->convertLength($this->width, $unit);
    }
}
