<?php

namespace AwardWallet\Common\Parsing\Exception;

class NotAMemberException extends \CheckException
{

    public function __construct()
    {
        parent::__construct("You are not a member of this loyalty program.", ACCOUNT_PROVIDER_ERROR);
    }

}