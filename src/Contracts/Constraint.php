<?php
/**
 * Generic interface to validate a value against a specific requirement, i.e. a constraint.
 *
 * @author Brian Sandall
 * @copyright (c) 2016 Brian Sandall
 * @version 06/16/2016 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and
 *      there is plenty of room for improvement.  Use at your own risk.
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */

namespace FmTod\Shipping\Contracts;

interface Constraint
{
    /**
     * Check whether the given value meets the constraint requirements.
     * @param mixed $value May be of any type, depending on the constraint
     * @param mixed $error When provided, it typically stores a string describing the reason for failure, if any
     * @return bool true if the value passes the constraint, or false if it fails
     */
    public function check($value, &$error = ''): bool;

    /**
     * @return bool True if this constraint is currently enabled
     */
    public function isEnabled(): bool;

    /**
     * Enable or disable the constraint.
     * @param bool $is_enabled True to enable, false to disable
     */
    public function setStatus($is_enabled);
}
