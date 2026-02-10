<?php

namespace AwardWallet\Engine\testprovider\Checker;

class V8 extends \TAccountChecker
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
        return true;
    }

    public function Parse()
    {
        $this->SetBalance(100);
        $v8 = new \V8Js();
        $this->SetProperty("Name", $v8->executeString("len = 'Hello' + ' ' + 'World!';", 'basic.js'));

        return true;
    }
}
