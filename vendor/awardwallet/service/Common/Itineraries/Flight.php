<?php

namespace AwardWallet\Common\Itineraries;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\Accessor;

/**
 * Class Trip
 * @property $segments
 * @property $travelers
 * @property $ticketNumbers
 */
class Flight extends Itinerary
{
    /**
     * @var FlightSegment[]
     * @Type("array<AwardWallet\Common\Itineraries\FlightSegment>")
     * @Accessor(getter="getSegmentsForJMS", setter="setSegments")
     */
    protected $segments;
    /**
     * @var Person[]
     * @Type("array<AwardWallet\Common\Itineraries\Person>")
     * @Accessor(getter="getTravelersForJMS", setter="setTravelers")
     */
    protected $travelers;
    /**
     * @var array
     * @Type("array")
     */
    protected $ticketNumbers;

}
