<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class ParsedNumber {

	/**
	 * @var string
	 * @Type("string")
	 */
	public $number;

	/**
	 * @var boolean
	 * @Type("boolean")
	 */
	public $masked;

}