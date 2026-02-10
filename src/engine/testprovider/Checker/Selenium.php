<?php

namespace AwardWallet\Engine\testprovider\Checker;

class Selenium extends \TAccountChecker
{
    use \SeleniumCheckerHelper;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->InitSeleniumBrowser();
        $this->ArchiveLogs = true;
        $this->KeepState = true;
    }

    public function LoadLoginForm()
    {
        return true;
    }

    public function Login()
    {
        if (!isset($this->Answers['Norris name?'])) {
            $this->AskQuestion("Norris name?");

            return false;
        }
        $this->holdSession();

        return true;
    }

    public function Parse()
    {
        $this->SetBalance(10);

        return true;
    }
}
