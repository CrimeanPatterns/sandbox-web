<?php

namespace AwardWallet\Common\Itineraries;

use JMS\Serializer\Annotation\Type;

/**
 * Class CruiseDetails
 * @property $description
 * @property $class
 * @property $deck
 * @property $room
 * @property $ship
 * @property $shipCode
 */
class CruiseDetails extends LoggerEntity
{

    /**
     * @var string
     * @Type("string")
     */
    protected $description;

    /**
     * @var string
     * @Type("string")
     */
    protected $class;

    /**
     * @var string
     * @Type("string")
     */
    protected $deck;

    /**
     * @var string
     * @Type("string")
     */
    protected $room;

    /**
     * @var string
     * @Type("string")
     */
    protected $ship;

    /**
     * @var string
     * @Type("string")
     */
    protected $shipCode;

} 