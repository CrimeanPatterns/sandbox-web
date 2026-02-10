<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class Fee {

    /**
     * @var string
     * @Type("string")
     */
    public $name;

	/**
     * @var double
     * @Type("double")
     */
	public $charge;

}