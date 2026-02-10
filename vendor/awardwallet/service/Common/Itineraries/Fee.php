<?php

namespace AwardWallet\Common\Itineraries;

use JMS\Serializer\Annotation\Type;

/**
 * Class Fee
 * @property $name
 * @property $charge
 */
class Fee extends LoggerEntity
{

    /**
     * @var string
     * @Type("string")
     */
    protected $name;

    /**
     * @var double
     * @Type("double")
     */
    protected $charge;

}