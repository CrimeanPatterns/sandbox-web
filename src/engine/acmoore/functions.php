<?php

class TAccountCheckerAcmoore extends TAccountChecker
{
    public const REWARDS_PAGE_URL = 'https://acmoore.com/loyaltyprogram?mobile=true';

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
        $this->http->GetURL(self::REWARDS_PAGE_URL, [], 20);
        $this->http->RetryCount = 2;
        $response = $this->http->JsonLog();

        if (isset($response->crm_data->customer_no, $response->crm_data->first_name, $response->crm_data->rewards_points)) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL("https://acmoore.com/account/login");

        if ($this->http->Response["code"] != 200) {
            return $this->checkErrors();
        }
        $data = [
            "email"    => $this->AccountFields['Login'],
            "password" => $this->AccountFields['Pass'],
        ];
        $headers = [
            "Accept"       => "application/json, text/plain, */*",
            "Content-Type" => "application/json;charset=utf-8",
        ];
        $this->http->PostURL("https://acmoore.com/sessions", json_encode($data), $headers);

        return true;
    }

    public function checkErrors()
    {
        $this->logger->notice(__METHOD__);
        //# System maintenance
        if ($message = $this->http->FindSingleNode("//span[contains(text(),'we are currently performing system maintenance')]")) {
            throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }

        return false;
    }

    public function Login()
    {
        $response = $this->http->JsonLog();
        $status = $response->status ?? null;
        $result = $response->result ?? null;

        if ($status || $result) {
            return true;
        }
        $message = $response->message ?? null;

        if ($message == "Your password has expired. Please reset it to login.") {
            throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        if ($message == 'Invalid email or password, please try again.') {
            throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        if ($message == 'Username or password incorrect') {
            throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        if ($this->http->currentUrl() != self::REWARDS_PAGE_URL) {
            $this->http->GetURL(self::REWARDS_PAGE_URL);
        }
        $response = $this->http->JsonLog();
        // Name
        if (isset($response->crm_data->first_name, $response->crm_data->last_name)) {
            $this->SetProperty("Name", beautifulName("{$response->crm_data->first_name} {$response->crm_data->last_name}"));
        }
        // Customer No
        $this->SetProperty("AccountNumber", $response->crm_data->customer_no ?? null);
        // Balance - Point Balance
        $this->SetBalance($response->crm_data->rewards_points ?? null);
    }
}
