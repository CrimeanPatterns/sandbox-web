<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class Ferry extends Transportation
{
    /**
     * @var FerrySegment[]
     * @Type("array<AwardWallet\Schema\Itineraries\FerrySegment>")
     */
    public $segments;

    /**
     * @var ParsedNumber[]
     * @Type("array<AwardWallet\Schema\Itineraries\ParsedNumber>")
     */
    public $ticketNumbers;
}