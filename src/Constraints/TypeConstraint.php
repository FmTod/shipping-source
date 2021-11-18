<?php
/**
 * Constraint that checks if a value is an instance of the designated class.
 * If using PHP 7 or higher, anonymous classes may be a viable alternative to classes such as this.
 *
 * @author Brian Sandall
 * @copyright (c) 2016 Brian Sandall
 * @version 06/16/2016 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and
 *      there is plenty of room for improvement.  Use at your own risk.
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */

namespace FmTod\Shipping\Constraints;

use FmTod\Shipping\Contracts\Constraint;

class TypeConstraint implements Constraint
{
    protected $type;

    protected $enabled = true;

    /**
     * @param string $type Fully qualified class name; the value must be an instance of this class
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * @Override
     */
    public function check($value, &$error = ''): bool
    {
        $real_type = (is_object($value) ? get_class($value) : gettype($value));
        $error = "Expected type {$this->type}, received $real_type";

        return $value instanceof $this->type;
    }

    /**
     * @Override
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @Override
     */
    public function setStatus($is_enabled)
    {
        $this->enabled = (bool) $is_enabled;
    }
}
