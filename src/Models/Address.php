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

use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Class Address.
 *
 * @property string $full_name
 * @property string $first_name
 * @property string $last_name
 * @property string $company
 * @property string $attention
 * @property string $phone
 * @property string $email
 * @property string $address1
 * @property string $address2
 * @property string $address3
 * @property string $city
 * @property string $state
 * @property string $postal_code
 * @property string $country_code
 * @property bool $is_residential
 */
class Address extends Model
{
    /** Map of 2-character to 3-character country codes */
    public static array $COUNTRY_CODES = [
        'AD' => 'AND',
        'AE' => 'ARE',
        'AF' => 'AFG',
        'AG' => 'ATG',
        'AI' => 'AIA',
        'AL' => 'ALB',
        'AN' => 'ANT',
        'AQ' => 'ATA',
        'AR' => 'ARG',
        'AS' => 'ASM',
        'AT' => 'AUT',
        'AU' => 'AUS',
        'AW' => 'ABW',
        'AZ' => 'AZE',
        'BA' => 'BIH',
        'BB' => 'BRB',
        'BD' => 'BGD',
        'BE' => 'BEL',
        'BF' => 'BFA',
        'BG' => 'BGR',
        'BH' => 'BHR',
        'BI' => 'BDI',
        'BJ' => 'BEN',
        'BM' => 'BMU',
        'BN' => 'BRN',
        'BO' => 'BOL',
        'BR' => 'BRA',
        'BS' => 'BHS',
        'BT' => 'BTN',
        'BV' => 'BVT',
        'BW' => 'BWA',
        'BY' => 'BLR',
        'BZ' => 'BLZ',
        'CA' => 'CAN',
        'CC' => 'CCK',
        'CD' => 'COD',
        'CF' => 'CAF',
        'CG' => 'COG',
        'CH' => 'CHE',
        'CI' => 'CIV',
        'CK' => 'COK',
        'CL' => 'CHL',
        'CM' => 'CMR',
        'CN' => 'CHN',
        'CO' => 'COL',
        'CR' => 'CRI',
        'CU' => 'CUB',
        'CV' => 'CPV',
        'CX' => 'CXR',
        'CY' => 'CYP',
        'CZ' => 'CZE',
        'DE' => 'DEU',
        'DJ' => 'DJI',
        'DK' => 'DNK',
        'DM' => 'DMA',
        'DO' => 'DOM',
        'DZ' => 'DZA',
        'EC' => 'ECU',
        'EE' => 'EST',
        'EG' => 'EGY',
        'EH' => 'ESH',
        'ER' => 'ERI',
        'ES' => 'ESP',
        'ET' => 'ETH',
        'FI' => 'FIN',
        'FJ' => 'FJI',
        'FK' => 'FLK',
        'FM' => 'FSM',
        'FO' => 'FRO',
        'FR' => 'FRA',
        'GA' => 'GAB',
        'GB' => 'GBR',
        'GD' => 'GRD',
        'GE' => 'GEO',
        'GF' => 'GUF',
        'GG' => 'GGY',
        'GH' => 'GHA',
        'GI' => 'GIB',
        'GL' => 'GRL',
        'GM' => 'GMB',
        'GN' => 'GIN',
        'GP' => 'GLP',
        'GQ' => 'GNQ',
        'GR' => 'GRC',
        'GS' => 'SGS',
        'GT' => 'GTM',
        'GU' => 'GUM',
        'GW' => 'GNB',
        'GY' => 'GUY',
        'HK' => 'HKG',
        'HM' => 'HMD',
        'HN' => 'HND',
        'HR' => 'HRV',
        'HT' => 'HTI',
        'HU' => 'HUN',
        'ID' => 'IDN',
        'IE' => 'IRL',
        'IL' => 'ISR',
        'IN' => 'IND',
        'IO' => 'IOT',
        'IQ' => 'IRQ',
        'IR' => 'IRN',
        'IS' => 'ISL',
        'IT' => 'ITA',
        'JE' => 'JEY',
        'JM' => 'JAM',
        'JO' => 'JOR',
        'JP' => 'JPN',
        'KE' => 'KEN',
        'KG' => 'KGZ',
        'KH' => 'KHM',
        'KI' => 'KIR',
        'KM' => 'COM',
        'KN' => 'KNA',
        'KP' => 'PRK',
        'KR' => 'KOR',
        'KW' => 'KWT',
        'KY' => 'CYM',
        'KZ' => 'KAZ',
        'LA' => 'LAO',
        'LB' => 'LBN',
        'LC' => 'LCA',
        'LI' => 'LIE',
        'LK' => 'LKA',
        'LS' => 'LSO',
        'LT' => 'LTU',
        'LU' => 'LUX',
        'LV' => 'LVA',
        'LY' => 'LBY',
        'MA' => 'MAR',
        'MC' => 'MCO',
        'MD' => 'MDA',
        'ME' => 'MNE',
        'MG' => 'MDG',
        'MH' => 'MHL',
        'MK' => 'MKD',
        'ML' => 'MLI',
        'MM' => 'MMR',
        'MN' => 'MNG',
        'MO' => 'MAC',
        'MP' => 'MNP',
        'MQ' => 'MTQ',
        'MR' => 'MRT',
        'MS' => 'MSR',
        'MT' => 'MLT',
        'MU' => 'MUS',
        'MV' => 'MDV',
        'MW' => 'MWI',
        'MX' => 'MEX',
        'MY' => 'MYS',
        'MZ' => 'MOZ',
        'NA' => 'NAM',
        'NC' => 'NCL',
        'NE' => 'NER',
        'NF' => 'NFK',
        'NG' => 'NGA',
        'NI' => 'NIC',
        'NL' => 'NLD',
        'NO' => 'NOR',
        'NP' => 'NPL',
        'NR' => 'NRU',
        'NU' => 'NIU',
        'NZ' => 'NZL',
        'OM' => 'OMN',
        'PA' => 'PAN',
        'PE' => 'PER',
        'PF' => 'PYF',
        'PG' => 'PNG',
        'PH' => 'PHL',
        'PK' => 'PAK',
        'PL' => 'POL',
        'PM' => 'SPM',
        'PN' => 'PCN',
        'PR' => 'PRI',
        'PS' => 'PSE',
        'PT' => 'PRT',
        'PW' => 'PLW',
        'PY' => 'PRY',
        'QA' => 'QAT',
        'RE' => 'REU',
        'RO' => 'ROM',
        'RS' => 'SRB',
        'RU' => 'RUS',
        'RW' => 'RWA',
        'SA' => 'SAU',
        'SB' => 'SLB',
        'SC' => 'SYC',
        'SD' => 'SDN',
        'SE' => 'SWE',
        'SG' => 'SGP',
        'SH' => 'SHN',
        'SI' => 'SVN',
        'SJ' => 'SJM',
        'SK' => 'SVK',
        'SL' => 'SLE',
        'SM' => 'SMR',
        'SN' => 'SEN',
        'SO' => 'SOM',
        'SR' => 'SUR',
        'ST' => 'STP',
        'SV' => 'SLV',
        'SY' => 'SYR',
        'SZ' => 'SWZ',
        'TC' => 'TCA',
        'TD' => 'TCD',
        'TF' => 'ATF',
        'TG' => 'TGO',
        'TH' => 'THA',
        'TJ' => 'TJK',
        'TK' => 'TKL',
        'TL' => 'TLS',
        'TM' => 'TKM',
        'TN' => 'TUN',
        'TO' => 'TON',
        'TR' => 'TUR',
        'TT' => 'TTO',
        'TV' => 'TUV',
        'TW' => 'TWN',
        'TZ' => 'TZA',
        'UA' => 'UKR',
        'UG' => 'UGA',
        'UM' => 'UMI',
        'US' => 'USA',
        'UY' => 'URY',
        'UZ' => 'UZB',
        'VA' => 'VAT',
        'VC' => 'VCT',
        'VE' => 'VEN',
        'VG' => 'VGB',
        'VI' => 'VIR',
        'VN' => 'VNM',
        'VU' => 'VUT',
        'WF' => 'WLF',
        'WS' => 'WSM',
        'YE' => 'YEM',
        'YT' => 'MYT',
        'ZA' => 'ZAF',
        'ZM' => 'ZMB',
        'ZW' => 'ZWE',
    ];

