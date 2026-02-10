<?php

namespace AwardWallet\Engine\testprovider\Logon;

use AwardWallet\Engine\testprovider\Success;

class OAuthRefresh extends Success
{
    public function Parse()
    {
        $this->InvalidAnswers["NewAuthInfo"] = "SomeNewAuthInfo";
        $this->SetBalance(1);
    }
}
