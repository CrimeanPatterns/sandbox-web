<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class TransferLocation {

	/**
	 * @var string
	 * @Type("string")
	 */
	public $airportCode;

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