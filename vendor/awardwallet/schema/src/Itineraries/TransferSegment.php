<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class TransferSegment {

	/**
	 * @var TransferLocation
	 * @Type("AwardWallet\Schema\Itineraries\TransferLocation")
	 */
	public $departure;

	/**
	 * @var TransferLocation
	 * @Type("AwardWallet\Schema\Itineraries\TransferLocation")
	 */
	public $arrival;

	/**
	 * @var Car
	 * @Type("AwardWallet\Schema\Itineraries\Car")
	 */
	public $vehicleInfo;

	/**
	 * @var integer
	 * @Type("integer")
	 */
	public $adults;

	/**
	 * @var integer
	 * @Type("integer")
	 */
	public $kids;

	/**
	 * @var string
	 * @Type("string")
	 */
	public $traveledMiles;

	/**
	 * @var string
	 * @Type("string")
	 */
	public $duration;

}