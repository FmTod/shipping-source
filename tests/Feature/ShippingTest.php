<?php

use FmTod\Shipping\Models\Address;
use FmTod\Shipping\Models\Carrier;
use FmTod\Shipping\Models\Package;
use FmTod\Shipping\Models\Rate;
use FmTod\Shipping\Models\Service;
use FmTod\Shipping\Models\Shipment;
use FmTod\Shipping\Providers\ParcelPro;
use FmTod\Shipping\Providers\Shippo;
use FmTod\Shipping\Shipping;
use Illuminate\Support\Collection;
use function Pest\Faker\faker;

beforeEach(function () {
    $this->service = new Shipping([
        ParcelPro::NAME => [
            "client_key" => env('PARCELPRO_KEY'),
            "client_secret" => env('PARCELPRO_SECRET'),
        ],
        Shippo::NAME => [
            "access_token" => env('SHIPPO_TOKEN'),
        ],
    ], [
        'consignor' => new Address([
            'first_name' => 'Fulanito',
            'last_name' => 'Perez',
            'phone_number' => '7862691385',
            'email' => faker()->freeEmail,
            'street_address1' => '169 E Flagler St',
            'city' => 'Miami',
            'state' => 'FL',
            'postal_code' => '33131',
            'country_code' => 'US',
            'is_residential' => false,
        ], true),
        'consignee' => new Address([
            'first_name' => 'Fulanito',
            'last_name' => 'Perez',
            'phone_number' => '7862691385',
            'email' => faker()->freeEmail,
            'street_address1' => '169 E Flagler St',
            'city' => 'Miami',
            'state' => 'FL',
            'postal_code' => '33131',
            'country_code' => 'US',
            'is_residential' => false,
        ], true),
        'package' => new Package(10, [13, 10, 3]),
    ]);
});

it('can retrieve carriers', function () {
    sleep(10);
    $carriers = $this->service->carriers();
    expect($carriers)->toBeInstanceOf(Collection::class);
    expect($carriers->first())->toBeInstanceOf(Carrier::class);
});

it('can retrieve services', function () {
    $carriers = $this->service->services();
    expect($carriers)->toBeInstanceOf(Collection::class);
    expect($carriers->first())->toBeInstanceOf(Service::class);
});

it('can retrieve domestic rates', function () {
    sleep(10);
    $carriers = $this->service->rates();
    expect($carriers)->toBeInstanceOf(Collection::class);
    expect($carriers->first())->toBeInstanceOf(Rate::class);
});

it('can create a domestic shipment', function () {
    sleep(10);
    $rate = $this->service->rates()->where('provider.name', Shippo::NAME)->first();
    expect($rate)->toBeInstanceOf(Rate::class);

    $shipment = $this->service->shipment($rate);
    expect($shipment)->toBeInstanceOf(Shipment::class);
});
