<?php

namespace FmTod\Shipping\Services\ParcelPro\Enums;

use BenSampo\Enum\Enum;

final class HighValueStatus extends Enum
{
    public const   New = 0;

    public const   Assigned = 1;

    public const   Approved = 2;

    public const   Rejected = 3;

    public const   Printed = 4;

    public const   Expired = 5;

    public const   Voided = 6;

    public const   Cancelled = 7;
}
