<?php

class TAccountCheckerCrewards extends TAccountChecker
{
    public const REWARDS_PAGE_URL = 'https://www.cashrewards.com.au/rewards';

    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams($targetURL);
        $arg['CookieURL'] = 'http://www.cashrewards.com.au/shop';

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
        $success = $this->loginSuccessful();
        $this->http->RetryCount = 2;

        if ($success) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL(self::REWARDS_PAGE_URL);

        if (!$this->http->ParseForm('login')) {
            return $this->checkErrors();
        }
        $this->http->SetInputValue('EmailAddress', $this->AccountFields['Login']);
        $this->http->SetInputValue('Password', $this->AccountFields['Pass']);
        $this->http->SetInputValue('keepSignIn', true);
        $this->http->SetInputValue('reCaptchaToken', '');
        $this->http->unsetInputValue('checkbox');

        return true;
    }

    public function Login()
    {
        $url = 'https://www.cashrewards.com.au/Account/VerifyMember';
        //set headers
        $headers["Content-Type"] = 'application/json;charset=utf-8';
        $headers["Accept"] = 'application/json, text/plain, */*';
        //send post
        $this->http->PostURL($url, json_encode($this->http->Form), $headers);

        $response = $this->http->JsonLog(null, true, true);

        $success = $response['success'] ?? null;

        if ($success && $this->loginSuccessful()) {
            return true;
        }

        $message = $response['errors'][0]['message'] ?? null;

        if (in_array($message, [
            'Invalid Credentials',
            'Enter your Email Address',
            'Enter your Password',
        ])) {
            throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        //Available Rewards
        $this->SetBalance($this->http->FindSingleNode("//div[@class='content']//p[@class='availble-normal-txt']", null, true, "/([$\.0-9]+).*/"));
        // Rewards Balance
        $this->SetProperty("RewardsBalance", $this->http->FindSingleNode("//div[@class='content']//p[@class='balancetxt myrewards-price-txt']"));
        //Lifetime Rewards
        $this->SetProperty("LifetimeRewards", $this->http->FindSingleNode("//div[@class='content']//span[@class='lifetime-rewards-amount']"));

        //change current page
        $this->http->GetURL('https://www.cashrewards.com.au/settings');
        //get first name
        $FullName = $this->http->FindSingleNode("//input[@id='firstName']/@value");
        //get last name
        $FullName .= ' ' . $this->http->FindSingleNode("//input[@id='lastName']/@value");
        //Full Name
        $this->SetProperty("FullName", beautifulName($FullName));
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);
        $this->http->GetURL(self::REWARDS_PAGE_URL, [], 20);

        if ($this->http->FindSingleNode("//a[@href='/logout']")) {
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
