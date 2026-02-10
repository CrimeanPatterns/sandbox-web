<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class Bus extends Transportation {

	/**
	 * @var BusSegment[]
	 * @Type("array<AwardWallet\Schema\Itineraries\BusSegment>")
	 */
	public $segments;

	/**
	 * @var ParsedNumber[]
	 * @Type("array<AwardWallet\Schema\Itineraries\ParsedNumber>")
	 */
	public $ticketNumbers;

}