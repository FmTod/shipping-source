<?php

use FmTod\Shipping\Models\Address;
use function Pest\Faker\faker;

test('Validation', function () {
    $locale = 'en_US';

    $address = new Address([
        'first_name' => faker($locale)->firstName,
        'last_name' => faker($locale)->lastName,
        'company' => faker($locale)->company,
        'phone' => faker($locale)->phoneNumber,
        'email' => faker($locale)->freeEmail,
        'address1' => faker($locale)->streetAddress,
        'city' => faker($locale)->city,
        'state' => faker($locale)->state,
        'postal_code' => faker($locale)->postcode,
        'country_code' => faker($locale)->countryCode,
    ]);
    $this->assertTrue($address->validate());

    unset($address->address1);
    $this->assertFalse($address->validate());

    $address->address1 = faker($locale)->streetAddress;
    $this->assertTrue($address->validate());

    unset($address->company);
    $this->assertTrue($address->validate());

    unset($address->first_name, $address->last_name);
    $this->assertFalse($address->validate());
});
