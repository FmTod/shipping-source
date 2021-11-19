<?php

namespace FmTod\Shipping\Models;

/**
 * @property \FmTod\Shipping\Models\Provider $provider
 * @property \FmTod\Shipping\Models\Carrier $carrier
 * @property \FmTod\Shipping\Models\Service $service
 * @property \FmTod\Shipping\Models\Duration $duration
 * @property \FmTod\Money\Money $amount
 * @property string $tracking_number
 * @property \Illuminate\Support\Collection $labels
 * @property mixed $data
 */
class Shipment extends Model
{
    protected array $fillable = [
        'provider',
        'carrier',
        'service',
        'duration',
        'amount',
        'tracking_number',
        'labels',
        'data',
    ];
}
