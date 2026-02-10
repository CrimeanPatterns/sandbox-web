<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class TransportationSegment {

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
     * @var Transport
     * @Type("AwardWallet\Schema\Itineraries\Transport")
     */
	public $transport;

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