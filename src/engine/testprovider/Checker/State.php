<?php

namespace AwardWallet\Engine\testprovider\Checker;

use AwardWallet\Engine\testprovider\TestHelper;

/**
 * this class will check, that we can load and save state for Curl browser
 * if password is "log-me-in" it will create new state (login to aw)
 * if password is "check-logged-in" - it will check loaded state (try to restore aw session).
 */
class State extends \TAccountChecker
{
    use TestHelper;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->ArchiveLogs = true;
        $this->http->LogHeaders = true;
        $this->UseCurlBrowser();
    }

    public function IsLoggedIn()
    {
        $cookie = $this->http->getCookieByName("SomeSessionCookie");

        if ($cookie == "SomeValue") {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        if ($this->AccountFields['Pass'] == 'check-logged-in') {
            return false;
        }

        return true;
    }

    public function Login()
    {
        $this->http->setCookie("SomeSessionCookie", "SomeValue");

        return true;
    }

    public function Parse()
    {
        if ($this->http->getCookieByName("SomeSessionCookie") == "SomeValue") {
            $this->SetBalance(1);
        }
    }
}
