<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class Aircraft {

    /**
     * @var string
     * @Type("string")
     */
	public $iataCode;

    /**
     * @var string
     * @Type("string")
     */
	public $name;

    /**
     * @var boolean
     * @Type("boolean")
     */
	public $turboProp;

    /**
     * @var boolean
     * @Type("boolean")
     */
	public $jet;

    /**
     * @var boolean
     * @Type("boolean")
     */
	public $wideBody;

    /**
     * @var boolean
     * @Type("boolean")
     */
	public $regional;
    /**
     * @var string
     * @Type("string")
     */
    public $registrationNumber;

}