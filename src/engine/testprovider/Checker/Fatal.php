<?php

namespace AwardWallet\Engine\testprovider\Checker;

use AwardWallet\Engine\testprovider\Success;

class Fatal extends Success
{
    public function Parse()
    {
        ffff(); // unknown method call
    }
}
