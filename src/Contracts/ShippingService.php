<?php

namespace FmTod\Shipping\Contracts;

use FmTod\Shipping\Models\Carrier;
use FmTod\Shipping\Models\LabelResponse;
use FmTod\Shipping\Models\Rate;
use FmTod\Shipping\Models\Service;

interface ShippingService
{
    public function getCarriers();

    public function getServices();

    public function setShipment(Shippable $shipment);

    public function setConfig(array $config);

    public function getRates(array $options = []): array;

    public function getRate(Carrier|string $carrier, Service|string $service, array $parameters = []): Rate;

    public function createLabel(Rate $rate): LabelResponse;
}
