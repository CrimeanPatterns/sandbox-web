<?php

namespace AwardWallet\Common\Geo\Google;

use JMS\Serializer\Annotation as JMS;

class PlaceDetailsResponse extends GoogleResponse
{
    /**
     * The detailed information about the place requested
     *
     * @JMS\Type("AwardWallet\Common\Geo\Google\PlaceDetails")
     *
     * @var PlaceDetails|null
     */
    private $result;

    /**
     * @return PlaceDetails
     */
    public function getResult()
    {
        return $this->result;
    }
}