<?php

class TAccountCheckerObsports extends TAccountChecker
{
    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams($targetURL);
        $arg['CookieURL'] = 'https://www.giftcardandloyalty.com/';

        return $arg;
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
    }

    public function IsLoggedIn()
    {
        if ($this->attempt > 0 || !isset($this->State['sessionid'])) {
            return false;
        }

        $this->http->RetryCount = 0;
        $this->http->GetURL('https://www.giftcardandloyalty.com/myrewards.bootstrap/member_home.asp?merchant=OBSports&sessionid=' . $this->State['sessionid'], [], 20);
        $this->http->RetryCount = 2;

        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL('https://www.giftcardandloyalty.com/myrewards.bootstrap/?merchant=OBSports');

        if (!$this->http->ParseForm('loginForm')) {
            return $this->checkErrors();
        }
        $this->http->SetInputValue('loginId', $this->AccountFields['Login']);
        $this->http->SetInputValue('password', $this->AccountFields['Pass']);
        $this->http->FormURL = 'https://www.giftcardandloyalty.com/myrewards.bootstrap/default.asp?merchant=OBSports&submit=1';

        return true;
    }

    public function Login()
    {
        if (!$this->http->PostForm()) {
            return $this->checkErrors();
        }

        if (
            $this->http->ParseForm(null, 1, true, "//form[@id = 'loginForm' and contains(@action, 'sessionid')]")
        ) {
            if (!$this->http->PostForm()) {
                return $this->checkErrors();
            }

            if ($this->loginSuccessful()) {
                return true;
            }
        } elseif (
            $this->http->ParseForm(null, 1, true, "//form[@id = 'loginForm' and contains(@action, 'merchant=OBSports&submit=1')]")
            && ($message = $this->http->FindSingleNode('//div[not(contains(@style, "hidden"))]/span[@class = "text-danger" and contains(text(), "Access Denied!")]'))
        ) {
            throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        // init session state
        parse_str($this->http->currentUrl(), $output);
        $this->State['sessionid'] = $output['sessionid'] ?? null;
        // Name
        $this->SetProperty('Name', beautifulName($this->http->FindSingleNode('//div[contains(text(), "Name")]/following-sibling::div[1]')));
        // Card Number
        $this->SetProperty('CardNumber', $this->http->FindSingleNode('//div[contains(text(), "Card Number")]/following-sibling::div[1]'));
        // Last Visit Date
        $this->SetProperty('LastVisitDate', $this->http->FindSingleNode('//div[contains(text(), "Last Visit Date")]/following-sibling::div[1]', null, true, '/^([\d]{1,2}\\/[\d]{1,2}\\/[\d]{4})\s/'));
        // Last Visit At
        $this->SetProperty('LastVisitAt', $this->http->FindSingleNode('//div[contains(text(), "Last Visit At")]/following-sibling::div[1]'));
        // Last Spent
        $this->SetProperty('LastSpent', $this->http->FindSingleNode('//div[contains(text(), "Last Spent")]/following-sibling::div[1]', null, true, '#\$\s*[\d.,\-]+#'));
        // Balance - Point Balance
        $this->SetBalance($this->http->FindSingleNode('//div[contains(text(), "Point Balance")]/following-sibling::div[1]'));
        // Rewards Tier
        $this->SetProperty('RewardsTier', $this->http->FindSingleNode('//div[contains(text(), "Rewards Tier")]/following-sibling::div[1]'));
        // Expiry
        $this->SetProperty('MembershipExpiryDate', $this->http->FindSingleNode('//div[contains(text(), "Expiry")]/following-sibling::div[1]'));
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindSingleNode('//a[contains(@href, "logout")]/@href')) {
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
