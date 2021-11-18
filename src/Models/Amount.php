<?php

namespace FmTod\Shipping\Models;

/**
 * Class Amount.
 *
 * @property string $currency_code
 * @property float $value
 */
class Amount
{
    public $currency_code;

    public $value;

    /**
     * Amount constructor.
     * @param array|float $values Array of properties or amount value as double
     * @param null|string $currency_code Amount's currency code or null
     * @throws \Exception
     */
    public function __construct($values, $currency_code = null)
    {
        if (is_array($values)) {
            foreach ($values as $property => $value) {
                if (property_exists($this, $property)) {
                    $this->$property = $value;
                } else {
                    throw new \Exception("Property [$property] does not exists.");
                }
            }
        } else {
            $this->value = $values;
            $this->currency_code = $currency_code;
        }
    }

    /**
     * Return array of amount.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'value' => $this->value,
            'currency_code' => $this->currency_code,
        ];
    }
}
