<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class Event extends Itinerary {

    /**
     * @var ConfNo[]
     * @Type("array<AwardWallet\Schema\Itineraries\ConfNo>")
     */
	public $confirmationNumbers;

    /**
     * @var Address
     * @Type("AwardWallet\Schema\Itineraries\Address")
     */
	public $address;

    /**
     * @var string
     * @Type("string")
     */
	public $eventName;

    /**
     * @var integer
     * @Type("integer")
     */
	public $eventType;

    /**
     * @var string
     * @Type("string")
     */
	public $startDateTime;

    /**
     * @var string
     * @Type("string")
     */
	public $endDateTime;

    /**
     * @var string
     * @Type("string")
     */
	public $phone;

    /**
     * @var string
     * @Type("string")
     */
	public $fax;

    /**
     * @var integer
     * @Type("integer")
     */
	public $guestCount;

    /**
     * @var integer
     * @Type("integer")
     */
    public $kidsCount;

    /**
     * @var Person[]
     * @Type("array<AwardWallet\Schema\Itineraries\Person>")
     */
	public $guests;

	/**
	 * @var string[]
	 * @Type("array<string>")
	 */
	public $seats;

	public function getPersons(): array
    {
        return $this->guests ?? [];
    }

}