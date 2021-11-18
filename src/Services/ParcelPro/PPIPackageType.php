<?php

namespace FmTod\Shipping\Services\ParcelPro;

class PPIPackageType
{
    public $packageTypeCode = '';

    public $packageTypeDesc = '';

    public $carrierServiceCode = '';

    public $carrierCode = FmTod\Shipping\APIs\ParcelPro\Enums\Carriers::NotSet;
}
