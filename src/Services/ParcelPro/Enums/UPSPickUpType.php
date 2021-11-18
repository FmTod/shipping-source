<?php

namespace FmTod\Shipping\Services\ParcelPro\Enums;

use BenSampo\Enum\Enum;

final class UPSPickUpType extends Enum
{
    public const Ocassional = 0;

    public const RequestPending = 1;

    public const DailyPickUp = 2;

    public const DailyOnRoutePickUp = 3;

    public const DaySpecificPickUp = 4;

    public const SmartPickUp = 5;
}
