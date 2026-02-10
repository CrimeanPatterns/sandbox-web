<?php

namespace AwardWallet\Common\Itineraries;

use JMS\Serializer\Annotation\Type;

/**
 * Class Person
 * @property $fullName
 */
class Person extends LoggerEntity
{

    /**
     * @var string
     * @Type("string")
     */
    protected $fullName;

}