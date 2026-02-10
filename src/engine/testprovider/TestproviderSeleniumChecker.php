<?php

namespace AwardWallet\Engine\testprovider;

class TAccountCheckerTestproviderSelenium extends \TAccountChecker
{
    use \SeleniumCheckerHelper;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->InitSeleniumBrowser();
        $this->disableImages();
        $this->useChrome();
        $this->KeepState = true;
        $this->keepCookies(false);
        $this->ArchiveLogs = true;
    }

    public function LoadLoginForm()
    {
        $this->http->GetURL("https://translate.google.ru/?vi=c");
        sleep(3);

        return true;
    }

    public function Login()
    {
        // security questions
        if ($this->parseQuestion()) {
            return false;
        }

        return true;
    }

    public function parseQuestion()
    {
        $question = "Please enter 'table'";
        $this->http->Log("Question -> {$question}");
        $this->holdSession();
        // do not keep Advanced Access Code
        unset($this->Answers[$question]);
        $this->AskQuestion($question, null, "AdvancedAccessCode");

        return true;
    }

    public function ProcessStep($step)
    {
        $this->http->Log(__METHOD__);

        if ($this->isNewSession()) {
            return $this->LoadLoginForm() && $this->Login();
        }

        switch ($step) {
            case "AdvancedAccessCode":
                return $this->AdvancedAccessCode($this->Question);

                break;
        }

        return false;
    }

    public function AdvancedAccessCode($question)
    {
        $this->http->Log(__METHOD__);
        $input = $this->waitForElement(\WebDriverBy::id("source"), 10, true);

        if (!$input) {
            $this->logger->error('Failed to find input field for "answer"');

            return false;
        }
        $input->sendKeys($this->Answers[$question]);
        $this->http->Log("Submit form");
        $submitButton = $this->waitForElement(\WebDriverBy::xpath("//input[@id = 'gt-submit']"), 0);

        if (!$submitButton) {
            $this->logger->error('Failed to find submit button');

            return false;
        }
        $submitButton->click();

        return true;
    }

    public function Parse()
    {
        $this->SetBalance(1000);
    }
}
