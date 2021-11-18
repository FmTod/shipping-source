<?php

namespace FmTod\Shipping\Models;

/**
 * Class Provider.
 *
 * @property string $name
 * @property string $class
 */
class Provider extends Model
{
    protected array $fillable = [
        'name',
        'class',
    ];

    protected static array $rules = [
        'name' => 'required|string',
        'class' => 'required|string',
    ];
}
