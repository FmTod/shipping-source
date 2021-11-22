<?php

namespace FmTod\Shipping\Providers\ParcelPro;

/**
 * Class PPIEstimatorRequest
 *
 * @property \FmTod\Shipping\Providers\ParcelPro\PPIContact $ShipTo
 * @property \FmTod\Shipping\Providers\ParcelPro\PPIContact $ShipFrom
 * @property bool $ShipToResidential
 * @property int $Weight
 * @property int $Height
 * @property int $Width
 * @property int $Length
 * @property float $InsuredValue
 * @property bool $IsSaturdayDelivery
 * @property bool $IsDeliveryConfirmation
 */
class PPIEstimatorRequest extends PPIObject
{
    protected array $data = [
        'ShipTo' => [],
        'ShipFrom' => [],
        'ShipToResidential' => false,
        'Weight' => 1,
        'Height' => 0,
        'Width' => 0,
        'Length' => 0,
        'InsuredValue' => 1,
        'IsSaturdayDelivery' => false,
        'IsDeliveryConfirmation' => false,

        'UserId' => 0,
        'IsSaturdayPickUp' => false,
        'IsCod' => false,
        'CodAmount' => 0.0,
        'IsSecuredCod' => false,
        'IsRegularPickUp' => false,
        'IsDropoff' => true,
        'UpdateAddressBook' => false,
    ];
}
