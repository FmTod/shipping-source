<?php
/**
 * The package class creates an object for each package being shipped.
 *
 * @authors Alex Fraundorf - AlexFraundorf.com, Brian Sandall
 * @copyright (c) 2017, Brian Sandall
 * @copyright (c) 2012-2013, Alex Fraundorf and AffordableWebSitePublishing.com LLC
 * @version 03/16/2017 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and
 *      there is plenty of room for improvement.  Use at your own risk.
 * @since 12/02/2012
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */

namespace FmTod\Shipping\Models;

use JetBrains\PhpStorm\Pure;
use UnexpectedValueException;

class Package
{
    /**
     * @var mixed integer or float - weight of package
     */
    protected $weight = null;

    /**
     * @var mixed integer or float - length of package (the longest dimension - sorted and set by constructor)
     */
    protected $length = null;

    /**
     * @var mixed integer or float - width of package
     */
    protected $width = null;

    /**
     * @var mixed integer or float - height of package
     */
    protected $height = null;

    /**
     * @var int calculated size of package (length plus girth)
     */
    protected $size = null;

    /**
     * @var int calculated volume of package
     */
    protected $volume = null;

    /**
     * @var array package options
     *
     * acceptable keys are:
     *  string 'description'
     *  string|int 'type'
     *  float|int 'insured_amount'
     *  boolean 'signature_required'
     */
    protected $options = [];

    /**
     * Constructor sets class properties and delegates calculation of the package size.
     *
     * @param float|int $weight the weight of the package - do NOT enclose in quotes!
     * @param array $dimensions - array elements can be integers or floats - do NOT enclose values in quotes!
     * @param array $options
     *@since 12/02/2012
     * @version updated 01/14/2013
     */
    public function __construct(float|int $weight, array $dimensions, array $options = [])
    {
        // set class weight property
        $this->weight = $weight;
        // set the object options
        $this->options = $options;
        // order the dimensions from longest to shortest
        rsort($dimensions, SORT_NUMERIC);
        // set class dimension properties
        // note: length is the longest dimension
        $this->length = $this->roundUpToTenth($dimensions[0]);
        $this->width = $this->roundUpToTenth($dimensions[1]);
        $this->height = $this->roundUpToTenth($dimensions[2]);
        // validate the package parameters
        $this->isPackageValid();
        // calculate the package's size and set the class property
        $this->size = $this->calculatePackageSize();
        $this->volume = $this->calculatePackageVolume();
    }

    /**
     * Rounds a float UP to the next tenth (always rounds up) ie: 2.32 becomes 2.4, 3.58 becomes 3.6.
     *
     * @param float $float the float to be rounded
     * @return float the rounded float
     *@version updated 12/09/2012
     * @since 12/09/2012
     */
    protected function roundUpToTenth(float $float): float|int
    {
        // round each value UP to the next tenth
        return ceil($float * 10) / 10;
    }

    /**
     * Validates the package's weight and dimensions.
     *
     * @version updated 12/09/2012
     * @since 12/04/2012
     * @return bool of package validity
     * @throws UnexpectedValueException if the weight or a dimension is invalid
     */
    protected function isPackageValid(): bool
    {
        // create an array of the values to validate
        $values = ['weight', 'length', 'width', 'height'];
        // create a variable to hold invalid properties
        $invalid_properties = null;
        // loop through the values to check
        foreach ($values as $value) {
            // make sure that each value is set and not less than or equal to zero
            if (! isset($this->{$value}) || $this->{$value} <= 0) {
                // add the invalid property to the array
                $invalid_properties .= $value.', ';
            } else {
                // make sure that the value evaluates to either an integer or a float
                if (! filter_var($this->{$value}, FILTER_SANITIZE_NUMBER_INT) &&
                        ! filter_var($this->{$value}, FILTER_SANITIZE_NUMBER_FLOAT)) {
                    // add the invalid property to the array
                    $invalid_properties .= $value.', ';
                }
            }
        }
        // if there are any invalid properties, throw an exception
        if (! empty($invalid_properties)) {
            throw new UnexpectedValueException('Package object is not valid.  Properties ('.$invalid_properties
                .') are invalid or not set.');
        } else {
            return true;
        }
    }

