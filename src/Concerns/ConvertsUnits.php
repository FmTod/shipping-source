<?php

namespace FmTod\Shipping\Concerns;

use FmTod\Shipping\Exceptions\InvalidDistanceUnitException;
use FmTod\Shipping\Exceptions\InvalidMassUnitException;
use Illuminate\Support\Str;

trait ConvertsUnits
{
    /**
     * Convert provided weight to supported units
     *
     * @param float|int $value
     * @param string $unit
     * @return float|int
     */
    protected function convertWeight(float|int $value, string $unit): float|int
    {
        return match (Str::singular(Str::lower($unit))) {
            'lb' => $value,
            'kg' => $value * 0.453592,
            'oz' => $value * 16,
            'g' => $value * 453.6,
            default => throw new InvalidMassUnitException('Provided unit is not supported or invalid.')
        };
    }

    /**
     * Convert provided length to supported units
     *
     * @param float|int $value
     * @param string $unit
     * @return float|int
     */
    protected function convertLength(float|int $value, string $unit): float|int
    {
        return match (Str::singular(Str::lower($unit))) {
            'in' => $value,
            'cm' => $value * 2.54,
            'ft' => $value * 0.0833333,
            'm' => $value * 0.0254,
            default => throw new InvalidDistanceUnitException('Provided unit is not supported or invalid.')
        };
    }
}
