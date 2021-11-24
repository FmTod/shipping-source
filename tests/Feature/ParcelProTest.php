<?php

use FmTod\Money\Money;
use FmTod\Shipping\Models\Address;
use FmTod\Shipping\Models\Carrier;
use FmTod\Shipping\Models\Duration;
use FmTod\Shipping\Models\Package;
use FmTod\Shipping\Models\Provider;
use FmTod\Shipping\Models\Rate;
use FmTod\Shipping\Models\Service;
use FmTod\Shipping\Models\Shipment;
use FmTod\Shipping\Providers\ParcelPro;
use Illuminate\Support\Collection;
use function Pest\Faker\faker;

beforeEach(fn () => $this->service = new ParcelPro([
    "client_key" => env('PARCELPRO_KEY'),
    "client_secret" => env('PARCELPRO_SECRET'),
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
]));

it('can be constructed')
    ->expect(fn () => $this->service)
    ->toBeInstanceOf(ParcelPro::class);

it('can be called statically')
    ->expect(fn () => ParcelPro::config([
            "client_key" => env('PARCELPRO_KEY'),
            "client_secret" => env('PARCELPRO_SECRET'),
        ])
        ->setConsignor(new Address([
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
        ], true))
        ->setConsignee(new Address([
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
        ], true))
        ->setPackage(new Package(10, [13, 10, 3])))
    ->toBeInstanceOf(ParcelPro::class);

it('can retrieve carriers', function () {
    $carriers = $this->service->getCarriers();

    expect($carriers)->toBeInstanceOf(Collection::class);
    expect($carriers->first())->toBeInstanceOf(Carrier::class);
});

it('can retrieve services', function () {
    $services = $this->service->getServices();

    expect($services)->toBeInstanceOf(Collection::class);
    expect($services->first())->toBeInstanceOf(Service::class);
});

it('can retrieve domestic rates', function () {
    $rates = $this->service->getRates();
    expect($rates)->toBeInstanceOf(Collection::class);

    $rate = $rates->first();
    expect($rate)->toBeInstanceOf(Rate::class);
    expect($rate->provider)->toBeInstanceOf(Provider::class);
    expect($rate->carrier)->toBeInstanceOf(Carrier::class);
    expect($rate->service)->toBeInstanceOf(Service::class);
    expect($rate->duration)->toBeInstanceOf(Duration::class);
    expect($rate->duration->days)->toBeInt();
    expect($rate->duration->terms)->toBeString();
    expect($rate->amount)->toBeInstanceOf(Money::class);
});

it('can retrieve domestic rate for a specific carrier and service', function () {
    $carrier = $this->service->getCarriers()->first();
    expect($carrier)->toBeInstanceOf(Carrier::class);

    $services = $this->service->getServices();
    expect($services)->toBeInstanceOf(Collection::class);

    $service = $services->where('carrier', $carrier->value)->first();
    expect($service)->toBeInstanceOf(Service::class);

    $rate = $this->service->getRate($carrier, $service);
    expect($rate->provider)->toBeInstanceOf(Provider::class);
    expect($rate->carrier)->toBeInstanceOf(Carrier::class);
    expect($rate->service)->toBeInstanceOf(Service::class);
    expect($rate->duration)->toBeInstanceOf(Duration::class);
    expect($rate->duration->days)->toBeInt();
    expect($rate->duration->terms)->toBeString();
    expect($rate->amount)->toBeInstanceOf(Money::class);
});

it('can create a domestic shipment', function () {
    $carrier = $this->service->getCarriers()->first();
    $services = $this->service->getServices();
    $service = $services->where('carrier', $carrier->value)->first();
    $rate = $this->service->getRate($carrier, $service);
    $shipment = $this->service->createShipment($rate);

    expect($shipment)->toBeInstanceOf(Shipment::class);
})->skip();
