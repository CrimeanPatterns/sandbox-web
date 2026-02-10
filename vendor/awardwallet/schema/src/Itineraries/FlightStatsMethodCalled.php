<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class FlightStatsMethodCalled
{

    /**
     * @var string
     * @Type("string")
     */
    public $name;

    /**
     * @var integer
     * @Type("integer")
     */
    public $count;

}