<?php

class TAccountCheckerKeyrewards extends TAccountChecker
{
    public const REWARDS_PAGE_URL = 'https://www.williams-sonoma.com/account/keyrewards.html';

    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams($targetURL);
        $arg['CookieURL'] = 'https://thekeyrewards.com/';

        return $arg;
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
    }

    public function IsLoggedIn()
    {
        if ($this->attempt > 0) {
            return false;
        }

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
        $this->http->removeCookies();
        $this->http->GetURL('https://www.williams-sonoma.com/account/login.html');

        if (!$this->http->ParseForm('loginForm')) {
            return $this->checkErrors();
        }
        $this->http->SetInputValue('email', $this->AccountFields['Login']);
        $this->http->SetInputValue('password', $this->AccountFields['Pass']);

        return true;
    }

    public function Login()
    {
        if (!$this->http->PostForm()) {
            return $this->checkErrors();
        }

        if ($this->loginSuccessful()) {
            return true;
        }

        if ($message = $this->http->FindSingleNode('//li[contains(@class, "message")]', null, true, "/Sorry\, unrecognized email or password/")) {
            throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        $this->sendNotification("keyrewards - refs #16537 New Valid Account ");

        if ($this->http->currentUrl() != self::REWARDS_PAGE_URL) {
            $this->http->GetURL(self::REWARDS_PAGE_URL);
        }
        // get part the Balance
        $balPart1 = $this->http->FindSingleNode('//div[@id="label-progress"]') ?? null;
        $balPart2 = $this->http->FindSingleNode('//div[@id="label-progress-super"]') ?? null;
        // Balance - My Key Rewards
        if (isset($balPart1) && isset($balPart2)) {
            $this->SetBalance($balPart1 . '.' . $balPart2);
        }
        // Name
        $this->http->GetURL('https://www.williams-sonoma.com/account/updateaccount.html?cm_type=lnav');
        $this->SetProperty('Name', beautifulName($this->http->FindSingleNode('//div[@class="main-content account-information"]//div[1]//span[2]')));
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindSingleNode("//a[@id='signOut']")) {
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
