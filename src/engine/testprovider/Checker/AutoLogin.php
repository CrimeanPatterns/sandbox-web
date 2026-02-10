<?php

namespace AwardWallet\Engine\testprovider\Checker;

class AutoLogin extends \TAccountChecker
{
    public const RedirectURL = "RedirectLoginURL";

    public function LoadLoginForm()
    {
        $this->http->FormURL = sha1($this->AccountFields['Login'] . $this->AccountFields['Pass']);
        $this->http->Form = [
            'login'    => $this->AccountFields['Login'],
            'password' => $this->AccountFields['Pass'],
        ];

        if ($this->AccountFields['Pass'] == 'valid-autologin') { //'invalid-autologin'
            return true;
        }

        return false;
    }

    public function UpdateGetRedirectParams(&$arg)
    {
        $arg["RedirectURL"] = 'http://' . self::RedirectURL;
    }
}
