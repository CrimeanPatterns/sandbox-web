<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class ConfNo {

    /**
     * @var string
     * @Type("string")
     */
	public $number;

    /**
     * @var string
     * @Type("string")
     */
	public $description;

	/**
	 * @var boolean
	 * @Type("boolean")
	 */
	public $isPrimary;

}