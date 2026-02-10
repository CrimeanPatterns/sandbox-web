<?php

namespace AwardWallet\Common\Parsing\Exception;

class ProfileUpdateException extends \CheckException
{

    public function __construct()
    {
        parent::__construct("%DISPLAY_NAME% website is asking you to update your profile, until you do so we would not be able to retrieve your account information.", ACCOUNT_PROVIDER_ERROR);
    }

}