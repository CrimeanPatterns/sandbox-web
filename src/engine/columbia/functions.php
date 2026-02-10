<?php

class TAccountCheckerColumbia extends TAccountChecker
{
    private const REWARDS_PAGE_URL = 'https://www.columbia.com/on/demandware.store/Sites-Columbia_US-Site/en_US/Loyalty-Dashboard';

    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams($targetURL);
        $arg['CookieURL'] = 'https://www.columbia.com';

        return $arg;
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
    }

    public function IsLoggedIn()
    {
        $this->http->RetryCount = 0;
        $this->http->GetURL(self::REWARDS_PAGE_URL, [], 20);
        $this->http->RetryCount = 2;

        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        if (filter_var($this->AccountFields['Login'], FILTER_VALIDATE_EMAIL) === false) {
            throw new CheckException('Please enter a valid email address.', ACCOUNT_INVALID_PASSWORD);
        }

        $this->http->removeCookies();
        $this->http->GetURL('https://www.columbia.com/on/demandware.store/Sites-Columbia_US-Site/en_US/Login-Show');

        if (!$this->http->ParseForm('login-form')) {
            return $this->checkErrors();
        }

        $this->http->SetInputValue('loginEmail', $this->AccountFields['Login']);
        $this->http->SetInputValue('loginPassword', $this->AccountFields['Pass']);
        $this->http->SetInputValue('loginRememberMe', "true");

        return true;
    }

    public function Login()
    {
        if (!$this->http->PostForm()) {
            return $this->checkErrors();
        }

        $data = $this->http->JsonLog(null, 3, true);

        if (isset($data['redirectUrl'])) {
            $url = $data['redirectUrl'];
            $this->http->NormalizeURL($url);
            $this->http->GetURL($url);
        }

        if ($this->loginSuccessful()) {
            return true;
        }
        // login or password incorrect
        if (isset($data['error'][0]) && strstr($data['error'][0], 'Invalid login or password') !== false) {
            $this->logger->error("[Error]: {$data['error'][0]}");

            throw new CheckException($data['error'][0], ACCOUNT_INVALID_PASSWORD);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        if ($this->http->currentUrl() != self::REWARDS_PAGE_URL) {
            $this->http->GetURL(self::REWARDS_PAGE_URL);
        }
        // Balance
        $this->SetBalance($this->http->FindPreg('/My Rewards Balance\:\s*\D(\d+)/su'));
        // Name
        $this->SetProperty('Name', beautifulName($this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'Hello')]", null, true, "/Hello\,\s*(\D+)/u")));
        // Spend .... to earn your next $5 reward.
        $this->SetProperty("SpendToNextTier", $this->http->FindPreg('/\"spendToGetReward\"\:\s*\"([\d\.]+)\"/u'));
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindSingleNode("//text()[starts-with(normalize-space(), 'Hello')]")) {
            return true;
        }

        return false;
    }

    private function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        return false;
    }
}
