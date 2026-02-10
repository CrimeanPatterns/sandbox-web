<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class Address {

    /**
     * @var string
     * @Type("string")
     */
	public $text;

    /**
     * @var string
     * @Type("string")
     */
	public $addressLine;

    /**
     * @var string
     * @Type("string")
     */
	public $city;

    /**
     * @var string
     * @Type("string")
     */
	public $stateName;

    /**
     * @var string
     * @Type("string")
     */
	public $countryName;
    /**
     * @var string
     * @Type("string")
     */
	public $countryCode;

    /**
     * @var string
     * @Type("string")
     */
	public $postalCode;

    /**
     * @var double
     * @Type("double")
     */
	public $lat;

    /**
     * @var double
     * @Type("double")
     */
	public $lng;

    /**
     * @var integer
     * @Type("integer")
     */
	public $timezone;

    /**
     * @var string
     * @Type("string")
     */
    public $timezoneId;

}