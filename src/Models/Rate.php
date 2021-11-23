<?php

namespace FmTod\Shipping\Models;

use FmTod\Money\Money;

/**
 * Class Rate.
 *
 * @property string $id
 * @property Provider $provider
 * @property Carrier $carrier
 * @property Service $service
 * @property array $duration
 * @property array $attributes
 * @property array $messages
 * @property Money $amount
 * @property array $parameters
 */
class Rate extends Model
{
    protected array $fillable = [
        'id',
        'provider',
        'carrier',
        'service',
        'service',
        'duration',
        'amount',
        'attributes',
        'messages',
        'parameters',
    ];

    /**
     * Rate constructor.
     *
     * @param array $properties
     * @throws \Exception
     */
    public function __construct(array $properties = [])
    {
        if (isset($properties['provider']) && is_array($properties['provider'])) {
            $properties['provider'] = new Provider($properties['provider']);
        }

        if (isset($properties['carrier']) && is_array($properties['carrier'])) {
            $properties['carrier'] = new Carrier($properties['carrier']);
        }

        if (isset($properties['service']) && is_array($properties['service'])) {
            $properties['service'] = new Service($properties['service']);
        }

        if (isset($properties['duration']) && is_array($properties['duration'])) {
            $properties['duration'] = new Duration($properties['duration']);
        }

        if (isset($properties['amount']) && is_array($properties['amount'])) {
            $properties['amount'] = Money::parse($properties['amount']['value'], $properties['amount']['currency']);
        }

        parent::__construct($properties);
    }
}
