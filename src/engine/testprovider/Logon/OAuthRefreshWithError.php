<?php

namespace AwardWallet\Engine\testprovider\Logon;

use AwardWallet\Engine\testprovider\Success;

class OAuthRefreshWithError extends Success
{
    public function Parse()
    {
        $this->InvalidAnswers["NewAuthInfo"] = "none";

        throw new \CheckException("Invalid oauth logon", ACCOUNT_INVALID_PASSWORD);
        // return unknown error
    }
}
