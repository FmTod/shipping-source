<?php

namespace FmTod\Shipping\Models;

/**
 * Class Carrier.
 *
 * @property string|null $name
 * @property string $value
 */
class Carrier extends Model
{
    protected array $fillable = [
        'name',
        'value',
        'data',
    ];

    protected array $rules = [
        'name' => 'sometimes|nullable|string',
        'value' => 'required|string',
        'data' => 'sometimes|nullable|array',
    ];
}
