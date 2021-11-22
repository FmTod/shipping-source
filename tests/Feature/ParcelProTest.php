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
use FmTod\Shipping\Tests\stubs\ShipmentStub;
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

    $this->service = new ParcelPro([
        "client_key" => "645157API",
        "client_secret" => "Credentials645157",
    ], $shipment);
});

test('Constructor', function () {
    expect($this->service)->toBeInstanceOf(ParcelPro::class);
});

test('Carriers', function () {
    $carriers = $this->service->getCarriers();

    expect($carriers)->toBeInstanceOf(Collection::class);
    expect($carriers->first())->toBeInstanceOf(Carrier::class);
});

test('Services', function () {
    $services = $this->service->getServices();

    expect($services)->toBeInstanceOf(Collection::class);
    expect($services->first())->toBeInstanceOf(Service::class);
});

test('Estimator', function () {
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

test('Rates', function () {
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

test('Label', function () {
    $carrier = $this->service->getCarriers()->first();
    $services = $this->service->getServices();
    $service = $services->where('carrier', $carrier->value)->first();
    $rate = $this->service->getRate($carrier, $service);
    $shipment = $this->service->createShipment($rate);

    expect($shipment)->toBeInstanceOf(Shipment::class);
})->skip();
