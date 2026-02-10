<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class HotelReservation extends Itinerary {

    /**
     * @var ConfNo[]
     * @Type("array<AwardWallet\Schema\Itineraries\ConfNo>")
     */
	public $confirmationNumbers;

    /**
     * @var string
     * @Type("string")
     */
	public $hotelName;

    /**
     * @var string
     * @Type("string")
     */
	public $chainName;

    /**
     * @var Address
     * @Type("AwardWallet\Schema\Itineraries\Address")
     */
	public $address;

    /**
     * @var string
     * @Type("string")
     */
	public $checkInDate;

    /**
     * @var string
     * @Type("string")
     */
	public $checkOutDate;

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
     * @var Person[]
     * @Type("array<AwardWallet\Schema\Itineraries\Person>")
     */
	public $guests;

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
     * @var integer
     * @Type("integer")
     */
	public $roomsCount;

    /**
     * @var string
     * @Type("string")
     */
    public $cancellationNumber;

    /**
     * @var string
     * @Type("string")
     */
	public $cancellationDeadline;

    /**
     * @var bool
     * @Type("bool")
     */
	public $isNonRefundable;

    /**
     * @var Room[]
     * @Type("array<AwardWallet\Schema\Itineraries\Room>")
     */
	public $rooms;

    /**
     * @var integer
     * @Type("integer")
     */
	public $freeNights;


    public function getPersons(): array
    {
        return $this->guests ?? [];
    }

}