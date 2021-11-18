<?php
/**
 * Abstract packer class provides default implementation of IPacker#makePackages and requires
 * sub-classes to determine how each item is to be packaged. This allows each item to be any
 * type required by the individual software, instead of only allowing standard arrays.
 *
 * @author Brian Sandall
 * @copyright (c) 2016 Brian Sandall
 * @version 06/16/2016 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and
 *      there is plenty of room for improvement.  Use at your own risk.
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */

namespace FmTod\Shipping\Packers;

use FmTod\Shipping\Constraints\PackageHandlingConstraint;
use FmTod\Shipping\Constraints\PackageOptionConstraint;
use FmTod\Shipping\Constraints\PackageValueConstraint;
use FmTod\Shipping\Constraints\TypeConstraint;
use FmTod\Shipping\Contracts\Constraint;
use FmTod\Shipping\Contracts\MergeStrategy;
use FmTod\Shipping\Contracts\Packer;
use FmTod\Shipping\Models\Measure;
use FmTod\Shipping\Models\Package;
use FmTod\Shipping\Models\Weight;
use InvalidArgumentException;

abstract class AbstractPacker implements Packer
{
    /**
     * array of all required IConstraint, i.e. those that would cause a
     * carrier to refuse the package, such as by exceeding their max package weight limit.
     */
    protected $constraints = [];

    /**
     * array of all optional IConstraint, i.e. those that may incur
     * additional costs, but will not preclude the carrier from accepting the package.
     */
    protected $optional_constraints = [];

    /**
     * array of all IConstraint applied at the final package level, i.e.
     * constraints checked after packing is completed.
     */
    protected $post_constraints = [];

    /** array of IMergeStrategy strategies available for merging packages */
    protected $merge_strategies = [];

    /** True if catalog passed to #getPackageWorker use a combined weight (item weight * quantity) */
    protected $is_weight_combined;

    /** Maximum weight for a single package */
    protected $max_weight;

    /** Maximum value for the longest dimension */
    protected $max_length;

    /** Maximum total size - total size equals the length plus twice the combined height and width */
    protected $max_size;

    /** Unit of measurement for weights will be set during #init based on $init_options */
    protected $weight_unit;

    /** Unit of measurement for dimensions will be set during #init based on $init_options */
    protected $measure_unit;

    /**
     * Constructs a default packer with maximum allowed package weight, length, and size constraints.
     * Default values are in pounds and inches; units can be converted based on settings by overriding
     * #getWeightValue and #getMeasurementValue and providing appropriate $init_options for your implementation.
     * @param float|int $max_weight The absolute maximum weight allowed for any one package
     * @param float|int $max_length The absolute maximum length (longest dimension) allowed
     * @param float|int $max_size   The absolute maximum total size allowed, where total size = length + (2 * width) + (2 * height)
     * @param bool $is_weight_combined True if catalog passed to #getPackageWorker use a combined weight (item weight * quantity)
     * @param array $init_options   This parameter is passed to the #init method
     * @throws InvalidArgumentException if any argument fails to validate
     */
    public function __construct($max_weight = 150, $max_length = 108, $max_size = 165, $is_weight_combined = false, $init_options = [])
    {
        // Allow sub-classes to do any necessary pre-initializations before value conversions
        $this->init($init_options);
        $this->max_weight = $this->getWeightValue($max_weight);
        $this->max_length = $this->getMeasurementValue($max_length);
        $this->max_size = $this->getMeasurementValue($max_size);
        $this->is_weight_combined = filter_var($is_weight_combined, FILTER_VALIDATE_BOOLEAN);
        // Finally, add the default required constraints
        $this->addDefaultConstraints();
    }

    /**
     * Called from the constructor before any assignments are made to allow further initialization of class objects.
     * Default implementation sets the class $weight_unit and $measure_unit based on #getWeightUnit and #getMeasureUnit.
     * @param array $init_options Contents vary based on the constructor, but may look like:
     *                          array('currency_unit'=>'USD', 'dimension_unit'=>'in', 'weight_unit'=>'lb')
     */
    protected function init(array $init_options)
    {
        $this->weight_unit = $this->getWeightUnit($init_options);
        $this->measure_unit = $this->getMeasureUnit($init_options);
    }

