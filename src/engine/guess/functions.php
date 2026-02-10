<?php

class TAccountCheckerGuess extends TAccountChecker
{
    private const REWARDS_PAGE_URL = 'https://www.guess.com/us/en/guessList/';

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->setDefaultHeader("Accept-Encoding", "gzip, deflate");
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
        $this->http->GetURL('https://www.guess.com/us/en/login/?rurl=1');

        if (!$this->http->ParseForm('login-form')) {
            return $this->checkErrors();
        }

        $this->http->SetInputValue('loginEmail', $this->AccountFields['Login']);
        $this->http->SetInputValue('loginPassword', $this->AccountFields['Pass']);
        $this->http->SetInputValue('loginRememberMe', "on");

        return true;
    }

    public function Login()
    {
        if (!$this->http->PostForm()) {
            return $this->checkErrors();
        }

        $response = $this->http->JsonLog();
        $success = $response->success ?? null;

        $this->logger->debug(">>> Success: {$success}");

        if ($success === true) {
            return $this->loginSuccessful();
        }

        $message = $response->error[0] ?? null;

        if ($message) {
            $this->logger->error("[Error]: {$message}");

            if (strstr($message, 'Invalid login or password. Remember that password is case-sensitive. Please try again.')) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            $this->DebugInfo = $message;

            return false;
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        // Balance - ... / XX points
        $this->SetBalance($this->http->FindSingleNode('//span[contains(@class,"percentage")][not(contains(@class,"d-none"))]/span'));
        // Number
        $this->SetProperty('CardNumber', $this->http->FindSingleNode('//p[contains(@class,"account-banner__cardnumber")]/@data-loyaltynum'));
        // Status
        $this->SetProperty('Status', $this->http->FindSingleNode('//h2[@class="card--info__title"]', null, true, "/Status: ([\w ]+)/ims"));
        // Points to next reward
        $this->SetProperty('PointsTillNextReward', $this->http->FindSingleNode('//div[@class="card--info__description"]/p[2]', null, true, "/^You’re (\d+) points? away from your next/ims"));
        // Reward balance
        $RewardBalance = $this->http->FindSingleNode('//p[@class="card-balance__value"]/span');
        if ($RewardBalance)
        {
            $this->AddSubAccount([
                "Code" => "guessRewardBalance",
                "DisplayName" => "Reward balance",
                "Balance" => $RewardBalance,
            ]);

            $RewardBalance = str_replace('$', '', $RewardBalance);

            if ((float) $RewardBalance > 0.0) {
                $this->logger->debug(">>> RewardBalance: " . $RewardBalance);
                $this->sendNotification("refs #7299 Reward Balance > 0: $RewardBalance");
            }
        } else {
            $this->logger->debug("RewardBalance undefined");
            $this->sendNotification("refs #7299 RewardBalance undefined");
        }

        $ExpirationDate = $this->http->FindSingleNode('//p[contains(text(),"Reward balance")]/span');

        if ($ExpirationDate !== 'XX/XX/XX')
        {
            $this->sendNotification("refs #7299 Exp. date: $ExpirationDate");
        }

        $this->http->RetryCount = 0;
        $this->http->GetURL('https://www.guess.com/us/en/account/?registration=falsea', [], 20);
        $this->http->RetryCount = 2;
        // Name
        $this->SetProperty('Name', beautifulName($this->http->FindSingleNode('//dt[normalize-space()="Name"]/following-sibling::dd')));
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);
        $this->http->RetryCount = 0;
        $this->http->GetURL(self::REWARDS_PAGE_URL, [], 20);
        $this->http->RetryCount = 2;

        if ($this->http->FindSingleNode('//a[normalize-space()="Sign out"][@aria-label="Sign out"]')) {
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
