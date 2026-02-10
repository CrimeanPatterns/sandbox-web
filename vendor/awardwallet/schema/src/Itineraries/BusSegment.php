<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class BusSegment {

	/**
	 * @var string
	 * @Type("string")
	 */
	public $scheduleNumber;

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
	 * @var Vehicle
	 * @Type("AwardWallet\Schema\Itineraries\Vehicle")
	 */
	public $busInfo;

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

}