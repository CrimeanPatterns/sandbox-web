<?php


namespace AwardWallet\Common\Geo\Google;

use JMS\Serializer\Annotation as JMS;

class GeoCodeResponse extends GoogleResponse
{
    /**
     * @JMS\Type("array<AwardWallet\Common\Geo\Google\GeoTag>")
     *
     * @var GeoTag[]
     */
    private $results = [];

    /**
     * @return GeoTag[]
     */
    public function getResults(): array
    {
        return $this->results;
    }
}