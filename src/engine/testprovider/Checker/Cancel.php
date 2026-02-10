<?php

namespace AwardWallet\Engine\testprovider\Checker;

use AwardWallet\Engine\testprovider\Success;

class Cancel extends Success
{
    public function Parse()
    {
        throw new \CancelCheckException();
    }
}
