<?php

namespace FmTod\Shipping\Services\ParcelPro;

class PPIEstimatorRequest
{
    public $ShipToResidential = false;

    public $ShipTo = null;

    public $ShipFrom = null;

    public $Height = 0;

    public $Width = 0;

    public $Length = 0;

    public $Weight = 1.0;

    public $InsuredValue = 0.0;

    public $IsSaturdayPickUp = false;

    public $IsSaturdayDelivery = false;

    public $IsDeliveryConfirmation = false;

    public $IsDirectSignature = false;

    public $IsCod = false;

    public $CodAmount = 0.0;

    public $IsSecuredCod = false;

    public $IsRegularPickUp = false;

    public $IsDropoff = true;

    public $UpdateAddressBook = false;

    public $NotifyRecipient = false;

    public $IsPickUpRequested = false;

    public $IsSmartPickUp = false;

    public $NotifySender = false;

    public $IsThermal = true;

    public $IsMaxCoverageExceeded = false;

    public $IsBillToThirdParty = false;

    public $IsShipFromRestrictedZip = false;

    public $IsShipToRestrictedZip = false;

    public $IsShipToHasRestrictedWords = false;

    public $IsShipFromHasRestrictedWords = false;

    public $IsHighValueShipment = false;

    public $IsHighValueReport = false;
}
