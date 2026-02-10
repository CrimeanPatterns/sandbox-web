<?php

namespace AwardWallet\Engine\testprovider\Checker;

use AwardWallet\Engine\testprovider\Success;

class Throttled extends Success
{
    public function Parse()
    {
        throw new \ThrottledException(3, null, null, 'Throttled test message');
    }
}
