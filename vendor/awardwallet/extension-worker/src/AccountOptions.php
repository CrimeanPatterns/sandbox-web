<?php

namespace AwardWallet\ExtensionWorker;

class AccountOptions {

    public string $login;
    public ?string $login2;
    public ?string $login3;
    public bool $isMobile;

    public function __construct(
        string $login,
        ?string $login2,
        ?string $login3,
        bool $isMobile
    ) {
        $this->login = $login;
        $this->login2 = $login2;
        $this->login3 = $login3;
        $this->isMobile = $isMobile;
    }
}