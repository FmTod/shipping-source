<?php

namespace FmTod\Shipping\Contracts;

use FmTod\Shipping\Models\Package;

interface MergeStrategy
{
    /**
     * Combine two Package packages into a single package.
     *
     * @param Package $packageA
     * @param Package $packageB
     * @param string $error Message describing reason for failure, if any
     * @return Package The combined Package on success, or false if they could not be combined
     */
    public function merge(Package $packageA, Package $packageB, string &$error = ''): Package;
}
