<?php

namespace AwardWallet\Engine\testprovider\Checker;

use AwardWallet\Engine\testprovider\Success;

class NeverExpiresWithNote extends Success
{
    public function Parse()
    {
        $this->SetBalance(10);
        $this->SetExpirationDateNever();
        $this->SetProperty("AccountExpirationWarning", "do not expire with elite status");
    }
}
