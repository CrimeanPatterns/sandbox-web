<?php

class TAccountCheckerSheetz extends TAccountChecker
{
    private const REWARDS_PAGE_URL = 'https://orders.sheetz.com/order/information';

    private const LOGIN_PAGE_URL = 'https://orders.sheetz.com/auth/login?destination=/';

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
        $this->http->removeCookies();
        $this->http->GetURL(self::LOGIN_PAGE_URL);

        if ($this->http->Response['code'] !== 200 || !$this->http->FindSingleNode('//title[contains(text(), "Sheetz.com")]')) {
            return $this->checkErrors();
        }

        $data = [
            'username' => $this->AccountFields['Login'],
            'password' => $this->AccountFields['Pass'],
        ];
        $headers = [
            'Accept'       => 'application/json, text/plain, */*',
            'Content-Type' => 'application/json',
        ];
        $this->http->RetryCount = 0;
        $this->http->PostURL('https://orders.sheetz.com/anybff/api/users/login', json_encode($data), $headers);
        $this->http->RetryCount = 2;

        return true;
    }

    public function Login()
    {
        $response = $this->http->JsonLog();
        $message = $response->message ?? null;

        if (isset($response->authorizationToken)) {
            $this->State['authorizationToken'] = $response->authorizationToken;

            return true;
        }

        if ($message == "Incorrect credentials were entered") {
            throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        if ($this->http->currentUrl() != self::REWARDS_PAGE_URL) {
            $this->http->GetURL(self::REWARDS_PAGE_URL);
        }
        // Balance
        $this->SetBalance($this->http->FindSingleNode('//p[@class="spendable-points-value"]'));
        // Name
        $this->SetProperty('Name', beautifulName($this->http->FindSingleNode('//header//p[1]')));
        // Status
        $this->SetProperty('Status', $this->http->FindSingleNode('//header//p[@class="tier-label"]'));
        // Yearly Points
        $this->SetProperty('YearlyPointz', $this->http->FindSingleNode('//p[@class="tier-progress-label"]'));
        // Pointz to the next status
        $this->SetProperty('PointzToTheNextStatus', $this->http->FindSingleNode('//p[@class="tier-maintain-label"]'));

        $this->http->GetURL('https://orders.sheetz.com/account/rewardz');

        //$this->SetProperty('')
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);
        $this->http->RetryCount = 0;
        $this->http->GetURL(self::REWARDS_PAGE_URL, [], 20);
        $this->http->RetryCount = 2;

        if ($this->http->FindSingleNode('//p[normalize-space()="Log Out"][@class="menu-item-label"]')) {
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
