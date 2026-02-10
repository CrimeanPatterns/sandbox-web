<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class CruiseSegment {

    /**
     * @var TransportLocation
     * @Type("AwardWallet\Schema\Itineraries\TransportLocation")
     */
	public $departure;

    /**
     * @var TransportLocation
     * @Type("AwardWallet\Schema\Itineraries\TransportLocation")
     */
	public $arrival;

}