    /**
     * Calculates the package's size (the length plus the girth).
     *
     * @version updated 01/14/2013
     * @since 12/04/2012
     * @return int the size (length plus girth of the package) and rounded
     */
    #[Pure]
    protected function calculatePackageSize(): int
    {
        return (int) round($this->length + $this->calculatePackageGirth());
    }

    /**
     * Calculates the package's girth (the distance around the two smaller sides of the package or width + width
     *      + height + height.
     *
     * @param float|int|null $width the width of the package (if null, the object property $this->width will be used)
     * @param float|int|null $height the height of the package (if null, the object property $this->height will be used)
     * @return int the girth of the package
     *@since 12/04/2012
     * @version updated 01/14/2013
     */
    public function calculatePackageGirth(float|int $width = null, float|int $height = null): float|int
    {
        // if values are null, fill them with the object properties
        if ($width === null) {
            $width = $this->width;
        }
        if ($height === null) {
            $height = $this->height;
        }
        // calculate and return the girth
        return 2 * ($width + $height);
    }

    /**
     * Calculates the package's total volume to the nearest whole measurement unit.
     * @return int the volume of the package
     */
    public function calculatePackageVolume(): int
    {
        return (int) round($this->length * $this->width * $this->height);
    }

    /**
     * Returns the specified property of the object or throwns an exception if that property is not set.
     *
     * @param string $property the desired object property
     * @return mixed the value found for the desired object property
     * @throws UnexpectedValueException if the property is not set
     *@since 12/08/2012
     * @version updated 12/08/2012
     */
    public function get(string $property): mixed
    {
        if (! isset($this->{$property})) {
            throw new UnexpectedValueException('There is no data in the requested property ('.$property.').');
        }

        return $this->{$property};
    }

    /**
     * Returns the specified option value of the object's options array.
     *
     * @param string $key the desired key of the options array
     * @return mixed the value found for the desired array key
     *@version updated 01/01/2013
     * @since 01/01/2013
     */
    public function getOption(string $key): mixed
    {
        return $this->options[$key] ?? null;
    }

    /**
     * Merges another package's options with the current options array, as follows:
     * 'description'         : Descriptions concatenate unless the string is already present
     * 'insured_amount'      : Amounts are added together if present
     * 'type'                : Both packages must have the same packing type
     * 'additional_handling' : True if either package has this option
     * 'signature_required'  : True if either package has this option
     * default               : Current value if set, otherwise the merged package's value.
     *
     * @param Package $package The package being merged with the current Package instance
     * @param string $error   Message describing why the merge failed, if applicable
     * @return bool false if options were unable to merge
     */
    public function mergeOptions(self $package, string &$error): bool
    {
        if (empty($this->options)) {
            $this->options = $package->options;

            return true;
        }
        foreach ($package->options as $key => $value) {
            switch ($key) {
            case 'description': // Descriptions concatenate unless the string is already present
                $description = (empty($this->options[$key]) ? '' : $this->options[$key]);
                $description .= (empty($value) || str_contains($description, $value) ? '' : '. '.$value);
                if (! empty($description)) {
                    $this->options[$key] = $description;
                }

                break;
            case 'insured_amount': // Amounts are added together if present
                $value += (float) (empty($this->options[$key]) ? 0 : $this->options[$key]);
                if ($value > 0) {
                    $this->options[$key] = $value;
                }

                break;
            case 'type': // Both packages must have the same packing type
                $type = (array_key_exists($key, $this->options) ? $this->options[$key] : '');
                if ($type !== $value) {
                    $error = "<p>Packaging types do not match: $type vs. $value</p>";

                    return false;
                }

                break;
            case 'additional_handling': // fall-through
            case 'signature_required':  // True if either package has this option
                $this->options[$key] = (! empty($value) || ! empty($this->options[$key]));

                break;
            default: // default behavior is to keep original value if present
                if (! array_key_exists($key, $this->options)) {
                    $this->options[$key] = $value;
                }
            }
        }

        return true;
    }
}
