<?php

namespace FmTod\Shipping\Models;

/**
 * Class Service.
 *
 * @property string|null $name
 * @property string $value
 * @property array $data
 */
class Service extends Model
{
    protected array $fillable = [
        'carrier',
        'name',
        'value',
        'data',
    ];

    protected array $rules = [
        'carrier' => 'sometimes|nullable|string',
        'name' => 'sometimes|nullable|string',
        'value' => 'required|string',
        'data' => 'sometimes|nullable|array',
    ];
}
