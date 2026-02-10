<?php

namespace AwardWallet\ExtensionWorker;

class LoginWithIdResult
{

    public LoginResult $loginResult;
    public string $loginId;
    public Tab $tab;

    public function __construct(LoginResult $loginResult, string $loginId, Tab $tab)
    {

        $this->loginResult = $loginResult;
        $this->loginId = $loginId;
        $this->tab = $tab;
    }

}