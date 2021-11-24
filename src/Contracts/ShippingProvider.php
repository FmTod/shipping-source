<?php

namespace FmTod\Shipping\Contracts;

use FmTod\Shipping\Models\Carrier;
use FmTod\Shipping\Models\Rate;
use FmTod\Shipping\Models\Service;
use FmTod\Shipping\Models\Shipment;
use Illuminate\Support\Collection;

interface ShippingProvider
{
    public function __construct(array $config, ?array $parameters = null);

    public function getCarriers(): Collection;

    public function getServices(): Collection;

    public function setConsignor(ShippableAddress $shipment): static;

    public function setConsignee(ShippableAddress $shipment): static;

    public function setPackage(ShippablePackage $shipment): static;

    public function getConsignor(): ?ShippableAddress;

    public function getConsignee(): ?ShippableAddress;

    public function getPackage(): ?ShippablePackage;

    public function setConfig(array $config): static;

    public function getRates(array $parameters = []): Collection;

    public function getRate(Carrier|string $carrier, Service|string $service, array $parameters = []): Rate;

    public function createShipment(Rate $rate): Shipment;
}
