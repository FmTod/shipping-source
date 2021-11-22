<?php

namespace FmTod\Shipping\Providers\ParcelPro;

use FmTod\Shipping\Providers\ParcelPro\Enums\Carriers;

class PPIPackageType
{
    public $packageTypeCode = '';

    public $packageTypeDesc = '';

    public $carrierServiceCode = '';

    public $carrierCode = Carriers::NotSet;
}
