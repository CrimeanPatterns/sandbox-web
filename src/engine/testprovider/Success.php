<?php

namespace AwardWallet\Engine\testprovider;

class Success extends \TAccountChecker
{
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
        $this->SetBalance(10);
    }

    protected function clipSecondsFromTimeStamp(int $timeStamp): int
    {
        return $timeStamp - $timeStamp % 60;
    }
}
