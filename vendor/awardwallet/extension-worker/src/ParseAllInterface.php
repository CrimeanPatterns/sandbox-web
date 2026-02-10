<?php

namespace AwardWallet\ExtensionWorker;

use AwardWallet\Schema\Parser\Component\Master;

interface ParseAllInterface
{

    /**
     * with this interface you could parse all you want in one method: balance, history, trips, etc.
     *
     * @param ParseHistoryOptions|null $historyOptions - you will receive null, if no history is requested
     * @param ParseItinerariesOptions|null $itinerariesOptions - you will receive null, if no itineraries are requested
     */
    public function parseAll(Tab $tab, Master $master, AccountOptions $accountOptions, ?ParseHistoryOptions $historyOptions, ?ParseItinerariesOptions $itinerariesOptions) : void;

}