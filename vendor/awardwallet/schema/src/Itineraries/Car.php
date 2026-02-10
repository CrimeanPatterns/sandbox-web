<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class Car {

    /**
     * @var string
     * @Type("string")
     */
	public $type;

    /**
     * @var string
     * @Type("string")
     */
	public $model;

    /**
     * @var string
     * @Type("string")
     */
	public $imageUrl;

}