<?php

namespace AwardWallet\Engine\testprovider\Logon;

/**
 * this class will check that passwords equals.
 */
class CheckPassword extends \TAccountChecker
{
    public const EXPECTED_PASSWORD = 'pass-12_<>-"^&;-';

    public function LoadLoginForm()
    {
        return true;
    }

    public function Login()
    {
        if ($this->AccountFields['Pass'] != self::EXPECTED_PASSWORD) {
            throw new \CheckException("Invalid password ({$this->AccountFields['Pass']}), expected: " . self::EXPECTED_PASSWORD, ACCOUNT_INVALID_PASSWORD);
        }

        return true;
    }

    public function Parse()
    {
        $this->SetBalance(1);
    }
}
