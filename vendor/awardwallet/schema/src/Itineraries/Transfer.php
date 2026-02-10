<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class Transfer extends Transportation {

	/**
	 * @var TransferSegment[]
	 * @Type("array<AwardWallet\Schema\Itineraries\TransferSegment>")
	 */
	public $segments;

}