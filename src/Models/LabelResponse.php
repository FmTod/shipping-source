<?php
/**
 * The package class creates an object to hold the response from a rate query.
 *
 * @author Alex Fraundorf - AlexFraundorf.com
 * @copyright (c) 2012-2013, Alex Fraundorf and AffordableWebSitePublishing.com LLC
 * @version 04/15/2013 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and
 *      there is plenty of room for improvement.  Use at your own risk.
 * @since 12/08/2012
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */

namespace FmTod\Shipping\Models;

/**
 * Class LabelResponse.
 *
 * @property float|float $shipment_cost
 * @property string $provider
 * @property string $carrier
 * @property string $service
 * @property string $master_tracking_number
 * @property int $estimated_days
 * @property array $labels
 */
class LabelResponse
{
    public $shipment_cost = null;

    public $provider = null;

    public $carrier = null;

    public $service = null;

    public $master_tracking_number = null;

    public $estimated_days = null;

    /**
     * Holds the details of each shipping service available.
     * @property array $labels
     *  Each array element will contain:
     *      string [tracking_number] the tracking number of the package
     *      string [label_url] type of format for label ie: gif, tif
     *      string [label_file_type] type of format for label ie: gif, tif
     */
    public $labels = [];

    /**
     * Constructs the object and sets the status.
     *
     * @param string $status the status of the request - 'Success' or 'Error'
     * @version updated 12/28/2012
     * @since 12/08/2012
     */
    public function __construct()
    {
        // set class properties
        //$this->status = $status;
    }

    /**
     * Returns the labelResponse object as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        foreach ($this->labels as $label) {
            if ($label['tracking_number'] === $this->master_tracking_number) {
                $url = $label['label_url'];
            }
        }

        return [
            'provider' => $this->provider,
            'carrier' => $this->carrier,
            'service' => $this->service,
            'master_tracking_number' => $this->master_tracking_number,
            'estimated_days' => $this->estimated_days,
            'labels' => $this->labels,
            'shipment_cost' => $this->shipment_cost,
            'url' => $url ?? '',
        ];
    }
}
