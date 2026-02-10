<?php


namespace AwardWallet\Common\Geo\Google;

use JMS\Serializer\Annotation as JMS;

class ViewPort
{
    public function __construct(LatLng $ne, LatLng $sw)
    {
        $this->northeast = $ne;
        $this->southwest = $sw;
    }

    /**
     * @JMS\Type("AwardWallet\Common\Geo\Google\LatLng")
     *
     * @var LatLng
     */
    private $northeast;

    /**
     * @JMS\Type("AwardWallet\Common\Geo\Google\LatLng")
     *
     * @var LatLng
     */
    private $southwest;

    /**
     * @return LatLng
     */
    public function getNortheast()
    {
        return $this->northeast;
    }

    /**
     * @return mixed
     */
    public function getSouthwest()
    {
        return $this->southwest;
    }

    public function __toString()
    {
        return "$this->northeast|$this->southwest";
    }
}