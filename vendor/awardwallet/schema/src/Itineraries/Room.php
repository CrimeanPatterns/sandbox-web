<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class Room {

    /**
     * @var string
     * @Type("string")
     */
	public $type;

    /**
     * @var string
     * @Type("string")
     */
	public $description;

	/**
	 * @var string
	 * @Type("string")
	 */
	public $rate;

	/**
	 * @var string
	 * @Type("string")
	 */
	public $rateType;

}