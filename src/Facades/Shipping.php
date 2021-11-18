<?php

namespace FmTod\Shipping\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \FmTod\Shipping\Shipping
 */
class Shipping extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'shipping';
    }
}
