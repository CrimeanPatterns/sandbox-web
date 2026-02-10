<?php

namespace AwardWallet\ExtensionWorker;

class SelectParserRequest {

    private ?string $login2;
    private ?string $login3;

    public function __construct(?string $login2, ?string $login3) {
        $this->login2 = $login2;
        $this->login3 = $login3;
    }

    public function getLogin2(): ?string
    {
        return $this->login2;
    }

    public function getLogin3(): ?string
    {
        return $this->login3;
    }

}
