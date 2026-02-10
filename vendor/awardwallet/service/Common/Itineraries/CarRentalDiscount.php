<?php

namespace AwardWallet\Common\Itineraries;

use JMS\Serializer\Annotation\Type;

/**
 * Class CarRentalDiscount
 * @property $name
 * @property $code
 */
class CarRentalDiscount extends LoggerEntity
{
    /**
     * @var string
     * @Type("string")
     */
    protected $name;

    /**
     * @var string
     * @Type("string")
     */
    protected $code;

}