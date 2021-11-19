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
 */
class Rate
{
    public string|int $id;

    public Provider $provider;

    public Carrier $carrier;

    public Service $service;

    public Duration $duration;

    public Money $amount;

    public array $attributes = [];

    public array $messages = [];

    /**
     * Rate constructor.
     * @param array $properties
     * @throws \Exception
     */
    public function __construct(array $properties = [])
    {
        if (isset($properties['provider']) && is_array($properties['provider'])) {
            $this->provider = new Provider($properties['provider']);
        } elseif (isset($properties['provider']) && $properties['provider'] instanceof Provider) {
            $this->provider = $properties['provider'];
        }

        if (isset($properties['carrier']) && is_array($properties['carrier'])) {
            $this->carrier = new Carrier($properties['carrier']);
        } elseif (isset($properties['carrier']) && $properties['carrier'] instanceof Carrier) {
            $this->carrier = $properties['carrier'];
        }

        if (isset($properties['service']) && is_array($properties['service'])) {
            $this->service = new Service($properties['service']);
        } elseif (isset($properties['service']) && $properties['service'] instanceof Service) {
            $this->service = $properties['service'];
        }

        if (isset($properties['id'])) {
            $this->id = $properties['id'];
        }

        if (isset($properties['duration'])) {
            $this->duration = $properties['duration'];
        }

        if (isset($properties['attributes'])) {
            $this->attributes = $properties['attributes'];
        }

        if (isset($properties['messages'])) {
            $this->messages = $properties['messages'];
        }

        if (isset($properties['amount']) && is_array($properties['amount'])) {
            $this->amount = Money::parse($properties['amount']['value'], $properties['amount']['currency']);
        } elseif (isset($properties['amount']) && $properties['amount'] instanceof Money) {
            $this->amount = $properties['amount'];
        }
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider->toArray(),
            'carrier' => $this->carrier->toArray(),
            'service' => $this->service->toArray(),
            'duration' => $this->duration->toArray(),
            'attributes' => $this->attributes,
            'messages' => $this->messages,
            'amount' => $this->amount->toArray(),
        ];
    }
}
