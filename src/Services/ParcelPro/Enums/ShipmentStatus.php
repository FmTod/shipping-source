<?php

namespace FmTod\Shipping\Services\ParcelPro\Enums;

use BenSampo\Enum\Enum;

final class ShipmentStatus extends Enum
{
    public const BillingInfo = 0;

    public const Manifest = 1;

    public const PickupScan = 2;

    public const Voided = 3;

    public const LabelPrinted = 4;

    public const InTransit = 5;

    public const Delivered = 6;

    public const Exception = 7;

    public const ReprintRequested = 8;

    public const TNF = 9;

    public const BIRNew = 10;

    public const VoidButInTransit = 11;

    public const VoidButDelivered = 12;

    public const VoidButException = 13;

    public const Reinstated = 14;
}
