<?php

namespace FmTod\Shipping\Contracts;

use FmTod\Shipping\Models\Carrier;
use FmTod\Shipping\Models\Rate;
use FmTod\Shipping\Models\Service;
use FmTod\Shipping\Models\Shipment;
use Illuminate\Support\Collection;

interface ShippingService
{
    public function getCarriers(): Collection;

    public function getServices(): Collection;

    public function setShippable(Shippable $shipment): static;

    public function getShippable(): ?Shippable;

    public function setConfig(array $config): static;

    public function getRates(array $options = []): Collection;

    public function getRate(Carrier|string $carrier, Service|string $service, array $parameters = []): Rate;

    public function createShipment(Rate $rate): Shipment;
}
