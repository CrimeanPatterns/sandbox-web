<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class CruiseDetails {

    /**
     * @var string
     * @Type("string")
     */
	public $description;

    /**
     * @var string
     * @Type("string")
     */
	public $class;

    /**
     * @var string
     * @Type("string")
     */
	public $deck;

    /**
     * @var string
     * @Type("string")
     */
	public $room;

    /**
     * @var string
     * @Type("string")
     */
	public $ship;

    /**
     * @var string
     * @Type("string")
     */
	public $shipCode;

    /**
     * @var string
     * @Type("string")
     */
	public $voyageNumber;

}