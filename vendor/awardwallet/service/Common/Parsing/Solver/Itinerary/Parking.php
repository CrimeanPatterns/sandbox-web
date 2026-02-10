<?php


namespace AwardWallet\Common\Parsing\Solver\Itinerary;


use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Schema\Parser\Common\Itinerary;

class Parking extends ItinerarySolver
{

    protected function solveItinerary(Itinerary $it, Extra $extra)
    {
        /** @var \AwardWallet\Schema\Parser\Common\Parking $it */
        if ($it->getAddress())
            $this->dh->parseAddress($it->getAddress(), $extra);
    }
}