<?php

namespace FmTod\Shipping\Services\ParcelPro;

use FmTod\Shipping\Services\ParcelPro\Enums\ContactType;

class PPIContact extends PPIObject
{
    protected array $data = [
        'ContactId' => 'NOID',
        'ContactType' => ContactType::Location,
        'CompanyName' => '',
        'FirstName' => '',
        'LastName' => '',
        'StreetAddress' => '',
        'ApartmentSuite' => '',
        'City' => '',
        'State' => '',
        'Country' => '',
        'Zip' => '',
        'TelephoneNo' => '',
        'Email' => '',

        'ContactID' => 'NOID',
        'CustomerId' => '',
        'UserId' => 0,
        'ProvinceRegion' => '',
        'FaxNo' => '',
        'NickName' => 'Other',
        'IsExpress' => false,
        'IsResidential' => false,
        'IsUserDefault' => false,
        'UPSPickUpType' => 0,
        'TotalContacts' => '0',
    ];
}
