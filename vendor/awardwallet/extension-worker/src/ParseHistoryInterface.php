<?php

namespace AwardWallet\ExtensionWorker;

use AwardWallet\Schema\Parser\Component\Master;

interface ParseHistoryInterface
{

    public function parseHistory(Tab $tab, Master $master, AccountOptions $accountOptions, ParseHistoryOptions $historyOptions) : void;

}