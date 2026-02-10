<?php

namespace AwardWallet\Common\Itineraries;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\Accessor;

/**
 * Class Event
 * @property $eventName
 * @property $address
 * @property $startDateTime
 * @property $endDateTime
 * @property $phone
 * @property $fax
 * @property $guests
 * @property $guestCount
 * @property $eventType
 */
class Event extends Itinerary
{
    /**
     * @var string
     * @Type("string")
     */
    protected $eventName;
    /**
     * @var Address
     * @Type("AwardWallet\Common\Itineraries\Address")
     */
    protected $address;
    /**
     * @var string
     * @Type("string")
     */
    protected $startDateTime;
    /**
     * @var string
     * @Type("string")
     */
    protected $endDateTime;
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
    protected $eventType;

}