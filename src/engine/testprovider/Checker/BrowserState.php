<?php

namespace AwardWallet\Engine\testprovider\Checker;

/**
 * this class will check, that we can load and save state for Curl browser.
 */
class BrowserState extends \TAccountChecker
{
    protected $validAnswer = [
        'question' => 'Why?',
        'answer'   => 'Because!',
    ];

    protected $cookie = [
        'name'  => 'browser_state_check_cookie',
        'value' => '100500',
    ];

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->ArchiveLogs = true;
        $this->UseCurlBrowser();
    }

    public function LoadLoginForm()
    {
        return true;
    }

    public function Login()
    {
        if (!empty($this->Answers)) {
            throw new \CheckException('Missing step', ACCOUNT_INVALID_PASSWORD);
        }

        $this->http->setCookie($this->cookie['name'], $this->cookie['value']);

        $this->Question = $this->validAnswer['question'];
        $this->ErrorCode = ACCOUNT_QUESTION;
        $this->Step = 'Question';
        $this->State["Test"] = true;

        return false;
    }

    public function Parse()
    {
        $this->SetBalance(198);
    }

    public function ProcessStep($step)
    {
        $this->http->log("ProcessStep: $step");

        if ($this->http->getCookieByName($this->cookie['name']) !== $this->cookie['value']) {
            throw new \CheckException('Cookie missing', ACCOUNT_INVALID_PASSWORD);
        }

        if (empty($this->State["Test"])) {
            throw new \CheckException('State variable missing', ACCOUNT_INVALID_PASSWORD);
        }

        if ($this->Answers[$this->Question] === $this->validAnswer['answer']) {
            return true;
        }

        throw new \CheckException('Question missing', ACCOUNT_INVALID_PASSWORD);
    }
}
