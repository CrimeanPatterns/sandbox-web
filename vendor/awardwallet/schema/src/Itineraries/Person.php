<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class Person {

    const TYPE_INFANT = 'infant';

    /**
     * @var string
     * @Type("string")
     */
	public $name;

	/**
	 * @var boolean
	 * @Type("boolean")
	 */
	public $full;
    /**
     * @var string
     * @Type("string")
     */
	public $type;

}