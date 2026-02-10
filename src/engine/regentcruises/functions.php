<?php

class TAccountCheckerRegentcruises extends TAccountChecker
{
    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams($targetURL);
        $arg['CookieURL'] = 'https://www.rssc.com/myaccountlogin.aspx?ReturnUrl=%2fmyaccount%2f';

        return $arg;
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->setDefaultHeader('Accept-Encoding', 'gzip, deflate, br');
    }

    public function IsLoggedIn()
    {
        if ($this->attempt > 0) {
            return false;
        }

        $this->http->RetryCount = 0;
        $this->http->GetURL('https://www.rssc.com/myaccount/', [], 20);
        $this->http->RetryCount = 2;

        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        // Login must be correct email
        if (filter_var($this->AccountFields['Login'], FILTER_VALIDATE_EMAIL) === false) {
            throw new CheckException('Invalid e-mail and/or password', ACCOUNT_INVALID_PASSWORD);
        }

        $this->http->removeCookies();
        $this->http->GetURL('https://www.rssc.com/myaccountlogin.aspx?ReturnUrl=%2fmyaccount%2f');

        if (!$this->http->ParseForm('form1')) {
            return $this->checkErrors();
        }
        $this->http->SetInputValue('uxMal$uxRegister$uxLoginEmailAddressTextbox', $this->AccountFields['Login']);
        $this->http->SetInputValue('uxMal$uxRegister$uxLoginPasswordTextbox', $this->AccountFields['Pass']);
        $this->http->SetInputValue('uxMal$uxRegister$uxRememberMeCheckbox', 'on');
        $this->http->SetInputValue('__EVENTTARGET', 'uxMal$uxRegister$uxLoginButton');

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

        if ($message = $this->http->FindSingleNode('//p[contains(text(), "Email and/or password invalid")]')) {
            throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        // Balance - Nights cruised
        $this->SetBalance($this->http->FindSingleNode('//div[@id="cruiseInfo"]//div[@class="myaccountSideboxInside"]//td[contains(., "Nights cruised:")]/following-sibling::td[1]'));

        // Name
        $name = beautifulName($this->http->FindSingleNode('(//div[@id="cruiseInfo"]//div[@class="myaccountSideboxInside"]/p)[1]'));
        $name = str_replace(':', '', $name);
        $this->SetProperty('Name', $name);

        // Tier Level
        $this->SetProperty('TierLevel', $this->http->FindSingleNode('//div[@id="cruiseInfo"]//div[@class="myaccountSideboxInside"]//td[contains(., "Tier Level:")]/following-sibling::td[1]'));

        // Reward Nights
        $this->SetProperty('RewardNights', $this->http->FindSingleNode('//div[@id="cruiseInfo"]//div[@class="myaccountSideboxInside"]//td[contains(., "Reward Nights:")]/following-sibling::td[1]'));

        // Nights until next level
        $this->SetProperty('NightsUntilNextLevel', $this->http->FindSingleNode('//div[@id="cruiseInfo"]//div[@class="myaccountSideboxInside"]//td[contains(., "Nights until next level:")]/following-sibling::td[1]'));

        // Recently Saved Cruises
        if (!empty($this->http->FindNodes('//div[@id="contentResultlist"]//td'))) {
            $this->sendNotification("refs #12319: Need to add Recently Saved Cruises // IV");
        }

        // All saved cruises
        $this->http->GetURL('https://www.rssc.com/myaccount/savedcruises.aspx');
        $text = 'You currently have no saved cruises. Please use the cruise finder to find cruises you are interested in and use the Save Cruise link to add them to this list.';

        if ($this->http->FindSingleNode('//div[@id="uxCruiseResults"]') !== $text) {
            $this->sendNotification("refs #12319: Need to add All Saved Cruises // IV");
        }
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindSingleNode('//div[@id="cruiseInfo"]//div[@class="myaccountSideboxInside"]//td[contains(text(), "Nights cruised:")]')) {
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
