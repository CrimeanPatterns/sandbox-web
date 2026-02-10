<?php


namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class TicketLink
{

    /**
     * @var string
     * @Type("string")
     */
    public $link;

    /**
     * @var string
     * @Type("string")
     */
    public $name;

}