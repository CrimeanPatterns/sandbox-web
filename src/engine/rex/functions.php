<?php

class TAccountCheckerRex extends TAccountChecker
{
    use AwardWallet\Engine\ProxyList;

    private const REWARDS_PAGE_URL = 'https://www.rex.com.au/';

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->setHttp2(true);
        $this->http->RetryCount = 0;
        $this->http->SetProxy($this->proxyReCaptcha());
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL(self::REWARDS_PAGE_URL);

        if (
             !$this->http->FindSingleNode('//title[contains(text(), "Rex Airlines")]')
             || $this->http->Response['code'] != 200
         ) {
            return $this->checkErrors();
        }

        return true;
    }

    public function Login()
    {
        if ($this->loginSuccessful()) {
            return true;
        }

        if ($message = $this->http->Response['headers']['in-auth-response'] ?? null) {
            $this->logger->error("[Error]: {$message}");

            // There is different text on the website and in the headlines
            if (strstr($message, 'Incorrect response') || strstr($message, 'Username ' . $this->AccountFields['Login'] . ' not found')) {
                throw new CheckException("Invalid Username or Password", ACCOUNT_INVALID_PASSWORD);
            }

            $this->DebugInfo = $message;

            return false;
        }

        return false;
    }

    public function Parse()
    {
        $this->GetSecureUrl('https://rexflyer.com.au/inspirenetz-api/api/0.9/json/customer/customerprofile/12');
        $log = $this->http->JsonLog(null, 3, true);
        // Name
        $firstName = $log['data']['cusFName'] ?? null;
        $lastName = $log['data']['cusLName'] ?? null;
        $this->SetProperty('Name', beautifulName("{$firstName} {$lastName}"));
        // tieName
        $this->SetProperty('Tier', $log['data']['tieName'] ?? null);
        // Number
        $this->SetProperty('Number', $log['data']['cusLoyaltyId'] ?? null);

        $this->GetSecureUrl('https://rexflyer.com.au/inspirenetz-api/api/0.9/json/portal/rewardbalance/12/6352');
        $log = $this->http->JsonLog(null, 3, true);
        // Balance - Rex Flyer Points
        $this->SetBalance($log['data'][0]['crbRewardBalance'] ?? null);

        $this->GetSecureUrl('https://rexflyer.com.au/inspirenetz-api/api/0.9/json/customer/tier/status/info');
        $log = $this->http->JsonLog(null, 3, true);
        $data = $log['data']['upgrade']['requirements'] ?? null;

        // Status points
        $this->SetProperty('StatusPoints', $data[0]['currentValue'] ?? null);
        // Status flights
        $this->SetProperty('StatusFlights', $data[1]['currentValue'] ?? null);

        if (
            !$this->GetSecureUrl('https://rexflyer.com.au/inspirenetz-api/api/0.9/json/customer/transactions/12/1980-01-01/9999-12-31?page.page=1&page.size=5')
        ) {
            return;
        }

        $log = $this->http->JsonLog(null, 3, true);
        $data = $log['data'] ?? null;

        if (
            is_numeric($log['totalpages'] ?? null)
            && (int) $log['totalpages'] > 1
        ) {
            $this->sendNotification("refs #23163 Found more than 5 pages in story elements. We need to create a pagination mechanism // IZ");
        }

        if (
            !is_iterable($data)
            || count($data) === 0
        ) {
            return;
        }

        foreach ($data as $dataItem) {
            $expDate = $dataItem['txnRewardExpDt'] ?? null;

            if (!$expDate || !$this->http->FindPreg('/\d{4}-\d{2}-\d{2}/', false, $expDate)) {
                continue;
            }

            if (strtotime($expDate) - time() <= 31536000) {
                $this->sendNotification("refs #23163 found a history item with an expiration interval of less than one year // IZ");
            }
        }
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if (!$this->GetSecureUrl('https://rexflyer.com.au/inspirenetz-api/api/0.9/json/user/authenticate')) {
            return $this->checkErrors();
        }

        $log = $this->http->JsonLog(null, 3, true);

        if ($log['status'] == 'success') {
            return true;
        }

        return false;
    }

    private function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        return false;
    }

    private function GetSecureUrl($url)
    {
        $this->http->GetURL($url);
        $data = $this->http->FindPregAll('/\"([^\"]+)\"/', $this->http->Response['headers']['www-authenticate']);
        $hash = $this->getHash($this->AccountFields['Login'], $data[0], $this->AccountFields['Pass'], 'GET', $url, $data[2]);

        return $this->http->GetURL($url, [
            "Authorization" => 'Digest realm="' . $data[0] . '", username="' . $this->AccountFields['Login'] . '", uri="' . $url . '", nonce="' . $data[2] . '", response="' . $hash . '"',
        ]);
    }

    private function getHash($login, $realm, $password, $method, $url, $nonce)
    {
        $a1 = $login . ':' . $realm . ':' . $password;
        $a2 = $method . ':' . $url;

        return md5(md5($a1) . ':' . $nonce . ':' . md5($a2));
    }
}
