<?php

namespace AwardWallet\Common\Geo;

class GeoCodeResult
{

    /**
     * @var float
     */
    public $lat;
    /**
     * @var float
     */
    public $lng;
    /**
     * @var string
     */
    public $postalCode;
    /**
     * @var array
     */
    public $types = [];
    /**
     * @var string
     */
    public $formattedAddress;
    /**
     * @var array 
     */
    public $detailedAddress = [];
    /**
     * @var bool
     */
    public $cityUnreliable = false;

    /**
     * @var string
     */
    public $tzId;

    public function __construct(float $lat, float $lng)
    {
        $this->lat = $lat;
        $this->lng = $lng;
    }


}