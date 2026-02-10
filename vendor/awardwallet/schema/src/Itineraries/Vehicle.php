<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class Vehicle {

	/**
	 * @var string
	 * @Type("string")
	 */
	public $type;

	/**
	 * @var string
	 * @Type("string")
	 */
	public $model;

}