    /**
     * Called at the end of the class constructor to add initial constraints. The default
     * implementation adds the minimum constraints required to ensure a deliverable package:
     *  - package type, to ensure subsequent constraints receive a Package object when checked
     *  - max weight, length, and size constraints, typically representing the limits of what a carrier will accept.
     */
    protected function addDefaultConstraints()
    {
        // Package type constraint is added first, as subsequent constraints expect #check parameter to be that type
        $this->addConstraint(new TypeConstraint(\FmTod\Shipping\Models\Package::class));
        $this->addConstraint(new PackageValueConstraint($this->max_weight, 'weight', '<='), 'max_weight', true, true);
        $this->addConstraint(new PackageValueConstraint($this->max_length, 'length', '<='), 'max_length', true, true);
        $this->addConstraint(new PackageValueConstraint($this->max_size, 'size', '<='), 'max_size', true, true);
    }

    /**
     * Called during #makePackages prior to iterating the $catalog array to allow for e.g. sorting.
     * @param array $items
     */
    protected function prePackage(array &$items)
    {
    }

    /**
     * @Override Default implementation of IPacker#makePackages
     * @param array $items
     * @param array $notPacked
     * @return array
     */
    public function makePackages(array $items, array &$notPacked = []): array
    {
        $packages = [];
        $this->prePackage($items);
        foreach ($items as $item) {
            try {
                $packed = $this->getPackageWorker($item, $packages);
                if (! is_array($packed)) {
                    $notPacked[] = $item;
                } else {
                    $packages = array_merge($packages, $packed);
                }
            } catch (\Exception $e) {
                $item['error'] = $e->getMessage(); // allows error message to be displayed
                $notPacked[] = $item;
            }
        }
        // Check any post-packing Contraints
        if (! empty($this->post_constraints)) {
            foreach ($packages as $package) {
                if (! $this->doConstraintCheck($this->post_constraints, $package, $error)) {
                    throw new \UnexpectedValueException("Package failed constraint: $error");
                }
            }
        }

        return $packages;
    }

    /**
     * Convert an item into one or more Packages, provided the item contains all valid
     * information (e.g. weight, dimensions, etc.) and that it fulfills all constraints.
     * @param $item array or Object representing a single item, although that item may
     *               have a quantity greater than one
     * @param array $packages Array of Package objects already packed so that the current item
     *               may attempt to merge with a previous package
     * @return array of Package objects to add, may be empty if item merged with $packages
     */
    abstract protected function getPackageWorker($item, array &$packages);

    /**
     * Allows sub-classes the opportunity to convert currency values used in other functions.
     * @param float|int A currency value such as the value of a package in dollars
     * @return string The converted value
     */
    public function getCurrencyValue($value)
    {
        return $this->getValidatedFloat($value);
    }

    /**
     * Allows sub-classes the opportunity to convert measurement values used in other functions
     * Default implementation uses the measure unit retrieved from #getMeasureUnit to perform the conversion.
     * @param float|int A measurement value such as the length of a package in inches
     * @return string The converted value
     */
    public function getMeasurementValue($value)
    {
        return Measure::convertRounded($value, $this->measure_unit);
    }

    /**
     * Return the measurement unit based on the provided init options or a reasonable default unit.
     * Default implementation expects $init_options['dimension_unit'] to contain the unit or else returns 'in'.
     * @param $init_options
     * @return string
     */
    protected function getMeasureUnit($init_options)
    {
        try {
            return Measure::getStandardUnit(filter_var($init_options['dimension_unit'], FILTER_DEFAULT));
        } catch (\Exception $e) {
            // do nothing - missing or invalid unit of measure
        }

        return 'in';
    }

