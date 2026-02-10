<?php

class TAccountCheckerAlamocinema extends TAccountChecker
{
    private $response;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->setHttp2(true);
        $this->http->setDefaultHeader('Accept-Encoding', 'gzip, deflate, br');
    }

    public function IsLoggedIn()
    {
        return false;
    }

    public function LoadLoginForm()
    {
        // Login must be correct email
        if (filter_var($this->AccountFields['Login'], FILTER_VALIDATE_EMAIL) === false) {
            throw new CheckException('There is not a user that matches this email/password.', ACCOUNT_INVALID_PASSWORD);
        }

        $this->http->removeCookies();
        $this->http->GetURL('https://drafthouse.com/victory/sign-in');

        $data = [
            'email'         => $this->AccountFields['Login'],
            'password'      => $this->AccountFields['Pass'],
            'userSessionId' => md5($this->AccountFields['Login']),
        ];
        $headers = [
            'Accept'       => 'application/json, text/plain, */*',
            'Content-Type' => 'application/json;charset=utf-8',
        ];
        $this->http->PostURL('https://drafthouse.com/s/mother/v1/auth/login/email-password', json_encode($data),
            $headers);

        return true;
    }

    public function Login()
    {
        $this->response = $this->http->JsonLog();

        if (isset($this->response->error->errorCode->code) && $this->response->error->errorCode->code == 401) {
            throw new CheckException('There is not a user that matches this email/password.', ACCOUNT_INVALID_PASSWORD);
        }

        if (!isset($this->response->data->loyaltyMember->email)
            || $this->response->data->loyaltyMember->email !== $this->AccountFields['Login']) {
            return false;
        }

        if (isset($this->response->data->loginSuccess) && $this->response->data->loginSuccess == 'true') {
            return true;
        }

        return false;
    }

    public function Parse()
    {
        // Balance - CURRENT VISITS
        $this->SetBalance($this->response->data->loyaltyMember->currentPoints ?? null);
        // LIFETIME VISITS
        $this->SetProperty('LifetimeVisits', $this->response->data->loyaltyMember->lifetimePoints ?? null);
        // Name
        $this->SetProperty('Name', $this->response->data->loyaltyMember->fullName ? beautifulName($this->response->data->loyaltyMember->fullName) : null);
        // VICTORY REWARDS
        $this->SetProperty('Rewards', $this->response->data->loyaltyMember->rewards ? count($this->response->data->loyaltyMember->rewards) : null);
        // VICTORY LEVEL
        $this->SetProperty('Level', $this->response->data->loyaltyMember->currentVictoryLevel ?? null);
        // Card Number
        $this->SetProperty('CardNumber', $this->response->data->loyaltyMember->cardNumber ?? null);
        // Visits Until Next Victory Level
        $this->SetProperty('visitsUntilNextVictoryLevel', $this->response->data->loyaltyMember->visitsUntilNextVictoryLevel ?? null);
        // Rewards
        foreach ($this->response->data->loyaltyMember->rewards as $reward) {
            $this->AddSubAccount([
                "Code"           => "rewardAlamoCinema" . $reward->recognitionId,
                "DisplayName"    => $reward->name,
                "Balance"        => null,
                'ExpirationDate' => strtotime($reward->expiresDateTimeUtc),
            ]);
        }
    }
}
