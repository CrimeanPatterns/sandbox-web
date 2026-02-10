<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class OperatingCarrier {

    /**
     * @var Airline
     * @Type("AwardWallet\Schema\Itineraries\Airline")
     */
	public $airline;

    /**
     * @var string
     * @Type("string")
     */
	public $flightNumber;

	/**
	 * @var string
	 * @Type("string")
	 */
	public $confirmationNumber;

	/**
	 * @var PhoneNumber[]
	 * @Type("array<AwardWallet\Schema\Itineraries\PhoneNumber>")
	 */
	public $phoneNumbers;

}