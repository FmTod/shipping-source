<?php
/**
 * Weight class with static conversion methods.
 *
 * Currently supported units: lb, kg
 *
 * @author Brian Sandall
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */

namespace FmTod\Shipping\Models;

class Weight
{
    protected $value;

    protected $unit;

    public function __construct($value, $unit = 'lb')
    {
        if (filter_var($value, FILTER_VALIDATE_FLOAT) === false) {
            throw new \InvalidArgumentException('Invalid weight value: '.print_r($value, true));
        }
        $this->value = (float) $value;
        $this->unit = self::getStandardUnit($unit);
    }

    /** Returns the raw weight value */
    public function getValue(): float
    {
        return $this->value;
    }

    /** Returns the unit of measure for this weight */
    public function getUnit(): string
    {
        return $this->unit;
    }

    /**
     * Returns this object's weight value converted to the provided unit of measure.
     */
    public function convertTo($to_unit): float
    {
        $to_unit = self::getStandardUnit($to_unit);
        switch ($to_unit) {
        case 'lb': return $this->toPounds();
        case 'kg': return $this->toKilograms();
        }

        return $this->value;
    }

    /**
     * Returns this object's weight value converted to the provided unit of measure and precision.
     * @param int $precision Number of decimals to allow
     */
    public function convertToRounded($to_unit, $precision): float
    {
        return round($this->convertTo($to_unit), $precision);
    }

    /** Returns the weight value in pounds */
    public function toPounds(): float
    {
        switch ($this->unit) {
        case 'kg': return $this->value * 2.204622618;
        default: return $this->value;
        }
    }

    /** Returns the weight value in kilograms */
    public function toKilograms(): float
    {
        switch ($this->unit) {
        case 'lb': return $this->value * 0.45359237;
        default: return $this->value;
        }
    }

    /**
     * Returns the weight value converted to the provided unit of measure.
     *
     * @param $from_unit string|null is 'lb'
     */
    public static function convert($value, $to_unit, ?string $from_unit = 'lb'): float
    {
        $weight = new self($value, $from_unit);

        return $weight->convertTo($to_unit);
    }

    /**
     * Recommend using this method over the raw convert for most purposes.
     * Returns the weight value converted to the provided unit of measure and precision.
     * @param int $precision Number of decimals to allow
     */
    public static function convertRounded($value, $to_unit, $from_unit = 'lb', $precision = 2): float
    {
        return round(self::convert($value, $to_unit, $from_unit), $precision);
    }

    /**
     * Returns the standard unit, e.g. 'lb' or 'kg' instead of e.g. 'lbs' or 'KG'.
     */
    public static function getStandardUnit($unit): string
    {
        if (! is_string($unit)) {
            throw new \InvalidArgumentException('Expected string argument; received: '.print_r($unit, true));
        }
        $unit = strtolower(str_replace('.', '', trim($unit)));
        switch ($unit) {
        case 'lb':
        case 'lbs':
        case 'pound':
        case 'pounds':
            return 'lb';
        case 'kg':
        case 'kgs':
        case 'kilo':
        case 'kilos':
        case 'kilogram':
        case 'kilograms':
            return 'kg';
        }
        throw new \InvalidArgumentException('Unrecognized unit of measure: '.print_r($unit, true));
    }
}
