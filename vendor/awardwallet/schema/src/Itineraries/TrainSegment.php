<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class TrainSegment {

	/**
	 * @var TransportLocation
	 * @Type("AwardWallet\Schema\Itineraries\TransportLocation")
	 */
	public $departure;

	/**
	 * @var TransportLocation
	 * @Type("AwardWallet\Schema\Itineraries\TransportLocation")
	 */
	public $arrival;

	/**
	 * @var string
	 * @Type("string")
	 */
	public $scheduleNumber;

	/**
	 * @var string
	 * @Type("string")
	 */
	public $serviceName;

	/**
	 * @var Vehicle
	 * @Type("AwardWallet\Schema\Itineraries\Vehicle")
	 */
	public $trainInfo;

	/**
	 * @var string
	 * @Type("string")
	 */
	public $car;

	/**
	 * @var string[]
	 * @Type("array<string>")
	 */
	public $seats;

	/**
	 * @var string
	 * @Type("string")
	 */
	public $traveledMiles;

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
	 * @var integer
	 * @Type("integer")
	 */
	public $stops;

    /**
     * @var TicketLink[]
     * @Type("array<AwardWallet\Schema\Itineraries\TicketLink>")
     */
	public $ticketLinks;

}