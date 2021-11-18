<?php
/**
 * Constraint that checks one or more child constraints, passing if any one of them succeeds.
 *
 * @author Brian Sandall
 * @copyright (c) 2016 Brian Sandall
 * @version 06/28/2016 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and
 *      there is plenty of room for improvement.  Use at your own risk.
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */

namespace FmTod\Shipping\Constraints;

use FmTod\Shipping\Contracts\Constraint;

class FlexibleConstraint implements Constraint
{
    protected $children;

    protected $enabled = true;

    /**
     * @param array $children Array of IConstraints to be checked
     */
    public function __construct(array $children)
    {
        if (empty($children)) {
            throw new \InvalidArgumentException('FlexibleConstraint requires at least one child constraint');
        }
        foreach ($children as $child) {
            if (! ($child instanceof Constraint)) {
                throw new \InvalidArgumentException('All array elements must be of type IConstraint; received '.gettype($child));
            }
        }
        $this->children = $children;
    }

    /**
     * @Override
     */
    public function check($package, &$error = ''): bool
    {
        foreach ($this->children as $child) {
            if ($child->check($package, $error)) {
                return true;
            }
        }

        return false;
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
