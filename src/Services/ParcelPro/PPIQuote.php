<?php

namespace FmTod\Shipping\Services\ParcelPro;

/**
 * Class PPIQuote.
 *
 * @property \FmTod\Shipping\Services\ParcelPro\PPIContact|null ShipTo
 * @property \FmTod\Shipping\Services\ParcelPro\PPIContact|null ShipFrom
 * @property string ShipDate
 * @property int Length
 * @property int Width
 * @property int Height
 * @property int Weight
 * @property int InsuredValue
 * @property string ServiceCode
 * @property string CarrierCode
 * @property bool IsDeliveryConfirmation
 * @property bool IsSaturdayDelivery
 */
class PPIQuote extends PPIObject
{
    protected array $data = [
        'ShipmentId' => 'NOID',
        'QuoteId' => '',
        'CustomerId' => 'NOID',
        'ShipToResidential' => false,
        'ServiceCode' => '02',
        'CarrierCode' => '1',
        'ShipTo' => [],
        'NotifyRecipient' => true,
        'ShipFrom' => [],
        'ShipDate' => '',
        'PackageCode' => '02',
        'Height' => 0,
        'Width' => 0,
        'Length' => 0,
        'Weight' => 0.0,
        'InsuredValue' => 0,
        'IsSaturdayDelivery' => false,
        'IsDeliveryConfirmation' => true,
        'IsPickUpRequested' => false,
        'IsSmartPickUp' => false,
        'PickUpContactName' => '',
        'PickUpTelephone' => '',
        'PickUpAtHour' => '',
        'PickUpAtMinute' => '',
        'PickUpByHour' => '',
        'PickUpByMinute' => '',
        'PickUpDate' => '',
        'DispatchConfirmationNumber' => '',
        'DispatchLocation' => '',
        'NotifySender' => false,
        'ReferenceNumber' => '',
        'TrackingNumber' => '',
        'CustomerReferenceNumber' => '',
        'IsDirectSignature' => false,
        'IsThermal' => true,
        'IsMaxCoverageExceeded' => false,
        'Estimator' => [],
        'LabelImage' => null,
        'IsBillToThirdParty' => false,
        'BillToThirdPartyPostalCode' => '',
        'BillToAccount' => '',
        'IsShipFromRestrictedZip' => false,
        'IsShipToRestrictedZip' => false,
        'IsShipToHasRestrictedWords' => false,
        'IsShipFromHasRestrictedWords' => false,
        'IsHighValueShipment' => false,
        'IsHighValueReport' => false,
        'ReceivedBy' => '',
        'ReceivedTime' => '',
        'TotalShipments' => '0',

        'UserId' => 0,
        'IsSaturdayPickUp' => false,
        'IsCod' => false,
        'CodAmount' => 0.0,
        'IsSecuredCod' => false,
        'IsRegularPickUp' => false,
        'IsDropoff' => true,
        'UpdateAddressBook' => false,
    ];

    /**
     * Initiate a new instance of the object.
     *
     * @param array|null $data
     */
    public function __construct(array $data = null)
    {
        parent::__construct($data);

        if (empty($this->ShipDate)) {
            $this->ShipDate = now()->format('Y-m-d');
        }
    }

    /**
     * Return array representation of the object.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'ShipFrom' => $this->ShipFrom?->toArray(),
            'ShipTo' => $this->ShipTo?->toArray(),
        ]);
    }
}
