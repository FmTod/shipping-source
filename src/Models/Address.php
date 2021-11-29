<?php
/**
 * The Address class contains all information necessary to send or receive a shipment.
 *
 * @author Brian Sandall (adapted from Alex Fraundorf's original Awsp\Shipment.php implementation)
 * @copyright (c) 2015 Brian Sandall
 * @version 07/07/2015 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and
 *      there is plenty of room for improvement.  Use at your own risk.
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */

namespace FmTod\Shipping\Models;

use FmTod\Shipping\Concerns\FormatsCountryCodes;
use FmTod\Shipping\Concerns\HasShippableAddressAttributes;
use FmTod\Shipping\Contracts\ShippableAddress;
use Illuminate\Support\Str;

/**
 * Class Address.
 *
 * @property string $full_name
 * @property string $first_name
 * @property string $last_name
 * @property string $company_name
 * @property string $phone_number
 * @property string $email
 * @property string $street_address1
 * @property string $street_address2
 * @property string $city
 * @property string $state
 * @property string $postal_code
 * @property string $country_code
 * @property bool $is_residential
 */
class Address extends Model implements ShippableAddress
{
    use HasShippableAddressAttributes;
    use FormatsCountryCodes;

    protected array $fillable = [
        'first_name',
        'last_name',
        'company_name',
        'phone_number',
        'email',
        'street_address1',
        'street_address2',
        'city',
        'state',
        'postal_code',
        'country_code',
        'is_residential',
    ];

    protected array $attributes = [
        'is_residential' => false,
    ];

    protected array $rules = [
        'first_name' => [
            'nullable',
            'required_without:company',
            'string',
        ],
        'last_name' => [
            'nullable',
            'required_without:company',
            'string',
        ],
        'company_name' => [
            'nullable',
            'required_without_all:first_name,last_name',
            'string',
        ],
        'phone_number' => [
            'nullable',
            'string',
        ],
        'email' => [
            'nullable',
            'email:rfc',
        ],
        'street_address1' => [
            'required',
            'string',
        ],
        'street_address2' => [
            'nullable',
            'string',
        ],
        'city' => [
            'required_without:state',
            'string',
        ],
        'state' => [
            'required_without:city',
            'string',
        ],
        'postal_code' => [
            'required',
            'string',
        ],
        'country_code' => [
            'required',
            'string',
            'size:2',
            ],
        'is_residential' => [
            'required',
            'boolean',
        ],
    ];

    protected bool $validateOnFill = true;

    /**
     * Constructs address object from the given array.
     * Required elements: 'address1', 'city', 'state', 'postal_code', 'country_code'
     * Allowed array elements: 'name','attention','phone','email','address1','address2','address3','city','state','postal_code','country_code','is_residential'.
     * @param array $data
     * @param bool $validateAsLabel If true, 'name' and 'phone' fields will also be required
     * @return void
     */
    public function __construct(array $data = [], bool $validateAsLabel = true)
    {
        parent::__construct($data);

        if ($validateAsLabel) {
            $this->setRule('phone_number', ['string', 'required']);
        }
    }

    /**
     * Get full name attribute by gluing the first and last names
     *
     * @return string|null
     */
    public function getFullNameAttribute(): ?string
    {
        if (empty($this->first_name) && empty($this->last_name)) {
            return null;
        }

        return implode(' ', array_filter([$this->first_name, $this->last_name]));
    }

    /**
     * Set full name attribute by splitting the provided string after the first space.
     *
     * @param string $value
     * @return void
     */
    public function setFullNameAttribute(string $value): void
    {
        $this->first_name = Str::before($value, ' ');
        $this->last_name = Str::after($value, ' ');
    }

    /**
     * Set and format the provided country code as the two character code
     *
     * @param string $value
     */
    public function setCountryCodeAttribute(string $value): void
    {
        $this->attributes['country_code'] = self::formatCountryCode($value);
    }
}
