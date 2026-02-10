<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class MarketingCarrier {

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

	/**
	 * @var boolean
	 * @Type("boolean")
	 */
	public $isCodeshare;

}