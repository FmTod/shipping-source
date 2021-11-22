<?php

namespace FmTod\Shipping\Providers\ParcelPro\Enums;

use BenSampo\Enum\Enum;

final class Carriers extends Enum
{
    public const   NotSet = 0;

    public const   Ups = 1;

    public const   Fedex = 2;

    public const   Dhl = 3;

    public const   Usps = 4;
}
