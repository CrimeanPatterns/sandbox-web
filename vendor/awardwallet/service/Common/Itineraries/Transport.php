<?php

namespace AwardWallet\Common\Itineraries;

use JMS\Serializer\Annotation\Type;

/**
 * Class Transport
 * @property string $type
 * @property string $name
 * @property string $vehicleClass
 */
class Transport extends LoggerEntity {

    /**
     * @var string
     * @Type("string")
     */
    protected $type;
    /**
     * @var string
     * @Type("string")
     */
    protected $name;
    /**
     * @var string
     * @Type("string")
     */
    protected $vehicleClass;

}