<?php

use FmTod\Shipping\Models\Address;
use FmTod\Shipping\Models\Carrier;
use FmTod\Shipping\Models\Package;
use FmTod\Shipping\Models\Service;
use FmTod\Shipping\Models\Shipment;
use FmTod\Shipping\Services\ParcelPro;
use Illuminate\Support\Collection;
use function Pest\Faker\faker;

test('Constructor', function () {
    $shipment = new Shipment(
        to: new Address([
            'name' => 'Fulanito Perez',
            'company' => 'Fulano',
            'phone' => '1237894565',
            'email' => 'example@example.com',
            'address1' => '169 E Flagler St',
            'address2' => 'Ste 1041',
            'city' => 'Miami',
            'state' => 'FL',
            'postal_code' => '33131',
            'country_code' => 'US',
            'is_residential' => false,
        ]),
        from: new Address([
            'name' => 'Fulanito Perez',
            'company' => 'Fulano',
            'phone' => '1237894565',
            'email' => 'example@example.com',
            'address1' => '169 E Flagler St',
            'address2' => 'Ste 1041',
            'city' => 'Miami',
            'state' => 'FL',
            'postal_code' => '33131',
            'country_code' => 'US',
            'is_residential' => false,
        ]),
        packages: [
            new Package(10, [13, 10, 3]),
        ]
    );

    $service = new ParcelPro([
        "client_key" => "645157API",
        "client_secret" => "Credentials645157",
    ], $shipment);

    $this->assertIsObject($service);
});

test('Carriers', function () {
    $shipment = new Shipment(
        to: new Address([
            'name' => 'Fulanito Perez',
            'company' => 'Fulano',
            'phone' => '1237894565',
            'email' => 'example@example.com',
            'address1' => '169 E Flagler St',
            'address2' => 'Ste 1041',
            'city' => 'Miami',
            'state' => 'FL',
            'postal_code' => '33131',
            'country_code' => 'US',
            'is_residential' => false,
        ]),
        from: new Address([
            'name' => 'Fulanito Perez',
            'company' => 'Fulano',
            'phone' => '1237894565',
            'email' => 'example@example.com',
            'address1' => '169 E Flagler St',
            'address2' => 'Ste 1041',
            'city' => 'Miami',
            'state' => 'FL',
            'postal_code' => '33131',
            'country_code' => 'US',
            'is_residential' => false,
        ]),
        packages: [
            new Package(10, [13, 10, 3]),
        ]
    );

    $service = new ParcelPro([
        "client_key" => "645157API",
        "client_secret" => "Credentials645157",
    ], $shipment);

    $carriers = $service->getCarriers();

    $this->assertIsArray($carriers);
    $this->assertIsObject($carriers[0]);
    $this->assertInstanceOf(Carrier::class, $carriers[0]);
});

test('Services', function () {
    $shipment = new Shipment(
        to: new Address([
            'name' => 'Fulanito Perez',
            'company' => 'Fulano',
            'phone' => '1237894565',
            'email' => 'example@example.com',
            'address1' => '169 E Flagler St',
            'address2' => 'Ste 1041',
            'city' => 'Miami',
            'state' => 'FL',
            'postal_code' => '33131',
            'country_code' => 'US',
            'is_residential' => false,
        ]),
        from: new Address([
            'name' => 'Fulanito Perez',
            'company' => 'Fulano',
            'phone' => '1237894565',
            'email' => 'example@example.com',
            'address1' => '169 E Flagler St',
            'address2' => 'Ste 1041',
            'city' => 'Miami',
            'state' => 'FL',
            'postal_code' => '33131',
            'country_code' => 'US',
            'is_residential' => false,
        ]),
        packages: [
            new Package(10, [13, 10, 3]),
        ]
    );

    $service = new ParcelPro([
        "client_key" => "645157API",
        "client_secret" => "Credentials645157",
    ], $shipment);

    $services = $service->getServices();

    $this->assertIsArray($services);
    $this->assertIsObject($services[0]);
    $this->assertInstanceOf(Service::class, $services[0]);
});

test('Rates', function () {
    $shipment = new Shipment(
        to: new Address([
            'name' => 'Fulanito Perez',
            'company' => 'Fulano',
            'phone' => '1237894565',
            'email' => faker()->email,
            'address1' => '169 E Flagler St',
            'address2' => 'Ste 1041',
            'city' => 'Miami',
            'state' => 'FL',
            'postal_code' => '33131',
            'country_code' => 'US',
            'is_residential' => false,
        ], true),
        from: new Address([
            'name' => 'Fulanito Perez',
            'company' => 'Fulano',
            'phone' => '1237894565',
            'email' => faker()->email,
            'address1' => '169 E Flagler St',
            'address2' => 'Ste 1041',
            'city' => 'Miami',
            'state' => 'FL',
            'postal_code' => '33131',
            'country_code' => 'US',
            'is_residential' => false,
        ], true),
        packages: [
            new Package(10, [13, 10, 3]),
        ]
    );

    $shipper = new ParcelPro([
        "client_key" => "645157API",
        "client_secret" => "Credentials645157",
    ], $shipment);

    $carrier = $shipper->getCarriers()->first();
    $this->assertInstanceOf(Carrier::class, $carrier);

    $services = $shipper->getServices();
    $this->assertInstanceOf(Collection::class, $services);

    $service = $services->where('carrier', $carrier->value)->first();
    $this->assertInstanceOf(Service::class, $service);

    $rate = $shipper->getRate($carrier, $service);
});
