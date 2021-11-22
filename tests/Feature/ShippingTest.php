<?php

use FmTod\Shipping\Models\Address;
use FmTod\Shipping\Models\Carrier;
use FmTod\Shipping\Models\Package;
use FmTod\Shipping\Models\Rate;
use FmTod\Shipping\Models\Service;
use FmTod\Shipping\Models\Shipment;
use FmTod\Shipping\Providers\ParcelPro;
use FmTod\Shipping\Shipping;
use FmTod\Shipping\Tests\stubs\ShipmentStub;
use FmTod\Shipping\Providers\Shippo;
use Illuminate\Support\Collection;
use function Pest\Faker\faker;

beforeEach(function () {
    $shipment = new ShipmentStub(
        to: new Address([
            'first_name' => 'Fulanito',
            'last_name' => 'Perez',
            'phone' => '7862691385',
            'email' => faker()->freeEmail,
            'address1' => '169 E Flagler St',
            'city' => 'Miami',
            'state' => 'FL',
            'postal_code' => '33131',
            'country_code' => 'US',
            'is_residential' => false,
        ], true),
        from: new Address([
            'first_name' => 'Fulanito',
            'last_name' => 'Perez',
            'phone' => '7862691385',
            'email' => faker()->freeEmail,
            'address1' => '169 E Flagler St',
            'city' => 'Miami',
            'state' => 'FL',
            'postal_code' => '33131',
            'country_code' => 'US',
            'is_residential' => false,
        ], true),
        packages: [new Package(10, [13, 10, 3])]
    );

    $this->service = new Shipping([
        ParcelPro::NAME => [
            "client_key" => env('PARCELPRO_KEY'),
            "client_secret" => env('PARCELPRO_SECRET'),
        ],
        Shippo::NAME => [
            "access_token" => env('SHIPPO_TOKEN'),
        ]
    ], $shipment);
});

it('can retrieve carriers', function () {
    $carriers = $this->service->carriers();
    expect($carriers)->toBeInstanceOf(Collection::class);
    expect($carriers->first())->toBeInstanceOf(Carrier::class);
});

it('can retrieve services', function () {
    $carriers = $this->service->services();
    expect($carriers)->toBeInstanceOf(Collection::class);
    expect($carriers->first())->toBeInstanceOf(Service::class);
});

it('can retrieve rates', function () {
    $carriers = $this->service->rates();
    expect($carriers)->toBeInstanceOf(Collection::class);
    expect($carriers->first())->toBeInstanceOf(Rate::class);
});

it('can create a shipment', function () {
    $rate = $this->service->rates()->where('provider.name', Shippo::NAME)->first();
    expect($rate)->toBeInstanceOf(Rate::class);

    $shipment = $this->service->shipment($rate);
    expect($shipment)->toBeInstanceOf(Shipment::class);
});