    protected array $fillable = [
        'first_name',
        'last_name',
        'company',
        'attention',
        'phone',
        'email',
        'address1',
        'address2',
        'address3',
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
            'required_without:company',
            'string',
        ],
        'last_name' => [
            'required_without:company',
            'string',
        ],
        'company' => [
            'required_without_all:first_name,last_name',
            'string',
        ],
        'attention' => [
            'nullable',
            'string',
        ],
        'phone' => [
            'nullable',
            'string',
        ],
        'email' => [
            'nullable',
            'email:rfc',
        ],
        'address1' => [
            'required',
            'string',
        ],
        'address2' => [
            'nullable',
            'string',
        ],
        'address3' => [
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
            $this->setRule('phone', ['string', 'required']);
        }
    }

    /**
     * Convert given country code to new format, if possible.
     *
     * @param string $code original country code, e.g. 'US'
     * @param int $alpha ISO 3166-1 alpha designation; acceptable values are 2 and 3
     * @return string Country code with the specified number of characters
     */
    public static function formatCountryCode(string $code, int $alpha = 2): string
    {
        $code = strtoupper($code);

        if ($alpha !== 2 && $alpha !== 3) {
            throw new InvalidArgumentException('Valid values for alpha are 2 and 3. Received '.print_r($alpha, true));
        }

        if (strlen($code) === $alpha) {
            return $code;
        }

        if ($alpha === 3 && array_key_exists($code, self::$COUNTRY_CODES)) {
            return self::$COUNTRY_CODES[$code];
        }

        if (in_array($code, self::$COUNTRY_CODES, true)) {
            return array_flip(self::$COUNTRY_CODES)[$code];
        }

        throw new InvalidArgumentException("Failed to convert country code $code to $alpha characters in length");
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
