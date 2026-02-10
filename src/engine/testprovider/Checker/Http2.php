<?php

namespace AwardWallet\Engine\testprovider\Checker;

class Http2 extends \TAccountChecker
{
    public function IsLoggedIn()
    {
        return false;
    }

    public function LoadLoginForm()
    {
        return true;
    }

    public function Login()
    {
        $this->http->setHttp2(true);
        $this->http->GetURL("https://www.tunetheweb.com/performance-test/");

        return true;
    }

    public function Parse()
    {
        $this->SetBalance(100);
    }
}
