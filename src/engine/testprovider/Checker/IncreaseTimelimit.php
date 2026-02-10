<?php

namespace AwardWallet\Engine\testprovider\Checker;

use AwardWallet\Engine\testprovider\Success;

class IncreaseTimelimit extends Success
{
    public function Parse()
    {
        sleep(10);
        $this->increaseTimeLimit();
        sleep(10);
        $this->increaseTimeLimit();
        sleep(10);
    }
}