    /**
     * Allows sub-classes the opportunity to convert weight values used in other functions.
     * Default implementation uses the weight unit retrieved from #getWeightUnit to perform the conversion.
     * @param float|int A weight value such as the weight of a package in pounds
     * @return string The converted value
     */
    public function getWeightValue($value)
    {
        return Weight::convertRounded($value, $this->weight_unit);
    }

    /**
     * Return the weight unit based on the provided init options or a reasonable default unit.
     * Default implementation expects $init_options['weight_unit'] to contain the unit or else returns 'lb'.
     * @param $init_options
     * @return string
     */
    protected function getWeightUnit($init_options)
    {
        try {
            return Weight::getStandardUnit(filter_var($init_options['weight_unit'], FILTER_DEFAULT));
        } catch (\Exception $e) {
            // do nothing - missing or invalid unit of measure
        }

        return 'lb';
    }

    /**
     * @Deprecated since 06/16/2016 - constraint can be added directly
     * Adds (optional) constraint for the preferred package size (e.g. to avoid additional handling fees)
     * @param float|int $size Usually the max size before a package is considered 'large'
     *                        Value is passed through #getMeasurementValue before it is used
     * @return AbstractPacker Returns itself for convenience
     */
    public function setPreferredSize($size)
    {
        $this->addConstraint(new PackageValueConstraint($this->getMeasurementValue($size), 'size', '<='), 'preferred_size', false, true);

        return $this;
    }

    /**
     * @Deprecated since 06/16/2016 - constraint can be added directly
     * Adds (optional) constraint for the preferred package weight (e.g. to avoid additional handling fees)
     * @param float|int $weight Usually the max weight before a package is considered 'heavy'
     *                          Value is passed through #getWeightValue before it is used
     * @return AbstractPacker Returns itself for convenience
     */
    public function setPreferredWeight($weight)
    {
        $this->addConstraint(new PackageValueConstraint($this->getWeightValue($weight), 'weight', '<='), 'preferred_weight', false, true);

        return $this;
    }

    /**
     * @Deprecated since 06/16/2016 - constraint can be added directly
     * Adds optional additional handling constraint with given thresholds
     * @param float|int $first  Maximum length of longest dimension before additional handling charges are applied
     * @param float|int $second Maximum length of second-longest dimension before additional handling charges are applied
     *                          Values are passed through #getMeasurementValue before they are used
     * @return AbstractPacker Returns itself for convenience
     */
    public function setAdditionalHandlingLimits($first, $second)
    {
        $thresholds = [$this->getMeasurementValue($first), $this->getMeasurementValue($second)];
        $this->addConstraint(new PackageHandlingConstraint($thresholds), 'additional_handling', false, true);

        return $this;
    }

    /**
     * @Deprecated since 06/16/2016 - constraint can be added directly
     * Adds (required) constraint for the maximum allowed insurance amount
     * @param float|int $value Value is passed through #getCurrencyValue before it is used
     * @return AbstractPacker Returns itself for convenience
     */
    public function setMaxInsurance($value)
    {
        $value = $this->getCurrencyValue($value);
        $this->addConstraint(new PackageOptionConstraint($value, 'insured_amount', '<=', true), 'max_insurance', true, true);

        return $this;
    }

    /**
     * Override in child classes if the $item implementation differs from the default array.
     * @param $item array or Object containing information about the item(s) to be packaged
     * @return array of options for a new package containing the specified item(s)
     */
    protected function getPackageOptions($item)
    {
        return empty($item['options']) || ! is_array($item['options']) ? [] : $item['options'];
    }

