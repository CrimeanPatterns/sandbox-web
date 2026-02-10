<?php

use AwardWallet\Common\Parsing\Html;

class TAccountCheckerGiant extends TAccountChecker
{
    public const REWARDS_PAGE_URL = 'http://www.giantfood.com';

    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams($targetURL);
        $arg['CookieURL'] = 'http://www.giantfood.com/';

        return $arg;
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
    }

    public function IsLoggedIn()
    {
        if ($this->attempt > 0 || !isset($this->State['bearer-token'])) {
            return false;
        }

        $this->http->RetryCount = 0;
        $this->http->GetURL(self::REWARDS_PAGE_URL, [], 20);
        $this->http->RetryCount = 2;

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

        if (!$srcJS = $this->http->FindSingleNode('//script[contains(@src, "/static/common/js/bundle/base.min.")]/@src')) {
            return false;
        }
        $this->http->GetURL(self::REWARDS_PAGE_URL . $srcJS);
        $string = $this->http->FindPreg('/SNS:\{(.*)\}\,GNTC:/');
        $token = $this->http->FindPreg('/tokenAuth:"(.*)"/', false, $string);

        if (!isset($token)) {
            return false;
        }
        $data = [
            "username"   => $this->AccountFields['Login'],
            "password"   => $this->AccountFields['Pass'],
            "grant_type" => 'password',
        ];
        //set headers
        $headers = [
            "Content-Type"  => 'application/x-www-form-urlencoded',
            "Accept"        => '*/*',
            "authorization" => 'Basic ' . $token,
        ];
        //send post
        $this->http->RetryCount = 0;
        $this->http->PostURL('https://giantfood.com/auth/oauth/token', $data, $headers);
        $this->http->RetryCount = 2;

        return true;
    }

    public function Login()
    {
        $response = $this->http->JsonLog(null, true, true);
        $bearer_token = $response['access_token'] ?? null;
        $token_type = $response['token_type'] ?? null;

        if ($bearer_token && $token_type && $this->loginSuccessful()) {
            $this->State['bearer-token'] = $bearer_token;

            return true;
        }

        $message = $response['error'] ?? null;

        if ($message) {
            if (strstr($message, 'invalid_grant')) {
                throw new CheckException('The username or password you entered is not valid. Please try again.', ACCOUNT_INVALID_PASSWORD);
            }
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        $bearer_token = $this->State['bearer-token'];
        $header = [
            'authorization'  => 'Bearer ' . $bearer_token,
            'Accept'         => 'application/json, text/plain, */*',
            'api-version'    => '1.0',
            'X-Store-Number' => null,
        ];
        $this->http->RetryCount = 0;
        $this->http->GetURL('https://giantfood.com/auth/api/private/synergy/account?_=' . date('UB'), $header);
        $this->http->RetryCount = 2;
        $response = $this->http->JsonLog(null, true, true);

        if (isset($response['exception']) && $response['exception'] == 'com.peapod.bannersites.auth.proxy.synergy.exception.NotFoundException') {
            $this->http->RetryCount = 0;
            $this->http->GetURL('https://giantfood.com/auth/profile?_=' . date('UB'), $header);
            $this->http->RetryCount = 2;

            $response = $this->http->JsonLog(null, true, true);
        }

        // set storeNumber for gasPoints request
        $header['X-Store-Number'] = $response['storeNumber'] ?? null;

        // Name
        if (isset($response['firstName']) && isset($response['lastName'])) {
            $this->SetProperty('Name', beautifulName($response['firstName'] . ' ' . $response['lastName']));
        } elseif (isset($response['firstName'])) {
            $this->SetProperty('Name', beautifulName($response['firstName']));
        }
        // Year-to-Date Savings
        $this->SetProperty("YTDSavings", '$' . ArrayVal($response, 'yearToDateSavings', 0));
        // card number
        if ($cardNumber = ArrayVal($response, 'cardNumber', null)) {
            $this->SetProperty("Number", $cardNumber);
        }

        if (!$cardNumber) {
            return;
        }

        $this->http->GetURL('https://giantfood.com/auth/api/private/synergy/coupons/stats/' . $cardNumber . '?_=' . date('UB'), $header);
        $response = $this->http->JsonLog(null, true, true);
        // Your Available Savings - (totalAvlSavings)
        $this->SetProperty('YourAvailableSavings', ($response['totalAvlSavings'] ?? false) ? '$' . $response['totalAvlSavings'] : null);

        //get Balance raw
        if (!isset($header['X-Store-Number'])) {
            return;
        }
        // gasPoints request
        $this->http->GetURL('https://giantfood.com/auth/api/private/synergy/programs/' . $cardNumber . '/gas/details?_=' . date('UB'), $header);
        $response = $this->http->JsonLog(null, true, true);
        // Balance != 0
        if (isset($response['gasPoints']) && is_array($response['gasPoints']) && !empty($response['gasPoints'])) {
            // Balance - Gas Points
            $this->SetBalance(ArrayVal($response['gasPoints'][0], 'balance', null));
            // Points Expiring
            $this->SetProperty('ExpiringBalance', ArrayVal($response['gasPoints'][0], 'balanceToExpire', null));
        } elseif ($this->http->FindPreg("/^\s*\{\s*\"gasPoints\"\s*:\s*\[\s*\]\s*\,\s*\"calculatedRate\"\s*:\s*[\d\.]+\\s*}\s*$/", false, Html::cleanXMLValue($this->http->Response['body']))) {
            $this->SetBalance(0);
        }

        if ($this->ErrorCode == ACCOUNT_ENGINE_ERROR) {
            // Your account is not up-to-date
            $this->http->GetURL("https://giantfood.com/auth/api/private/synergy/missinginfo?_=" . date('UB'), $header);
            $response = $this->http->JsonLog(null, true, true);

            if (ArrayVal($response, 'complete') === false) {
                $this->throwProfileUpdateMessageException();
            }
        }
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->currentUrl() != self::REWARDS_PAGE_URL) {
            $this->http->GetURL(self::REWARDS_PAGE_URL);
        }

        if ($this->http->FindSingleNode('//a[contains(@href,"/my-account")]')) {
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
