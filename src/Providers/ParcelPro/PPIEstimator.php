<?php

namespace FmTod\Shipping\Providers\ParcelPro;

class PPIEstimator
{
    public $EstimatorHeaderID = '0';

    public $QuoteID = '';

    public $CustomerId = '';

    public $UserId = 0;

    public $CarrierCode = '';

    public $ServiceCode = '';

    public $ServiceCodeDescription = '';

    public $BaseRate = 0;

    public $AccessorialsCost = 0;

    public $TotalCharges = 0;

    public $ExceededMaxCoverage = false;

    public $CreationDate;

    public $HasDetail = false;

    public $BusinessDaysInTransit = 1;

    public $DeliveryByTime = '';

    public $EstimatorDetail = [];
}