    /**
     * Override in child classes if the $item implementation differs from the default array.
     * @param $item array or Object, depending on the implementation
     * @return int The quantity of the given item to be packaged (always at least 1)
     */
    protected function getQuantityFromItem($item)
    {
        if (array_key_exists('quantity', $item)) {
            return filter_var($item['quantity'], FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
        }

        return 1;
    }

    /**
     * Returns numeric arguments as an array('length','width','height') sorted from highest to lowest.
     * @param $l double Length
     * @param $w double Width
     * @param $h double Height
     * @return array|false
     */
    protected function getSortedDimensions($l, $w, $h)
    {
        $lwh = [$l, $w, $h];
        rsort($lwh, SORT_NUMERIC);

        return array_combine(['length', 'width', 'height'], $lwh);
    }

    /**
     * Split an item into two, each with half the original quantity and any other
     * properties adjusted accordingly - usually used in combination with recursion.
     *
     * @param array|object $item The item to split must have a quantity greater than 1
     * @return array containing exactly 2 catalog whose total quantity equals the original quantity
     */
    protected function splitItem($item)
    {
        $quantity = $this->getQuantityFromItem($item);
        if ($quantity < 2) {
            throw new InvalidArgumentException("Cannot split an item with quantity less than 2: item quantity = $quantity");
        }
        $tmp = [$item, $item];
        $tmp[0]['quantity'] = ceil($quantity / 2.0);
        $tmp[1]['quantity'] = $quantity - $tmp[0]['quantity'];

        return $tmp;
    }

    /**
     * Adds a merge strategy for use when combining catalog into previous packages.
     * Note that the IPacker implementation must support merging for this to have any effect.
     *
     * @param MergeStrategy $strategy
     * @return AbstractPacker Returns itself for convenience
     */
    public function addMergeStrategy(MergeStrategy $strategy)
    {
        $this->merge_strategies[] = $strategy;

        return $this;
    }

    /**
     * Attempts to merge up to the given quantity of a package into existing packages.
     * Best to only call this method if there is at least one IMergeStrategy available.
     *
     * @param array   $packages    Array of Package packages from #getPackageWorker
     * @param Package $single_item a Package object, usually representing quantity 1 of the item to be packed
     * @param int     $quantity    Quantity of the item to pack, usually retrieved from #getQuantityFromItem
     * @return int Remaining quantity after merge
     */
    protected function merge(array &$packages, Package $single_item, $quantity)
    {
        foreach ($packages as &$current_package) {
            while ($quantity > 0 && $this->mergePackage($current_package, $single_item, $this->merge_strategies)) {
                $quantity--;
            }
            if ($quantity < 1) {
                break;
            }
        }
        unset($current_package); // unset reference to save puppies

        return $quantity;
    }

    /**
     * Attempts to merge one package into another using the most efficient strategy provided.
     * The combined package must meet all required and optional constraints that apply.
     * Note that optional constraint status may be altered based on previous packages.
     *
     * @param Package $old  Reference to previously existing package - will be modified if merged
     * @param Package $item A package to be merged into the existing one
     * @param array        $strategies Any number of IMergeStrategy strategies to be attempted
     * @return true on success, otherwise false
     */
    protected function mergePackage(Package &$old, Package $item, array $strategies)
    {
        // Toggle optional constraint status based on previous package
        $this->updateOptionalConstraints($old);
        // Find the most efficiently packed package out of all available strategies
        $package = null;
        foreach ($strategies as $strategy) {
            if (! ($strategy instanceof MergeStrategy)) {
                // throw an exception to alert developers, or simply ignore it and continue on
                // throw new \InvalidArgumentException("Expected type IMergeStrategy, received " . getType($strategy));
                continue;
            }
            $combined = $this->getMergeResult($old, $item, $strategy);
            if ($combined) {
                if ($package == null || $package->get('size') > $combined->get('size')) {
                    $package = $combined;
                }
            }
        }
        if ($package instanceof \Awsp\Ship\Package) {
            $old = $package;

            return true;
        }

        return false;
    }

    /**
     * Attempts to merge the packages using the strategy provided, then checks
     * the resulting package against all required and optional constraints.
     *
     * @param Package $old
     * @param Package $item
     * @param MergeStrategy $strategy
     * @return Package|bool
     */
    protected function getMergeResult(Package $old, Package $item, MergeStrategy $strategy)
    {
        $combined = $strategy->merge($old, $item);
        if (! ($combined instanceof Package)) {
            return false;
        } elseif (! $this->checkConstraints($combined) || ! $this->checkOptionalConstraints($combined)) {
            return false;
        }

        return $combined;
    }

    /**
     * Adds a constraint, optionally overwriting any existing constraint with the same key.
     * A constraint should be considered 'required' if the shipping carrier would refuse a
     * non-concordant package, and 'optional' if it would simply incur an additional cost.
     *
     * @param Constraint $constraint The constraint to add
     * @param int|string  $key        Optional key parameter used to access the constraint
     * @param bool     $required   True if the constraint is required, or false for an optional constraint
     * @param bool     $overwrite  True to overwrite any existing constraint with the same key
     * @return AbstractPacker Returns itself for convenience
     * @throws InvalidArgumentException if a constraint exists for the provided key and $overwrite is false
     */
    public function addConstraint(Constraint $constraint, $key = null, $required = true, $overwrite = false)
    {
        if ($required) {
            $constraints = &$this->constraints;
        } else {
            $constraints = &$this->optional_constraints;
        }
        if ($key === null) {
            $constraints[] = $constraint;
        } elseif ($overwrite || ! array_key_exists($key, $constraints)) {
            $constraints[$key] = $constraint;
        } else {
            throw new InvalidArgumentException(($required ? 'Required' : 'Optional')." constraint '$key' already exists!");
        }

        return $this;
    }

    /**
     * Adds a post-constraint, optionally overwriting any existing constraint with the same key.
     * Post constraints are checked after packing has been completed.
     *
     * @param Constraint $constraint The constraint to add
     * @param int|string  $key        Optional key parameter used to access the constraint
     * @param bool     $overwrite  True to overwrite any existing constraint
     * @return AbstractPacker Returns itself for convenience
     * @throws InvalidArgumentException if a constraint exists for the provided key and $overwrite is false
     */
    public function addPostConstraint(Constraint $constraint, $key = null, $overwrite = false)
    {
        if ($key === null) {
            $this->post_constraints[] = $constraint;
        } elseif ($overwrite || ! array_key_exists($key, $this->post_constraints)) {
            $this->post_constraints[$key] = $constraint;
        } else {
            throw new InvalidArgumentException("Post constraint '$key' already exists!");
        }

        return $this;
    }

    /**
     * Checks whether or not the package fulfills all required constraints.
     * @param Package $package The Package to be checked
     * @param string  $error   Message describing the constraint that failed, if any
     * @return true if the package fulfills all required constraints
     */
    protected function checkConstraints(Package $package, &$error = '')
    {
        return $this->doConstraintCheck($this->constraints, $package, $error);
    }

    /**
     * Checks whether or not the package fulfills all optional constraints, e.g. when merging packages.
     * @param Package $package The Package to be checked
     * @param string  $error   Message describing the constraint that failed, if any
     * @return true if the package fulfills all optional constraints
     */
    protected function checkOptionalConstraints(Package $package, &$error = '')
    {
        return $this->doConstraintCheck($this->optional_constraints, $package, $error);
    }

    /**
     * Enables or disables optional constraints based on the provided package.
     * @param Package $package
     */
    protected function updateOptionalConstraints(Package $package)
    {
        foreach ($this->optional_constraints as $constraint) {
            $constraint->setStatus($constraint->check($package));
        }
    }

    private function doConstraintCheck(array $constraints, Package $package, &$error)
    {
        foreach ($constraints as $constraint) {
            if ($constraint->isEnabled() && ! $constraint->check($package, $error)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns value as a float after validating with PHP filter_var.
     * @param float|int $value
     * @return float
     * @throws InvalidArgumentException if the value fails the filter
     */
    final protected function getValidatedFloat($value)
    {
        if (false === ($return = filter_var($value, FILTER_VALIDATE_FLOAT))) {
            throw new InvalidArgumentException('Expected float or integer, received '.gettype($value));
        }

        return $return;
    }
}
