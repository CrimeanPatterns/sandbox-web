<?php

namespace AwardWallet\Common\Itineraries;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\Accessor;

/**
 * Class Reservation
 * @property string $hotelName
 * @property string $chainName
 * @property string $checkInDate
 * @property string $checkOutDate
 * @property Address $address
 * @property string $phone
 * @property string $fax
 * @property Person[] $guests
 * @property integer $guestCount
 * @property integer $kidsCount
 * @property Room[] $rooms
 * @property integer $roomsCount
 * @property string $cancellationPolicy
 */
class HotelReservation extends Itinerary
{
    /**
     * @var string
     * @Type("string")
     */
    protected $hotelName;
    /**
     * @var string
     * @Type("string")
     */
    protected $chainName;
    /**
     * @var string
     * @Type("string")
     */
    protected $checkInDate;
    /**
     * @var string
     * @Type("string")
     */
    protected $checkOutDate;
    /**
     * @var Address
     * @Type("AwardWallet\Common\Itineraries\Address")
     */
    protected $address;
    /**
     * @var string
     * @Type("string")
     */
    protected $phone;
    /**
     * @var string
     * @Type("string")
     */
    protected $fax;
    /**
     * @var Person[]
     * @Type("array<AwardWallet\Common\Itineraries\Person>")
     * @Accessor(getter="getGuestsForJMS", setter="setGuests")
     */
    protected $guests;
    /**
     * @var integer
     * @Type("integer")
     */
    protected $guestCount;
    /**
     * @var integer
     * @Type("integer")
     */
    protected $kidsCount;
    /**
     * @var Room[]
     * @Type("array<AwardWallet\Common\Itineraries\Room>")
     * @Accessor(getter="getRoomsForJMS", setter="setRooms")
     */
    protected $rooms;
    /**
     * @var integer
     * @Type("integer")
     */
    protected $roomsCount;
    /**
     * @var string
     * @Type("string")
     */
    protected $cancellationPolicy;

}
