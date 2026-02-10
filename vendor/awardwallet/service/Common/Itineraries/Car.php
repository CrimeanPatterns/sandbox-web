<?php

namespace AwardWallet\Common\Itineraries;

use JMS\Serializer\Annotation\Type;

/**
 * Class RentalCar
 * @property $type
 * @property $model
 * @property $imageUrl
 */
class Car extends LoggerEntity
{

    /**
     * @var string
     * @Type("string")
     */
    protected $type;
    /**
     * @var string
     * @Type("string")
     */
    protected $model;
    /**
     * @var string
     * @Type("string")
     */
    protected $imageUrl;

}