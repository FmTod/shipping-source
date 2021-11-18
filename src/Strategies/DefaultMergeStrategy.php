<?php

namespace FmTod\Shipping\Strategies;

use FmTod\Shipping\Contracts\MergeStrategy;
use FmTod\Shipping\Models\Package;

class DefaultMergeStrategy implements MergeStrategy
{
    private $callback;

    /**
     * @param callable $callback Optional function that will be called during #merge using the same parameters.
     *                           Return false from this function to prevent the merge.
     */
    public function __construct(callable $callback = null)
    {
        $this->callback = $callback;
    }

    /**
     * @Override
     * @param \FmTod\Shipping\Models\Package $packageA
     * @param \FmTod\Shipping\Models\Package $packageB
     * @param string $error
     * @return \FmTod\Shipping\Models\Package
     *
     * @throws \Throwable
     */
    public function merge(Package $packageA, Package $packageB, string &$error = ''): Package
    {
        $l = max($packageA->get('length'), $packageB->get('length'));
        $w = max($packageA->get('width'), $packageB->get('width'));
        $h = $packageA->get('height') + $packageB->get('height');
        $weight = $packageA->get('weight') + $packageB->get('weight');
        $combined = new Package($weight, [$l, $w, $h], $packageA->get('options'));

        // Don't forget to merge the package options into the combined package
        throw_if(! $combined->mergeOptions($packageB, $error), 'Options could not be merged.');

        if ($this->callback !== null) {
            call_user_func_array($this->callback, [$packageA, $packageB, &$error]);
        }

        return $combined;
    }
}
