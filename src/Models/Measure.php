<?php
/**
 * Measurements class with static conversion methods.
 *
 * Currently supported units: in, cm
 *
 * @author Brian Sandall
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */

namespace FmTod\Shipping\Models;

class Measure
{
    /** One inch equals exactly 2.54 centimeters, so use the inverse for the other way */
    public const CM_TO_IN = (1.0 / 2.54);

    protected $value;

    protected $unit;

    public function __construct($value, $unit = 'in')
    {
        if (filter_var($value, FILTER_VALIDATE_FLOAT) === false) {
            throw new \InvalidArgumentException('Invalid measurement value: '.print_r($value, true));
        }
        $this->value = (float) $value;
        $this->unit = self::getStandardUnit($unit);
    }

    /** Returns the raw measurement value */
    public function getValue()
    {
        return $this->value;
    }

    /** Returns the unit of measure for this measurement */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * Returns this object's measurement value converted to the provided unit of measure.
     * @param $to_unit
     * @return float|int
     */
    public function convertTo($to_unit)
    {
        $to_unit = self::getStandardUnit($to_unit);
        switch ($to_unit) {
        case 'in': return $this->toInches();
        case 'cm': return $this->toCentimeters();
        }

        return $this->value;
    }

    /**
     * Returns this object's measurement value converted to the provided unit of measure and precision.
     * @param $to_unit
     * @param int $precision Number of decimals to allow
     * @return float
     */
    public function convertToRounded($to_unit, $precision)
    {
        return round($this->convertTo($to_unit), $precision);
    }

    /** Returns the measurement value in inches */
    public function toInches()
    {
        switch ($this->unit) {
        case 'cm': return $this->value * self::CM_TO_IN;
        default: return $this->value;
        }
    }

    /** Returns the measurement value in centimeters */
    public function toCentimeters()
    {
        switch ($this->unit) {
        case 'in': return $this->value * 2.54;
        default: return $this->value;
        }
    }

    /**
     * Returns the measurement value converted to the provided unit of measure.
     * @param $value
     * @param $to_unit
     * @param string $from_unit Default is 'in'
     * @return float|int
     */
    public static function convert($value, $to_unit, $from_unit = 'in')
    {
        $measure = new self($value, $from_unit);

        return $measure->convertTo($to_unit);
    }

    /**
     * Recommend using this method over the raw convert for most purposes.
     * Returns the measurement value converted to the provided unit of measure and precision.
     * @param $value
     * @param $to_unit
     * @param string $from_unit
     * @param int $precision Number of decimals to allow
     * @return float
     */
    public static function convertRounded($value, $to_unit, $from_unit = 'in', $precision = 2)
    {
        return round(self::convert($value, $to_unit, $from_unit), $precision);
    }

    /**
     * Returns the standard unit, e.g. 'in' or 'cm' instead of e.g. 'inches' or 'CM'.
     * @param $unit
     * @return string
     */
    public static function getStandardUnit($unit)
    {
        if (! is_string($unit)) {
            throw new \InvalidArgumentException('Expected string argument; received: '.print_r($unit, true));
        }
        $unit = strtolower(str_replace('.', '', trim($unit)));
        switch ($unit) {
        case 'imperial':
        case 'in':
        case 'ins':
        case 'inch':
        case 'inches':
            return 'in';
        case 'metric':
        case 'cm':
        case 'cms':
        case 'centimeter':
        case 'centimeters':
            return 'cm';
        }

        throw new \InvalidArgumentException('Unrecognized unit of measure: '.print_r($unit, true));
    }
}
