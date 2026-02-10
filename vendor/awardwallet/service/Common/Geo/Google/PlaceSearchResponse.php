<?php

namespace AwardWallet\Common\Geo\Google;

use JMS\Serializer\Annotation as JMS;

class PlaceSearchResponse extends GoogleResponse
{
    /**
     * List of places
     *
     * @JMS\Type("array<AwardWallet\Common\Geo\Google\Place>")
     *
     * @var Place[]
     */
    private $results = [];

    /**
     * List of places
     *
     * @return Place[]
     */
    public function getResults()
    {
        return $this->results;
    }
}