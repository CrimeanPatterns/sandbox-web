<?php

namespace AwardWallet\Engine\testprovider\Checker;

use AwardWallet\Engine\testprovider\Success;

class Retry extends Success
{
    public function Parse()
    {
        if ($this->AccountFields['Login2'] === "-u") {
            throw new \CheckRetryNeededException();
        }

        throw new \CheckRetryNeededException(2, 20, 'ACCOUNT_LOCKOUT', ACCOUNT_LOCKOUT);
    }
}
