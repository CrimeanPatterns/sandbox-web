<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class CarRentalLocation {

    /**
     * @var Address
     * @Type("AwardWallet\Schema\Itineraries\Address")
     */
	public $address;

    /**
     * @var string
     * @Type("string")
     */
	public $localDateTime;

    /**
     * @var string
     * @Type("string")
     */
	public $openingHours;

    /**
     * @var string
     * @Type("string")
     */
	public $phone;

    /**
     * @var string
     * @Type("string")
     */
	public $fax;

}