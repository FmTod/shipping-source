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
 * @property bool ReferenceNumber
 * @property bool CustomerReferenceNumber
 */
class PPIQuote extends PPIObject
{
    protected array $data = [
        'ServiceCode'                   => "",
        'CarrierCode'                   => "",
        'PackageCode'                   => "02",
        'ShipDate'                      => "",
        'ReferenceNumber'               => "",
        'CustomerReferenceNumber'       => "",
        'ShipTo'                        => [],
        'ShipFrom'                      => [],
        'ShipToResidential'             => false,
        'IsSaturdayDelivery'            => false,
        'IsDeliveryConfirmation'        => false,
        'Weight'                        => 1,
        'Height'                        => 0,
        'Width'                         => 0,
        'Length'                        => 0,
        'InsuredValue'                  => 1,

        //<editor-fold desc="Misc" defaultstate="collapsed">
        'UserId'                        => 0,
        'IsSaturdayPickUp'              => false,
        'IsCod'                         => false,
        'CodAmount'                     => 0.0,
        'IsSecuredCod'                  => false,
        'IsRegularPickUp'               => false,
        'IsDropoff'                     => true,
        'UpdateAddressBook'             => false,
        'InsuredValueThreshold'         => 0,
        'InsuredValueMultiplier'        => 0,
        'NotifyRecipient'               => true,
        'QuoteId'                       => '',
        'ShipmentId'                    => '',
        'CustomerId'                    => '',
        'IsPickUpRequested'             => false,
        'IsSmartPickUp'                 => false,
        'PickUpContactName'             => '',
        'PickUpTelephone'               => '',
        'PickUpAtHour'                  => '',
        'PickUpAtMinute'                => '',
        'PickUpByHour'                  => '',
        'PickUpByMinute'                => '',
        'PickUpDate'                    => '',
        'DispatchConfirmationNumber'    => '',
        'DispatchLocation'              => '',
        'NotifySender'                  => false,
        'TrackingNumber'                => '',
        'IsDirectSignature'             => false,
        'IsThermal'                     => true,
        'IsMaxCoverageExceeded'         => false,
        'Estimator'                     => [],
        'LabelImage'                    => null,
        'IsBillToThirdParty'            => false,
        'BillToThirdPartyPostalCode'    => '',
        'BillToAccount'                 => '',
        'IsShipFromRestrictedZip'       => false,
        'IsShipToRestrictedZip'         => false,
        'IsShipToHasRestrictedWords'    => false,
        'IsShipFromHasRestrictedWords'  => false,
        'IsHighValueShipment'           => false,
        'IsHighValueReport'             => false,
        'ReceivedBy'                    => '',
        'ReceivedTime'                  => '',
        'TotalShipments'                => '0',
        //</editor-fold>
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
