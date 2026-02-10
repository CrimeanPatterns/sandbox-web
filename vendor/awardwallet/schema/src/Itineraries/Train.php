<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class Train extends Transportation {

	/**
	 * @var TrainSegment[]
	 * @Type("array<AwardWallet\Schema\Itineraries\TrainSegment>")
	 */
	public $segments;

	/**
	 * @var ParsedNumber[]
	 * @Type("array<AwardWallet\Schema\Itineraries\ParsedNumber>")
	 */
	public $ticketNumbers;

}