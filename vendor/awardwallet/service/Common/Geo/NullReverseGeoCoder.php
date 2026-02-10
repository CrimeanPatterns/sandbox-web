<?php

namespace AwardWallet\Common\Geo;

class NullReverseGeoCoder implements ReverseGeoCodeSourceInterface
{

    public function reverseGeoCode(float $lat, float $lng): array
    {
        return [];
    }
}
