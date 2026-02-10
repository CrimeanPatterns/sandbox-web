<?php
/**
 * Created by PhpStorm.
 * User: APuzakov
 * Date: 31.05.16
 * Time: 17:09
 */

namespace AwardWallet\Common\Itineraries;

use JMS\Serializer\Annotation\Type;

/**
 * Class Cancelled
 * @property $type
 * @property $itineraryType
 * @property $confirmationNumber
 */
class Cancelled extends Itinerary
{
    /**
     * @var string
     * @Type("string")
     */
    protected $itineraryType;

    /**
     * @var string
     * @Type("string")
     */
    protected $confirmationNumber;

}