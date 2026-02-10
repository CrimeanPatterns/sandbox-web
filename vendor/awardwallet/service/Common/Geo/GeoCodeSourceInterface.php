<?php

namespace AwardWallet\Common\Geo;

interface GeoCodeSourceInterface
{

    public function getSourceId() : string;
    /**
     * @return GeoCodeResult[]
     */
    public function geoCode(string $query, array $bias = []) : array;

}