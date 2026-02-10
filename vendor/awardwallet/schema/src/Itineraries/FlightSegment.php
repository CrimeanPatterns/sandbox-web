<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class FlightSegment {

    /**
     * @var TripLocation
     * @Type("AwardWallet\Schema\Itineraries\TripLocation")
     */
	public $departure;

    /**
     * @var TripLocation
     * @Type("AwardWallet\Schema\Itineraries\TripLocation")
     */
	public $arrival;

	/**
	 * @var MarketingCarrier
	 * @Type("AwardWallet\Schema\Itineraries\MarketingCarrier")
	 */
	public $marketingCarrier;

	/**
	 * @var OperatingCarrier
	 * @Type("AwardWallet\Schema\Itineraries\OperatingCarrier")
	 */
	public $operatingCarrier;

	/**
	 * @var Airline
	 * @Type("AwardWallet\Schema\Itineraries\Airline")
	 */
	public $wetleaseCarrier;

    /**
     * @var string[]
     * @Type("array<string>")
     */
	public $seats;

    /**
     * @var Aircraft
     * @Type("AwardWallet\Schema\Itineraries\Aircraft")
     */
	public $aircraft;

    /**
     * @var string
     * @Type("string")
     */
	public $traveledMiles;

    /**
     * @var integer
     * @Type("integer")
     */
	public $calculatedTraveledMiles;

    /**
     * @var string
     * @Type("string")
     */
	public $cabin;

    /**
     * @var string
     * @Type("string")
     */
	public $bookingCode;

    /**
     * @var string
     * @Type("string")
     */
	public $duration;

    /**
     * @var int
     * @Type("integer")
     */
	public $calculatedDuration;

    /**
     * @var string
     * @Type("string")
     */
	public $meal;

    /**
     * @var boolean
     * @Type("boolean")
     */
	public $smoking;

    /**
     * @var string
     * @Type("string")
     */
	public $status;

    /**
     * @var boolean
     * @Type("boolean")
     */
    public $cancelled;

    /**
     * @var integer
     * @Type("integer")
     */
	public $stops;

    /**
     * @var string
     * @Type("string")
     */
	public $flightStatsMethodUsed;

}