<?php

namespace AwardWallet\Common\Itineraries;

use JMS\Serializer\Annotation\Type;

/**
 * Class TripSegment
 * @property FlightPoint $departure
 * @property FlightPoint $arrival
 * @property $seats
 * @property $transport
 * @property $flightNumber
 * @property $scheduleNumber
 * @property $airlineName
 * @property $operator
 * @property $aircraft
 * @property $traveledMiles
 * @property $awardMiles
 * @property $cabin
 * @property $bookingClass
 * @property $duration
 * @property $meal
 * @property $smoking
 * @property $pendingUpgradeTo
 * @property $stops
 */
class FlightSegment extends LoggerEntity
{

    /**
     * @var FlightPoint
     * @Type("AwardWallet\Common\Itineraries\FlightPoint")
     */
    protected $departure;

    /**
     * @var FlightPoint
     * @Type("AwardWallet\Common\Itineraries\FlightPoint")
     */
    protected $arrival;
    /**
     * @var array
     * @Type("array")
     */
    protected $seats;
    /**
     * @var Transport
     * @Type("AwardWallet\Common\Itineraries\Transport")
     */
    protected $transport;
    /**
     * @var string
     * @Type("string")
     */
    protected $flightNumber;
    /**
     * @var string
     * @Type("string")
     */
    protected $scheduleNumber;
    /**
     * @var string
     * @Type("string")
     */
    protected $airlineName;
    /**
     * @var string
     * @Type("string")
     */
    protected $operator;
    /**
     * @var string
     * @Type("string")
     */
    protected $aircraft;
    /**
     * @var string
     * @Type("string")
     */
    protected $traveledMiles;
    /**
     * @var string
     * @Type("string")
     */
    protected $cabin;
    /**
     * @var string
     * @Type("string")
     */
    protected $bookingClass;
    /**
     * @var string
     * @Type("string")
     */
    protected $duration;
    /**
     * @var string
     * @Type("string")
     */
    protected $meal;
    /**
     * @var string
     * @Type("string")
     */
    protected $smoking;
    /**
     * @var string
     * @Type("string")
     */
    protected $pendingUpgradeTo;
    /**
     * @var integer
     * @Type("integer")
     */
    protected $stops;

}