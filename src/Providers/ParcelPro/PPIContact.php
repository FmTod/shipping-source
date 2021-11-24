<?php

namespace FmTod\Shipping\Providers\ParcelPro;

use FmTod\Shipping\Providers\ParcelPro\Enums\ContactType;

class PPIContact extends PPIObject
{
    protected array $data = [
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
        'NickName' => '',
        'IsExpress' => false,
        'IsResidential' => false,
        'IsUserDefault' => false,
        'UPSPickUpType' => 0,
        'TotalContacts' => '0',
    ];
}
