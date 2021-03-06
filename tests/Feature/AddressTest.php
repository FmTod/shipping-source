<?php

use FmTod\Shipping\Models\Address;
use Illuminate\Support\Str;
use function Pest\Faker\faker;

beforeEach(function () {
    $this->address = new Address([
        'first_name' => faker()->firstName,
        'last_name' => faker()->lastName,
        'company_name' => faker()->company,
        'phone_number' => faker()->phoneNumber,
        'email' => faker()->freeEmail,
        'street_address1' => faker()->streetAddress,
        'city' => faker()->city,
        'state' => faker()->stateAbbr,
        'postal_code' => faker()->postcode,
        'country_code' => faker()->countryCode,
        'is_residential' => true,
    ]);
});

it('validates the attributes', function () {
    $this->assertTrue($this->address->validate());

    unset($this->address->street_address1);
    $this->assertFalse($this->address->validate());

    $this->address->street_address1 = faker()->streetAddress;
    $this->assertTrue($this->address->validate());

    unset($this->address->company_name);
    $this->assertTrue($this->address->validate());

    unset($this->address->first_name, $this->address->last_name);
    $this->assertFalse($this->address->validate());
});

it('parses the full name from first and last', function () {
    expect($this->address->full_name)->toEqual("{$this->address->first_name} {$this->address->last_name}");
});

it('parses first and last name from full name', function () {
    $fullName = faker()->name;
    $this->address->full_name = $fullName;
    expect($this->address->first_name)->toEqual(Str::before($this->address->full_name, ' '));
    expect($this->address->last_name)->toEqual(Str::after($this->address->full_name, ' '));
});

it('format country codes', function () {
    expect(Address::formatCountryCode('USA'))->toEqual('US');
    expect(Address::formatCountryCode('US', 3))->toEqual('USA');
});
