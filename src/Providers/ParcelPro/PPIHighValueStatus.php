<?php

namespace FmTod\Shipping\Providers\ParcelPro;

use FmTod\Shipping\Providers\ParcelPro\Enums\HighValueStatus;

class PPIHighValueStatus
{
    public $quoteId = '';

    public $status = HighValueStatus::New;

    public $message = '';
}
