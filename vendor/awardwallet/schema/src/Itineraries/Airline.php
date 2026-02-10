<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class Airline {

    /**
     * @var string
     * @Type("string")
     */
	public $name;

    /**
     * @var string
     * @Type("string")
     */
	public $iata;

    /**
     * @var string
     * @Type("string")
     */
	public $icao;

}