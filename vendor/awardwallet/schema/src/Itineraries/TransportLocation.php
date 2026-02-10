<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class TransportLocation {

    /**
     * @var string
     * @Type("string")
     */
	public $stationCode;

    /**
     * @var string
     * @Type("string")
     */
	public $name;

    /**
     * @var string
     * @Type("string")
     */
	public $localDateTime;

    /**
     * @var Address
     * @Type("AwardWallet\Schema\Itineraries\Address")
     */
	public $address;

}