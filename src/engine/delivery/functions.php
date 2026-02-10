<?php

class TAccountCheckerDelivery extends TAccountChecker
{
    public const REWARDS_PAGE_URL = 'https://www.delivery.com/';

    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams($targetURL);
        $arg['CookieURL'] = 'https://www.delivery.com/';

        return $arg;
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
    }

    public function IsLoggedIn()
    {
        if ($this->attempt > 0 || !isset($this->State['client_id']) || !isset($this->State['access_token'])) {
            return false;
        }

        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL(self::REWARDS_PAGE_URL);

        if (!$this->http->Response['code'] == 200) {
            return $this->checkErrors();
        }
        $urlJS = $this->http->FindPreg('/(scripts\\/[\d]{0,3}\.[\d]{0,3}\.[\d]{0,3}\.app-bundle\.js\?.*)"/');

        if (!$urlJS) {
            return $this->checkErrors();
        }
        $this->http->NormalizeURL($urlJS);
        $this->http->GetURL($urlJS);
        $client_id = $this->http->FindPreg('/;break;default:r="([a-zA-Z0-9]*)"\,a="/');
        $client_secret = $this->http->FindPreg('/default:r="' . $client_id . '"\,a="([a-zA-Z0-9]*)"\,e="web"/');

        if (!isset($client_id) && !isset($client_secret)) {
            return $this->checkErrors();
        }
        $this->State['client_id'] = $client_id;
        $data = [
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'grant_type'    => "password",
            'password'      => $this->AccountFields['Pass'],
            'rememberMe'    => true,
            'scope'         => "payment,global",
            'username'      => $this->AccountFields['Login'],
        ];
        //set headers
        $headers = [
            "Content-Type"  => 'application/json',
            "Accept"        => 'application/json',
        ];
        //send post
        $this->http->RetryCount = 0;
        $this->http->PostURL('https://www.delivery.com/api/customer/auth?client_id=' . $client_id, json_encode($data), $headers);
        $this->http->RetryCount = 2;

        return true;
    }

    public function Login()
    {
        $response = $this->http->JsonLog();

        if (isset($response->access_token)) {
            $this->State['access_token'] = $response->access_token;

            return true;
        }

        if (isset($response->message[0]->user_msg) && $response->message[0]->user_msg == 'The username or password was incorrect. Please try again or reset your password.') {
            throw new CheckException($response->message[0]->user_msg, ACCOUNT_INVALID_PASSWORD);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        $response = $this->http->JsonLog(null, false);
        // Name
        if (($response->user->first_name ?? null) && ($response->user->last_name . ' ' ?? null)) {
            $this->SetProperty('Name', beautifulName($response->user->first_name . ' ' . $response->user->last_name));
        }
        // Balance - (delivery_points)
        $this->SetBalance($response->user->delivery_points ?? null);
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        $this->http->RetryCount = 0;
        $headers = [
            "authorization" => $this->State['access_token'],
        ];
        $this->http->GetURL("https://www.delivery.com/api/customer/account?client_id={$this->State['client_id']}", $headers);
        $this->http->RetryCount = 2;
        $response = $this->http->JsonLog();

        if (isset($response->user->email)) {
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
