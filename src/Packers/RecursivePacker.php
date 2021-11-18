<?php
/**
 * This implementation first attempts to merge catalog into previous packages using
 * available IMergeStrategies, if any; remaining catalog are then packed recursively
 * in either a square or vertical stack, depending on which is more efficient.
 *
 * Each item is represented by an array.
 *
 * @author Brian Sandall
 * @copyright (c) 2016 Brian Sandall
 * @version 06/16/2016 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and
 *      there is plenty of room for improvement.  Use at your own risk.
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */

namespace FmTod\Shipping\Packers;

use FmTod\Shipping\Models\Package;

class RecursivePacker extends AbstractPacker
{
    /** CatalogItem packaged individually, used as a reference during merging and recursive packing */
    protected $single_item;

    /**
     * Override.
     * @param $item
     * @return array
     */
    protected function getPackageOptions($item)
    {
        $options = parent::getPackageOptions($item);
        // Adjust insured amount
        $insured_amount = (empty($options['insured_amount']) ? 0 : $options['insured_amount']);
        if (! empty($options['insured_amount'])) {
            $options['insured_amount'] *= $this->getQuantityFromItem($item);
        }

        return $options;
    }

    /**
     * @Override
     * @param array $item An array containing 'weight', 'length', 'width', 'height', and possibly 'quantity'
     * @param array $packages
     * @return array
     */
    protected function getPackageWorker($item, array &$packages)
    {
        if (! is_array($item)) {
            throw new \InvalidArgumentException('Expected item to be an array; received '.gettype($item));
        }
        // Extract required values from $item parameter
        $array = array_intersect_key($item, ['weight' => 0, 'length' => 0, 'width' => 0, 'height' => 0]);
        if (count($array) < 4) {
            throw new \InvalidArgumentException("CatalogItem must contain the following fields: 'length', 'width', 'height', 'weight', and usually 'quantity'");
        }
        extract($array);
        /**
         * @var mixed $length
         * @var mixed $height
         * @var mixed $width
         * @var mixed $weight
         */
        $quantity = $this->getQuantityFromItem($item);

        // Determine individual item weight
        if ($this->is_weight_combined && $quantity > 1) {
            $weight = max(0.1, ($weight / $quantity));
        }

        // Validate item dimensions and sort
        $lwh = $this->getSortedDimensions($this->getValidatedFloat($length), $this->getValidatedFloat($width), $this->getValidatedFloat($height));

        // Determine package options for single item and adjust insurance amount
        $options = $this->getPackageOptions($item);
        if ($quantity > 1 && array_key_exists('insured_amount', $options)) {
            $options['insured_amount'] /= $quantity;
        }

        // Ensure item is at least able to be packed individually
        $this->single_item = new Package($weight, $lwh, $options);
        if (! $this->checkConstraints($this->single_item, $error)) {
            throw new \InvalidArgumentException("Invalid package: $error");
        }
        // If item requires individual shipping, do not merge and skip recursive packing
        if (! empty($options['ships_individually'])) {
            return array_fill(0, $quantity, $this->single_item);
        }
        // Toggle optional constraints based on item packed singly
        $this->updateOptionalConstraints($this->single_item);
        if (! empty($this->merge_strategies)) {
            $quantity = $this->merge($packages, $this->single_item, $quantity);
        }

        // Pack remaining quantity recursively for a fairly accurate estimate
        if ($quantity > 1) {
            // Update item with converted unit values, remaining quantity, etc.
            $item = array_merge($item, $lwh, ['weight'=>$weight, 'quantity'=>$quantity, 'options'=>$options]);
            // Reset optional constraint status to that of single item after attempted merging
            $this->updateOptionalConstraints($this->single_item);

            return $this->recursivePackageWorker([$item]);
        }
        // Remaining quantity is either 0 or 1; return array filled with that many packages
        return $quantity > 0 ? [$this->single_item] : [];
    }

    /**
     * Recursively packs catalog into packages, distributing quantity as evenly as possible.
     * @param array $items
     * @param array $packages
     * @return array
     */
    protected function recursivePackageWorker(array $items, array $packages = [])
    {
        // Break catalog up into suitable packages based on max weight and max size
        foreach ($items as $item) {
            // Current item characteristics
            $quantity = $this->getQuantityFromItem($item);

            // 'Square' stacking (i.e. stack on both width and height)
            $width_modifier = (int) max(1, ceil(sqrt($quantity))); // will give approximate size (either exact or over-estimated)
            $height_modifier = $width_modifier;

            // Adjust modifiers to prevent gross overestimation
            if (($height_modifier * $width_modifier) > $quantity) {
                $height_modifier--;
                if (($height_modifier * $width_modifier) < $quantity) {
                    $width_modifier++;
                }
            }
            $weight = $this->single_item->get('weight') * $quantity;
            $width = $width_modifier * $this->single_item->get('width');
            $height = $height_modifier * $this->single_item->get('height');

            // Re-sort dimensions, in case width or height now exceeds length
            $lwh = $this->getSortedDimensions($this->single_item->get('length'), $width, $height);
            extract($lwh);
            $total_size = $length + (2 * ($width + $height));

            // Vertical stacking comparison (height should be the smallest dimension)
            $lwh = $this->getSortedDimensions($this->single_item->get('length'), $this->single_item->get('width'), ($this->single_item->get('height') * $quantity));
            $vertical_size = $lwh['length'] + (2 * ($lwh['width'] + $lwh['height']));
            if ($vertical_size < $total_size) {
                extract($lwh); // vertical stacking is more efficient, overwrite existing dimensions
                $total_size = $vertical_size;
            }

            // Must meet all required constraints to pack singly, and optional constraints to pack with other catalog
            $package = new Package($weight, [$length, $width, $height], $this->getPackageOptions($item));
            if ($this->checkConstraints($package, $error) && ($quantity === 1 || $this->checkOptionalConstraints($package, $error))) {
                // try to merge new package into previous packages; otherwise add it
                if (empty($packages) || $this->merge($packages, $package, 1) > 0) {
                    $packages[] = $package;
                }
            } elseif ($quantity === 1) { // couldn't be packed even as a single item
                throw new \InvalidArgumentException("Invalid package: $error");
            } else { // recursively split catalog into separate packages
                $packages = $this->recursivePackageWorker($this->splitItem($item), $packages);
            }
        }

        return $packages;
    }
}
