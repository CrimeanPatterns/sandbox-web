<?php

namespace AwardWallet\ExtensionWorker;

class Credentials {

    private string $login;
    private ?string $login2;
    private ?string $login3;
    private string $password;
    private array $answers;

    public function __construct(string $login, ?string $login2, ?string $login3, string $password, array $answers) {
        $this->login = $login;
        $this->login2 = $login2;
        $this->login3 = $login3;
        $this->password = $password;
        $this->answers = $answers;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function getLogin2(): ?string
    {
        return $this->login2;
    }

    public function getLogin3(): ?string
    {
        return $this->login3;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getAnswers(): array
    {
        return $this->answers;
    }


}
