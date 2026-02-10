<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class PhoneNumber {

    /**
     * @var string
     * @Type("string")
     */
	public $description;

    /**
     * @var string
     * @Type("string")
     */
	public $number;

}