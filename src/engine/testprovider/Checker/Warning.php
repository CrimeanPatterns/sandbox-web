<?php

namespace AwardWallet\Engine\testprovider\Checker;

use AwardWallet\Engine\testprovider\Success;

class Warning extends Success
{
    public function Parse()
    {
        preg_match("/blah/c", "blah"); // unknown modifier "c"
        $this->http->Log(SOME_MISSING_CONSTANT);
        $this->SetBalance(10);
    }
}
