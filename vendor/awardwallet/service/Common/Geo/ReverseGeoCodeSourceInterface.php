<?php

namespace AwardWallet\Common\Geo;

interface ReverseGeoCodeSourceInterface
{

    /**
     * @return GeoCodeResult[]
     */
    public function reverseGeoCode(float $lat, float $lng) : array;

}