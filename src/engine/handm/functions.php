<?php

class TAccountCheckerHandm extends TAccountChecker
{
    use SeleniumCheckerHelper;

    private const REWARDS_PAGE_URL = 'https://www2.hm.com/en_us/index.html';

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->setHttp2(true);
        $this->http->setUserAgent(\HttpBrowser::PROXY_USER_AGENT);
        $this->http->RetryCount = 0;
    }

    public function IsLoggedIn()
    {
        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL(self::REWARDS_PAGE_URL);

        if (!$this->http->FindSingleNode('//title[contains(text(), "H&M")]') || $this->http->Response['code'] != 200) {
            return $this->checkErrors();
        }

        $this->getAbckCookie();

        $headers = [
            'accept'       => 'application/json',
            'content-type' => 'application/x-www-form-urlencoded',
        ];
        $data = [
            'j_username'                   => $this->AccountFields['Login'],
            'j_password'                   => $this->AccountFields['Pass'],
            'asyncCall'                    => 'true',
            '_spring_security_remember_me' => 'on',
        ];
        $this->http->PostURL('https://www2.hm.com/en_us/j_spring_security_check', $data, $headers);

        return true;
    }

    public function Login()
    {
        $authFailed = $this->http->Response['headers']['x-validation-failure'] ?? null && $this->http->Response['code'] == 401;

        if ($authFailed) {
            throw new CheckException('Wrong email or password, please try again', ACCOUNT_INVALID_PASSWORD);
        }

        if (!strstr($this->http->currentUrl(), 'j_spring_security_check') && $this->loginSuccessful()) {
            return true;
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        $userData = $this->http->JsonLog(null, 0, true);
        // Name
        $this->SetProperty('Name', beautifulName($userData['displayName'] ?? null));
        // Elite level
        $this->SetProperty('EliteLevel', $userData['currentTierCopy'] ?? null);
        // Balance - points
        $this->SetBalance($userData['pointsBalance'] ?? null);
        // Renewal date
        $this->SetProperty('YourMembershipWillBeRenewedOn', strtotime($userData['pointsExpirationDate'] ?? null) ?? null);

        $progressText = $userData['mainMembershipCardCopy'] ?? null;
        $progressNextReward = $this->http->FindPreg('<^You’re+\s+([0-9]+)>', false, $progressText);
        $progressNextEliteStatus = $this->http->FindPreg('<and+\s+([0-9]+)>', false, $progressText);
        // Points till next reward
        $this->SetProperty('NextReward', $progressNextReward);
        // Points till next elite status
        $this->SetProperty('NextLevel', $progressNextEliteStatus);
        // Number
        $this->SetProperty('Number', $userData['customerLoyaltyId'] ?? null);

        $this->http->GetURL('https://www2.hm.com/en_us/v2/user/points-history');
        $pointsHistoryData = $this->http->JsonLog(null, 3, true);
        $totalNbrOfTransactions = $pointsHistoryData['totalNbrOfTransactions'] ?? null;

        if ($totalNbrOfTransactions) {
            $this->sendNotification("refs #22114 Account with transactions found // IZ");
        }

        $this->http->GetURL('https://www2.hm.com/en_us/v1/offersProposition');
        $offerKeysData = $this->http->JsonLog(null, 3, true);

        foreach ($offerKeysData as $okd) {
            $this->http->GetURL('https://www2.hm.com/en_us/member/memberOffers.v1.json?offerKeys=' . $okd['offerKey']);
            $offerData = $this->http->JsonLog(null, 3, true);
            $offerDataPrepared = $offerData[0] ?? null;
            $this->AddSubAccount([
                "Code"           => $okd['offerPropositionId'] ?? null,
                "DisplayName"    => $offerDataPrepared['headline'] ?? null,
                "Balance"        => null,
                "ExpirationDate" => strtotime($okd['endDateTime'] ?? null) ?? null,
            ]);
        }
    }

    private function getAbckCookie()
    {
        $key = 'handm_abck';
        $result = Cache::getInstance()->get($key);

        if (!empty($result) && $this->attempt == 0) {
            $this->logger->debug("set _abck from cache: {$result}");
            $this->http->setCookie("_abck", $result, ".hm.com");

            return;
        }

        try {
            $this->logger->notice(__METHOD__);
            $selenium = clone $this;
            $this->http->brotherBrowser($selenium->http);

            $this->logger->notice("Running Selenium...");
            $selenium->UseSelenium();

            $selenium->useGoogleChrome(\SeleniumFinderRequest::CHROME_99);

            $selenium->http->start();
            $selenium->Start();

            $selenium->http->removeCookies();
            $selenium->http->GetURL('https://www2.hm.com/en_us/login');

            $loginField = $selenium->waitForElement(WebDriverBy::xpath('//input[@id="email"]'), 15);
            $passwordField = $selenium->waitForElement(WebDriverBy::xpath('//input[@id="password"]'), 0);

            $cookiesAcceptBtn = $selenium->waitForElement(WebDriverBy::xpath('//button[@id="onetrust-accept-btn-handler"]'), 0);

            if ($cookiesAcceptBtn) {
                $cookiesAcceptBtn->click();
            }

            sleep(1);
            $loginField->click();
            sleep(1);
            $passwordField->click();
            sleep(1);

            $cookies = $selenium->driver->manage()->getCookies();

            foreach ($cookies as $cookie) {
                if (
                    !in_array($cookie['name'], [
                        '_abck',
                    ])
                ) {
                    continue;
                }

                $result = $cookie['value'];
                $this->logger->debug("set new _abck: {$result}");
                Cache::getInstance()->set($key, $cookie['value'], 60 * 60);

                $this->http->setCookie("_abck", $result, ".hm.com");
            }
        } finally {
            $this->logger->debug("close Selenium browser");
            $selenium->http->cleanup();
        }
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);
        $this->http->GetURL('https://www2.hm.com/en_us/v2/user', [
            'accept'       => 'application/json',
            'content-type' => 'application/json',
        ]);

        if ($this->http->Response['code'] != 200) {
            return false;
        }

        $log = $this->http->JsonLog(null, 3, true);
        $email = $log['email'] ?? null;
        $this->logger->debug("[Email]: {$email}");

        if (strtolower($email) == strtolower($this->AccountFields['Login'])) {
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
