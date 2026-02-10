<?php

class TAccountCheckerHoneygold extends TAccountChecker
{
    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
    }

    public function IsLoggedIn()
    {
        if ($this->attempt > 0 || !isset($this->State['token'])) {
            return false;
        }

        $this->http->RetryCount = 0;
        $success = $this->loginSuccessful($this->State['token']);
        $this->http->RetryCount = 2;

        if ($success) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL('https://www.joinhoney.com/honeygold/overview');

        if ($this->http->Response['code'] != 200) {
            return $this->checkErrors();
        }
        $data = [
            "email" 		      => $this->AccountFields['Login'],
            "password"     	=> $this->AccountFields['Pass'],
        ];
        //set headers
        $headers = [
            "Content-Type" 	=> 'application/json;charset=utf-8',
            "Accept"       	=> 'application/json, text/plain, */*',
        ];
        //send post
        $this->http->RetryCount = 0;
        $this->http->PostURL('https://d.joinhoney.com/login?honeysrc=website', json_encode($data), $headers);
        $this->http->RetryCount = 2;

        return true;
    }

    public function Login()
    {
        $token = $this->http->getCookieByName("honey-token-access");

        if ($token && $this->loginSuccessful($token)) {
            $this->State['token'] = $token;

            return true;
        }

        $response = $this->http->JsonLog(null, true, true);
        $message = $response['message'] ?? null;

        if ($message) {
            if (strstr($message, 'Invalid email and/or password.')) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }
            // https://help.joinhoney.com/article/59-proxy-error
            if (strstr($message, 'RequestThrottled')) {
                $this->logger->error($message);

                return false;
            }
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        $response = $this->http->JsonLog(null, false);
        // Balance - (available_points)
        $this->SetBalance($response->data->getUserById->points->pointsAvailable ?? null);

        // Name
        $FirstName = $response->data->getUserById->firstName ?? null;
        $LastName = $response->data->getUserById->lastName ?? null;
        $this->SetProperty('Name', beautifulName($FirstName . ' ' . $LastName));

        //Total - (pointsRedeemed)
        $this->SetProperty('Total', $response->data->getUserById->points->pointsRedeemed ?? null);

        //Pending - (pointsPendingDeposit)
        $this->SetProperty('Pending', $response->data->getUserById->points->pointsPendingDeposit ?? null);

        //ReferalEarned - (referralPoints)
        $this->SetProperty('ReferalEarned', $response->data->getUserById->onboarding->referralPoints ?? null);
    }

    private function loginSuccessful($token)
    {
        $this->logger->notice(__METHOD__);

        $header = [
            'authorization' => 'honey ' . $token,
            'Content-Type' 	=> 'application/json',
        ];
        $this->http->GetURL('https://d.joinhoney.com/v3?query=query%20getUser%20%7B%0A%20%20getUserById%0A%7D%0A&operationName=getUser&variables=%7B%7D', $header);
        $response = $this->http->JsonLog();

        if (isset($response->data->getUserById->userId)) {
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
