<?php

namespace AwardWallet\Common\Itineraries;

use JMS\Serializer\Annotation\Type;

/**
 * Class Cruise
 * @property $cruiseDetails
 */
class Cruise extends Flight
{
    /**
     * @var CruiseDetails
     * @Type("AwardWallet\Common\Itineraries\CruiseDetails")
     */
    protected $cruiseDetails;

}