<?php

namespace AwardWallet\ExtensionWorker;

use AwardWallet\Schema\Parser\Component\Master;

interface ParseItinerariesInterface
{

    public function parseItineraries(Tab $tab, Master $master, AccountOptions $options, ParseItinerariesOptions $parseItinerariesOptions) : void;

}