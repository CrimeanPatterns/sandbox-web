<?php


namespace AwardWallet\Common\Geo\Google;

use JMS\Serializer\Annotation as JMS;

class LatLng
{
    /**
     * Latitude
     *
     * @JMS\Type("float")
     *
     * @var float
     */
    private $lat;

    /**
     * Longitude
     *
     * @JMS\Type("float")
     *
     * @var float
     */
    private $lng;

    /**
     * LatLng constructor.
     * @param float $latitude
     * @param float $longitude
     */
    public function __construct(float $latitude, float $longitude)
    {
        $this->lat = $latitude;
        $this->lng = $longitude;
    }

    /**
     * @return float
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * @return float
     */
    public function getLng()
    {
        return $this->lng;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return preg_replace('#\.$#', '', preg_replace('#0+$#', '', sprintf("%0.7f", $this->lat))) . "," . preg_replace('#\.$#', '', preg_replace('#0+$#', '', sprintf("%0.7f", $this->lng)));
    }
}