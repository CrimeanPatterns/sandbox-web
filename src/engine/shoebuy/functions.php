<?php

class TAccountCheckerShoebuy extends TAccountChecker
{
    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams($targetURL);
        $arg['URL'] = 'https://www.shoes.com/login';
        $arg['SuccessURL'] = 'https://www.shoes.com/cust/account';
        $arg['RequestMethod'] = 'POST';
        $arg['PostValues'] = json_encode([
            'isGuest'  => false,
            'email'    => $this->AccountFields['Login'],
            'password' => $this->AccountFields['Pass'],
        ]);

        return $arg;
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->setHttp2(true);
        $this->http->setDefaultHeader('Accept-Encoding', 'gzip, deflate, br');
    }

    public function IsLoggedIn()
    {
        if ($this->attempt > 0) {
            return false;
        }

        $this->http->RetryCount = 0;
        $this->http->GetURL('https://www.shoes.com/cust/account', [], 20);
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
        $this->http->GetURL('https://www.shoes.com/cust/login');

        if (!$this->http->ParseForm('login_form')) {
            return $this->checkErrors();
        }
        $data = [
            'isGuest'  => false,
            'email'    => $this->AccountFields['Login'],
            'password' => $this->AccountFields['Pass'],
        ];
        $headers = [
            'Accept'           => 'application/json, text/javascript, */*; q=0.01',
            'Accept-Encoding'  => 'gzip, deflate, br',
            'Content-Type'     => 'application/vnd.com.shoebuy.v1+json',
            'X-Requested-With' => 'XMLHttpRequest',
        ];
        $this->http->RetryCount = 0;
        $this->http->PostURL('https://www.shoes.com/login', json_encode($data), $headers);
        $this->http->RetryCount = 2;

        return true;
    }

    public function Login()
    {
        if ($this->http->Response['code'] == 403) {
            throw new CheckException('Email and/or password invalid', ACCOUNT_INVALID_PASSWORD);
        }

        if ($this->http->getCookieByName("auth")) {
            $this->http->GetURL('https://www.shoes.com/cust/account');
        }

        if ($this->loginSuccessful()) {
            return true;
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        // Balance - TOTAL POINTS
        $data = $this->http->getCookieByName('session');
        $data = explode('|', urldecode($data));
        $json = $this->http->JsonLog($data[0], false);
        $this->SetBalance($json->rewards);
        // Points to Next Reward
        $this->SetProperty('NextReward', $json->rewardsNext);
        // Name
        $name = $this->http->FindSingleNode('//div[@class="info profile"]/span[1]');
        $name .= ' ' . $this->http->FindSingleNode('//div[@class="info profile"]/span[2]');
        $this->SetProperty('Name', $name);
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindSingleNode('//div[@class="info profile"]')) {
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
