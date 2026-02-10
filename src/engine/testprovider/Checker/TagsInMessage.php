<?php

namespace AwardWallet\Engine\testprovider\Checker;

/**
 * this class will check, that we can load and save form state with multiple fields for Curl browser
 * first time (without state) it will create new state (ask question)
 * second time it will check that state is restore correctly.
 *
 * @class MultiField
 *
 * @property \HttpBrowser $http
 */
class TagsInMessage extends \TAccountChecker
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
        $this->ErrorCode = ACCOUNT_INVALID_PASSWORD;
        $this->ErrorMessage = "<font size=22>Hello</font>";

        return false;
    }

    public function Parse()
    {
        if ($this->http->FindPreg("#PASS#ims")) {
            $this->SetBalance(1);
        }
    }

    public function ProcessStep($step)
    {
        if (!$this->http->InputExists('multi[]') || count($this->http->Inputs['multi[]']['values']) != 2) {
            return false;
        }

        if ($this->http->PostForm()) {
            return true;
        }
    }

    private function getBaseUrl()
    {
        return getSymfonyContainer()->getParameter("requires_channel") . '://' . getSymfonyContainer()->getParameter('host');
    }
}
