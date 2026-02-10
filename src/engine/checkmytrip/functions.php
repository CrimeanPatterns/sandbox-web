<?php

class TAccountCheckerCheckmytrip extends TAccountChecker
{
    use AwardWallet\Engine\ProxyList;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        //	    $this->http->SetProxy($this->proxyReCaptcha());
        $this->http->setHttp2(true);
        $this->http->setDefaultHeader('Accept-Encoding', 'gzip, deflate, br');
    }

    public function IsLoggedIn()
    {
        if ($this->attempt > 0 || empty($this->State['headers'])) {
            return false;
        }

        $this->http->RetryCount = 0;
        $this->http->PostURL("https://www.checkmytrip.com/cmt2web/apf/mobile/v1/account/getProfile?LANGUAGE=GB&SITE=NCMTNCMT", [], $this->State['headers']);
        $this->http->RetryCount = 2;
        $response = $this->http->JsonLog();

        if ($this->loginSuccessful($response)) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        // Login must be correct email
        if (filter_var($this->AccountFields['Login'], FILTER_VALIDATE_EMAIL) === false) {
            throw new CheckException('Invalid e-mail and/or password', ACCOUNT_INVALID_PASSWORD);
        }

        $this->http->removeCookies();
        $this->http->RetryCount = 0;

        $this->http->GetURL('https://www.checkmytrip.com/');

        if ($this->http->FindSingleNode('//form[@id="distilCaptchaForm"]') && !$this->parseGeetestCaptcha()) {
            return false;
        }

        if ($this->http->Response['code'] !== 200) {
            return false;
        }

        $data = [
            'userId'             => $this->AccountFields['Login'],
            'password'           => $this->AccountFields['Pass'],
            'triplistTimeWindow' => 'Future',
        ];
        $headers = [
            'Accept'       => 'application/json, text/plain, */*',
            'Content-Type' => 'application/x-www-form-urlencoded;charset=utf-8',
        ];
        $this->http->RetryCount = 0;
        $this->http->PostURL('https://www.checkmytrip.com/cmt2web/apf/mobile/v1/account/loginWithEmail?LANGUAGE=US&SITE=NCMTNCMT', ['data' => json_encode($data)], $headers);
        $this->http->RetryCount = 2;

        return true;
    }

    public function Login()
    {
        if ($this->http->FindSingleNode('//h1[normalize-space()="Access To Website Blocked"]')) {
            throw new CheckRetryNeededException(3);
        }

        $response = $this->http->JsonLog();

        if ($this->loginSuccessful($response)) {
            $this->State['headers'] = [
                "Accept"        => "application/json, text/plain, */*",
                "Authorization" => "Bearer {$response->model->accessToken}",
            ];

            return true;
        }

        $message = $response->model->errorCodes[0] ?? null;

        if ($message) {
            $this->logger->error("[errorCode]: {$message}");

            if ($message == '2026322') {
                throw new CheckException('Authentication failed. Please check your credentials and try again.', ACCOUNT_INVALID_PASSWORD);
            }

            return false;
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        $result = $this->http->JsonLog(null, false);

        if (empty($result->model->user->firstName) || empty($result->model->user->lastName)) {
            return;
        }
        // Name
        $this->SetProperty('Name', beautifulName($result->model->user->firstName . ' ' . $result->model->user->lastName));
        // Loyalty Cards
        $this->SetProperty('LoyaltyCards', $result->model->user->loyaltyCards);

        if (!empty($this->Properties['Name'])) {
            $this->SetBalanceNA();
        }

        if (!empty($this->Properties['LoyaltyCards'])) {
            $this->sendNotification("refs #6802 LoyaltyCards were found");
        }

        // Trips
        if (!empty($result->model->user->triplist->trips)) {
            $this->sendNotification("refs #6802. Trips founded // MT");
        }
    }

    private function loginSuccessful($response)
    {
        $this->logger->notice(__METHOD__);

        if (isset($response->model->success) && $response->model->success == true) {
            return true;
        }

        return false;
    }

    private function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        return false;
    }

    private function parseGeetestCaptcha()
    {
        $this->logger->notice(__METHOD__);
        $gt = $this->http->FindPreg("/gt:\s*'(.+?)'/");
        $apiServer = $this->http->FindPreg("/api_server:\s*'(.+?)'/");
        $ticket = $this->http->FindSingleNode('//input[@name = "dCF_ticket"]/@value');

        if (!$gt || !$apiServer || !$ticket) {
            $this->logger->notice('Not a geetest captcha');

            return false;
        }

        // watchdog workaround
        $this->increaseTimeLimit(180);

        /** @var HTTPBrowser $http2 */
        $http2 = clone $this->http;
        $url = '/distil_r_captcha_challenge';
        $this->http->NormalizeURL($url);
        $http2->PostURL($url, []);
        $challenge = $http2->FindPreg('/^(.+?);/');

        $recognizer = $this->getCaptchaRecognizer();
        $recognizer->RecognizeTimeout = 120;
        $parameters = [
            "pageurl"    => $this->http->currentUrl(),
            "proxy"      => $this->http->GetProxy(),
            'api_server' => $apiServer,
            'challenge'  => $challenge,
            'method'     => 'geetest',
        ];
        $captcha = $this->recognizeByRuCaptcha($recognizer, $gt, $parameters);
        $request = $this->http->JsonLog($captcha, true, true);

        if (empty($request)) {
            $this->logger->info('Retrying parsing geetest captcha');
            $captcha = $this->recognizeByRuCaptcha($recognizer, $gt, $parameters);
            $request = $this->http->JsonLog($captcha, true, true);
        }

        if (empty($request)) {
            $this->logger->error("geetest failed = true");

            return false;
        }

        $verifyUrl = $this->http->FindSingleNode('//form[@id = "distilCaptchaForm"]/@action');
        $this->http->NormalizeURL($verifyUrl);
        $payload = [
            'ticket'            => $ticket,
            'geetest_challenge' => $request['geetest_challenge'],
            'geetest_validate'  => $request['geetest_validate'],
            'geetest_seccode'   => $request['geetest_seccode'],
        ];
        $this->http->PostURL($verifyUrl, $payload);

        return true;
    }
}
