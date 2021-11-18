<?php
// config for FmTod/Shipping
return [
    'packer' => FmTod\Shipping\Packers\DefaultPacker::class,

    'strategy' => FmTod\Shipping\Strategies\DefaultMergeStrategy::class,
];
