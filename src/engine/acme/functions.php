<?php

use AwardWallet\Engine\ProxyList;

class TAccountCheckerAcme extends TAccountChecker
{
    use ProxyList;
    private const REWARDS_PAGE_URL = 'https://www.acmemarkets.com/customer-account/rewards';

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->SetProxy($this->proxyReCaptcha()); // incapsula issue
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
        $this->http->GetURL('https://www.acmemarkets.com/');

        if ($this->http->Response['code'] !== 200) {
            return $this->checkErrors();
        }

        $data = [
            'username'       => $this->AccountFields['Login'],
            'password'       => $this->AccountFields['Pass'],
        ];
        $headers = [
            'Accept'        => 'application/json, text/plain, */*',
            'Content-Type'  => 'application/json',
        ];

        $this->http->RetryCount = 0;
        $this->http->PostURL('https://albertsons.okta.com/api/v1/authn', json_encode($data), $headers);
        $this->http->RetryCount = 2;

        return true;
    }

    public function Login()
    {
        $response = $this->http->JsonLog();
        $status = $response->status ?? null;

        if ($status !== "SUCCESS") {
            $message = $response->errorSummary ?? null;

            if ($message) {
                $this->logger->error("[Error]: {$message}");

                if ($message == 'Authentication failed') {
                    throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
                }

                $this->DebugInfo = $message;
            }

            return false;
        }

        $sessionToken = $response->sessionToken;
        $this->http->GetURL('https://www.acmemarkets.com/');
        $configs = $this->http->findPreg("/initOktaConfig\('(.*?)'\);/imu");

        if (!$configs) {
            $this->logger->error("config not found");

            return false;
        }

//        $this->logger->debug($configs);
        $configs = $this->http->JsonLog($configs);
        $webClientId = $configs->webClientId;
        $issuerLink = $configs->issuer;

        if (!$webClientId || !$issuerLink) {
            $this->logger->error("config has been changed");

            return false;
        }

        $this->http->GetURL("{$issuerLink}/v1/authorize?client_id={$webClientId}&redirect_uri=https%3A%2F%2Fwww.acmemarkets.com%2Fbin%2Fsafeway%2Funified%2Fsso%2Fauthorize&response_type=code&response_mode=query&state=brown-volleyball-hermon-bill&nonce=MJta35OTaARg4wZ6E88CDk1dBQylsUMNMfiyzJGnN9SWaS4Ya9GBM1BMSERrlDHe&prompt=none&sessionToken={$sessionToken}&scope=openid%20profile%20email%20offline_access%20used_credentials"); // TODO: redirect not working properly

        $headers = [
            "Accept"           => "*/*",
            "X-Requested-With" => "XMLHttpRequest",
            "Referer"          => "https://www.acmemarkets.com/",
        ];

        $this->http->GetURL('https://www.acmemarkets.com/bin/safeway/unified/userinfo?rand=5624&banner=acmemarkets', $headers);
        $response = $this->http->JsonLog();

        if (empty($response->SWY_SHOP_TOKEN)) {
            $this->logger->error("SWY_SHOP_TOKEN not found");

            return false;
        }

        $this->State['access_token'] = $response->SWY_SHOP_TOKEN;

        if ($this->loginSuccessful()) {
            return true;
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        $response = $this->http->JsonLog(null, 0);
        // Name
        $fullName = $response->firstName . " " . $response->lastName;
        $this->SetProperty('Name', beautifulName($fullName));

        $headers = [
            "Accept"                    => "*/*",
            "Authorization"             => "Bearer " . $this->State['access_token'],
            "Ocp-Apim-Subscription-Key" => "9e38e3f1d32a4279a49a264e0831ea46",
            "X-Requested-With"          => "XMLHttpRequest",
            "Referer"                   => "https://www.acmemarkets.com/",
            "x-swy_banner"              => "acmemarkets",
            "x-swy-banner"              => "acmemarkets",
            "x-swy-client-id"           => "web-portal",
        ];

        $this->http->GetURL('https://www.acmemarkets.com/abs/pub/cnc/ucaservice/api/uca/customers/d14bdac0-cd3c-4c7e-ab93-ce3315ecaa6b/rewards', $headers);
        $response = $this->http->JsonLog();
        // Savings to Date
        $this->SetProperty("YearSavings", $response->savings->currentYearSavings);
        // Lifetime Savings
        $this->SetProperty("LifetimeSavings", $response->savings->lifetimeSavings);

        $this->http->GetURL('https://www.acmemarkets.com/content/experience-fragments/www/for_u/loyalty_progress_bar/master.content.html', $headers);
        // Rewards balance
        $this->SetBalance($this->http->FindSingleNode("//span[@class='rewards-balance']"));
        // Points towards your next Reward
        $this->SetProperty("PointsToNextReward", $this->http->FindSingleNode("//span[@class='points-to-next-reward text']", null, true, "/\/\s*([\d]*)/imu"));

        if ($this->Balance > 0) {
            $this->sendNotification("refs #19225 - need to check exp date");
        }
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if (!isset($this->State['access_token'])) {
            return false;
        }

        $headers = [
            "Accept"                    => "*/*",
            "Authorization"             => "Bearer " . $this->State['access_token'],
            "Ocp-Apim-Subscription-Key" => "9e38e3f1d32a4279a49a264e0831ea46",
            "X-Requested-With"          => "XMLHttpRequest",
            "Referer"                   => "https://www.acmemarkets.com/",
            "x-swy_banner"              => "acmemarkets",
            "x-swy-banner"              => "acmemarkets",
            "x-swy-client-id"           => "web-portal",
        ];
        $this->http->GetURL('https://www.acmemarkets.com/abs/pub/cnc/ucaservice/api/uca/customers/d14bdac0-cd3c-4c7e-ab93-ce3315ecaa6b/profile', $headers);
        $response = $this->http->JsonLog();
        $email = $response->emailId ?? null;
        $this->logger->debug("[Email]: {$email}");

        if ($email && strtolower($email) == strtolower($this->AccountFields['Login'])) {
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
