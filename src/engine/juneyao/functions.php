<?php

class TAccountCheckerJuneyao extends TAccountChecker
{
    private const REWARDS_PAGE_URL = 'https://global.juneyaoair.com/u/flights/flights-order';

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL('https://global.juneyaoair.com/');

        $data = [
            'userName' => $this->AccountFields['Login'],
            'password' => $this->AccountFields['Pass'],
            'currCd'   => 'USD',
            'lang'     => 'en',
        ];

        $headers = [
            'Accept'          => '*/*',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Content-Type'    => 'application/json;charset=UTF-8',
        ];

        $this->http->RetryCount = 0;
        $this->http->PostURL("https://globalapi.juneyaoair.com//api/account/login", json_encode($data), $headers);
        $this->http->RetryCount = 2;

        return true;
    }

    public function Login()
    {
        $response = $this->http->JsonLog();
        $token = $response->token ?? null;
        $resultCode = $response->resultCode ?? null;

        if ($resultCode == "Y") {
            return true;
        }

        if (isset($response->errorMsg)) {
            $message = $response->errorMsg;
            $this->logger->error($message);

            if ($message == 'Username does not exist' || $message == 'Wrong username or password') {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }
        }

        if (!$token) {
            return $this->checkErrors();
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        return null;
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindSingleNode('//a[contains(text(), "Log Out")]/@href')) {
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
