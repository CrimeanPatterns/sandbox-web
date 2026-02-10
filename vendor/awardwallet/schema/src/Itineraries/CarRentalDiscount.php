<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class CarRentalDiscount {

    /**
     * @var string
     * @Type("string")
     */
	public $name;

    /**
     * @var string
     * @Type("string")
     */
	public $code;

}