<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class IssuingCarrier {

    /**
     * @var Airline
     * @Type("AwardWallet\Schema\Itineraries\Airline")
     */
	public $airline;

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
     * @var ParsedNumber[]
     * @Type("array<AwardWallet\Schema\Itineraries\ParsedNumber>")
     */
	public $ticketNumbers;

}