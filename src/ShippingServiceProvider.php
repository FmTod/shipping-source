<?php

namespace FmTod\Shipping;

use FmTod\Shipping\Commands\ShippingCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ShippingServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('shipping')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_shipping_table')
            ->hasCommand(ShippingCommand::class);
    }
}
