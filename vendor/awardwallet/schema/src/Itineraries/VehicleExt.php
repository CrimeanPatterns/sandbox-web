<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class VehicleExt extends Vehicle
{
    /**
     * @var string
     * @Type("string")
     */
    public $length;
    /**
     * @var string
     * @Type("string")
     */
    public $height;
    /**
     * @var string
     * @Type("string")
     */
    public $width;


}