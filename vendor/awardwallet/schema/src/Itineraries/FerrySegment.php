<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class FerrySegment
{

    /**
     * @var TransportLocation
     * @Type("AwardWallet\Schema\Itineraries\TransportLocation")
     */
    public $departure;

    /**
     * @var TransportLocation
     * @Type("AwardWallet\Schema\Itineraries\TransportLocation")
     */
    public $arrival;

    /**
     * @var string[]
     * @Type("array<string>")
     */
    public $accommodations;

    /**
     * @var string
     * @Type("string")
     */
    public $carrier;

    /**
     * @var string
     * @Type("string")
     */
    public $vessel;

    /**
     * @var string
     * @Type("string")
     */
    public $traveledMiles;

    /**
     * @var string
     * @Type("string")
     */
    public $duration;

    /**
     * @var string
     * @Type("string")
     */
    public $meal;

    /**
     * @var string
     * @Type("string")
     */
    public $cabin;

    /**
     * @var boolean
     * @Type("boolean")
     */
    public $smoking;

    /**
     * @var integer
     * @Type("integer")
     */
    public $adultsCount;

    /**
     * @var integer
     * @Type("integer")
     */
    public $kidsCount;

    /**
     * @var string
     * @Type("string")
     */
    public $pets;

    /**
     * @var VehicleExt[]
     * @Type("array<AwardWallet\Schema\Itineraries\VehicleExt>")
     */
    public $vehicles;

    /**
     * @var VehicleExt[]
     * @Type("array<AwardWallet\Schema\Itineraries\VehicleExt>")
     */
    public $trailers;

}