<?php

namespace AwardWallet\Common\API\Email\V2\BoardingPass;

use JMS\Serializer\Annotation\Type;

class BoardingPass {

	/**
	 * @var string
	 * @Type("string")
	 */
	public $passenger;
	/**
	 * @var string
	 * @Type("string")
	 */
	public $recordLocator;
	/**
	 * @var string
	 * @Type("string")
	 */
	public $departureDate;
	/**
	 * @var string
	 * @Type("string")
	 */
	public $departureAirportCode;
	/**
	 * @var string
	 * @Type("string")
	 */
	public $flightNumber;
	/**
	 * @var string
	 * @Type("string")
	 */
	public $boardingPassUrl;
	/**
	 * @var string
	 * @Type("string")
	 */
	public $attachmentFileName;

}