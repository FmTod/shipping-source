<?php

namespace FmTod\Shipping\Services\ParcelPro;

class PPIHighValueStatus
{
    public $quoteId = '';

    public $status = FmTod\Shipping\APIs\ParcelPro\Enums\HighValueStatus::New;

    public $message = '';
}
