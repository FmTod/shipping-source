<?php

namespace FmTod\Shipping\Services\ParcelPro;

use FmTod\Shipping\Services\ParcelPro\Enums\Carriers;

class PPIPackage
{
    public $carrierCode = Carriers::NotSet; // Enums.Carriers.NotSet. Required must be UPS or Fedex

    public $ServiceCode = ''; // Populate this property from your available services, see references to get an available list of service

    public $shipTo = null;

    public $shipFrom = null;

    public $NotifySender = false; //Optional

    public $UpdateAddressBook = false; // Optional, if true, the ShipTo class will be updated/added to your address book regardless of the response

    public $ShipToResidential = false; // True if this is a residential address, false is this is a business

    public $Weight = 0; // Required, round up to the nearest whole number

    public $Width = 0; // required when package code 02 (your packaging) is used

    public $Height = 0; //required when package code 02 (your packaging) is used

    public $Length = 0; // required when package code 02 (your packaging) is used

    public $InsuredValue = 0; // Amount that you want to insure

    public $IsSaturdayDelivery = false; // True if Saturday Delivery is required

    public $IsDeliveryConfirmation = false; //True if Adult Signature is Required

    public $IsDirectSignature = false; //True if Direct Signature is required

    public $IsCod = false; //True if you want the carrier to Collect on Delivery

    public $CodAmount = 0; // Amount that you want to collect

    public $IsSecuredCod = false; //True if your cod is secured

    public $IsRegularPickUp = false;

    public $IsDropoff = true; // Default value

    public $ReferenceNumber = ''; //35 characters reference field

    public $CustomerReferenceNumber = ''; //35 characters reference field

    public $IsBillToThirdParty = false; //Set to true if you want someone other than the shipper to pay for the shipping. Valid Account Number is Required

    public $BillToAccount = ''; //Required if isBillToThirdParty is true. Provide the 3rd party account

    public $BillToThirdPartyPostalCode = ''; //Required if isBillToThirdParty is true. Provide the Postal code of the 3rd party account

    public $IsThermal = false; //True if using a thermal printer

    public $IsSmartPickUp = false; //UPS Only - Set this value to true if you signed up for UPS SmartPickup

    public $IsPickUpRequested = false; //True if you wish to schedule a pickup. Pickup requests require the Shipper's (ShipFrom) ContactID, you need to Save or Retrieve the Contact's ID using the Location end point. We use the ContactID, to check for any existing scheduled pickup, avoiding double charges for our customers.

    public $PickUpContactName = ''; // Required if IsPickUpRequested is true

    public $PickUpTelephone = ''; // Required if IsPickUpRequested is true

    public $PickUpAtHour = ''; //Earliest pickup hour

    public $PickUpAtMinute = ''; //Earliest pickup minute

    public $PickUpByHour = ''; //Latest pickup hour

    public $PickUpByMinute = ''; //Latest pickup minute

    public $PickUpDate = ''; //Format yyyy-mm-dd

    public $DispatchLocation = ''; // FedEx Only
}
