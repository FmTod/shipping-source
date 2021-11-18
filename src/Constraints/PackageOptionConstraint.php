<?php
/**
 * Compares the value from Package::getOption($key) against the given bound.
 *
 * @author Brian Sandall
 * @copyright (c) 2015 Brian Sandall
 * @version 09/25/2015 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and
 *      there is plenty of room for improvement.  Use at your own risk.
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */

namespace FmTod\Shipping\Constraints;

class PackageOptionConstraint extends PackageValueConstraint
{
    /** Determines whether the constraint passes if the package option for $this->key does not exist */
    private $allow_null;

    /**
     * @Override
     * Same as parent constructor, but with an additional parameter to allow null values
     * @param bool $allow_null True to allow non-existant package options to pass the constraint
     */
    public function __construct($bound, $key, $operator = '<=', $allow_null = false)
    {
        parent::__construct($bound, $key, $operator);
        $this->allow_null = filter_var($allow_null, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @Override
     * If the package option does not exist, the constraint will pass if null is allowed.
     * @param $package \FmTod\Shipping\Models\Package
     */
    public function check($package, &$error = ''): bool
    {
        $value = $package->getOption($this->key);
        $error = "Package option '{$this->key}' must be {$this->operator} {$this->bound}: value = $value";

        return $value === null ? $this->allow_null : $this->compare($value, $this->bound);
    }
}
