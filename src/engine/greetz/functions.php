<?php

class TAccountCheckerGreetz extends TAccountChecker
{
    private $gToken;
    private $userData;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->setHttp2(true);
        $this->http->setDefaultHeader('Accept-Encoding', 'gzip, deflate, br');
        $this->http->RetryCount = 0;
    }

    public function IsLoggedIn()
    {
        if ($this->attempt > 0) {
            return false;
        }

        $this->gToken = $this->http->getCookieByName('gToken');

        if ($this->gToken == null) {
            return false;
        }

        if (!$result = $this->checkToken()) {
            return false;
        }

        if (!$this->getUserData()) {
            return false;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        // Login must be correct email
        if (filter_var($this->AccountFields['Login'], FILTER_VALIDATE_EMAIL) === false) {
            throw new CheckException('De combinatie van het E-mailadres en wachtwoord is niet juist.', ACCOUNT_INVALID_PASSWORD);
        }

        $this->http->removeCookies();

        if (!$this->http->GetURL('https://www.greetz.nl/service/api/oauth/token?client_id=webmobile&grant_type=anonymous')) {
            return false;
        }

        $result = $this->http->JsonLog(null, false);

        if (empty($result->access_token)) {
            return false;
        }

        if (!$this->setToken($result)) {
            return false;
        }

        $this->http->GetURL('https://www.greetz.nl/auth/login');

        $data = [
            'username'      => $this->AccountFields['Login'],
            'password'      => $this->AccountFields['Pass'],
            'grant_type'    => 'password',
            'client_secret' => 'Hf65D2%kL1',
            'client_id'     => 'webmobile',
        ];
        $headers = [
            'Accept'        => 'application/json, text/plain, */*',
            'Content-Type'  => 'application/x-www-form-urlencoded',
            'Authorization' => 'OAuth ' . $result->access_token,
        ];

        $this->http->PostURL('https://www.greetz.nl/service/api/oauth/token', $data, $headers);

        return true;
    }

    public function Login()
    {
        $result = $this->http->JsonLog(null, false);

        if (!isset($result->status)) {
            return false;
        }

        if ($result->status == 401) {
            throw new CheckException('De combinatie van het E-mailadres en wachtwoord is niet juist', ACCOUNT_INVALID_PASSWORD);
        }

        if (!$this->setToken($result)) {
            return false;
        }

        if (!$this->checkToken()) {
            return false;
        }

        if (!$this->getUserData()) {
            return false;
        }

        return true;
    }

    public function Parse()
    {
        // Balance -
        $this->SetBalance($this->userData->nrOfLoyaltyPoints);
        // Name
        $name = !empty($this->userData->firstName) ? $this->userData->firstName : '';
        $name .= ' ' . (!empty($this->userData->lastName) ? $this->userData->lastName : '');
        $this->SetProperty('Name', beautifulName(trim($name)));
        // Greetz Tegoed
        if (!empty($this->userData->walletAmount)) {
            $this->AddSubAccount([
                "Code"        => "greetzTegoed",
                "DisplayName" => "Greetz Tegoed",
                "Balance"     => $this->userData->walletAmount,
            ]);
        }
    }

    public static function FormatBalance($fields, $properties)
    {
        if (isset($properties['SubAccountCode']) && strstr($properties['SubAccountCode'], 'greetzTegoed')) {
            return formatFullBalance($fields['Balance'], $fields['ProviderCode'], "€%0.2f");
        }

        return parent::FormatBalance($fields, $properties);
    }

    private function setToken($token)
    {
        $this->http->setCookie('gToken', base64_encode(json_encode($token)));

        $this->gToken = $this->http->getCookieByName('gToken');

        if ($this->gToken == null) {
            return false;
        }

        return true;
    }

    private function checkToken()
    {
        $result = $this->http->JsonLog(base64_decode($this->gToken), false);

        if (empty($result->access_token)) {
            return false;
        }

        return $result;
    }

    private function getUserData()
    {
        $result = $this->http->JsonLog(base64_decode($this->gToken), false);
        $this->http->GetURL('https://www.greetz.nl/service/api/profile',
            ['Authorization' => 'OAuth ' . $result->access_token]);
        $this->userData = $this->http->JsonLog(null, false);

        if (isset($this->userData->nrOfLoyaltyPoints)) {
            return true;
        }

        return false;
    }
}
