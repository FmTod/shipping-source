<?php

namespace FmTod\Shipping\Contracts;

interface ShippablePackage
{
    /**
     * Get package weight in the specified unit.
     *
     * @param string $unit
     * @return float
     */
    public function getWeight(string $unit = 'lb'): float;

    /**
     * Get package height in the specified unit.
     *
     * @param string $unit
     * @return float
     */
    public function getHeight(string $unit = 'in'): float;

    /**
     * Get package length in the specified unit.
     *
     * @param string $unit
     * @return float
     */
    public function getLength(string $unit = 'in'): float;

    /**
     * Get package width in the specified unit.
     *
     * @param string $unit
     * @return float
     */
    public function getWidth(string $unit = 'in'): float;
}
