<?php

namespace FmTod\Shipping\Providers\ParcelPro\Enums;

use BenSampo\Enum\Enum;

class ShipmentPurpose extends Enum
{
    public const Commercial = 'Commercial';
    public const Gift = 'Gift';
    public const PersonalEffects = 'Personal Effects';
    public const PersonalUse = 'Personal use';
    public const ReturnAndRepair = 'Return and Repair';
    public const Sample = 'Sample';
}
