<?php

class TAccountCheckerRetailmenot extends TAccountChecker
{
    use AwardWallet\Engine\ProxyList;
    /**
     * @var CaptchaRecognizer
     */
    private $recognizer;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->SetProxy($this->proxyReCaptcha());
        $this->http->setHttp2(true);
        $this->http->setDefaultHeader('Accept-Encoding', 'gzip, deflate, br');
    }

    public function IsLoggedIn()
    {
        if ($this->attempt > 0) {
            return false;
        }

        $this->http->RetryCount = 0;
        $this->http->GetURL('https://secure.retailmenot.com/my-cashback', [], 20);
        $this->http->RetryCount = 2;

        if ($this->loginSuccessful()) {
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
        $this->http->GetURL('https://secure.retailmenot.com/accounts/login');

        $captcha = $this->parseReCaptcha();

        if ($captcha === false) {
            return false;
        }
        $data = [
            'identifier'             => $this->AccountFields['Login'],
            'password'               => $this->AccountFields['Pass'],
            'passwordHash'           => hash('sha256', $this->AccountFields['Pass']),
            'passwordMeetsMinLength' => true,
        ];
        $headers = [
            'Accept'            => '*/*',
            'Content-Type'      => 'application/json',
            'x-recaptcha-token' => $captcha,
        ];
        $this->http->RetryCount = 0;
        $this->http->PostURL('https://secure.retailmenot.com/accounts/api/login', json_encode($data), $headers);
        $this->http->RetryCount = 2;

        return true;
    }

    public function Login()
    {
        $result = $this->http->JsonLog(null, false);

        if (!isset($result->success)) {
            return false;
        }

        if ($result->success == false && $result->error->message == 'Unauthorized') {
            throw new CheckRetryNeededException(2, 10, 'Invalid e-mail and/or password', ACCOUNT_INVALID_PASSWORD);
        }

        if ($result->success == true) {
            $this->recognizer->reportGoodCaptcha();
            $this->http->GetURL('https://secure.retailmenot.com/my-cashback');

            return true;
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        // Your lifetime rewards
        $this->SetProperty('LifetimeRewards', $this->http->FindSingleNode('//div[@class="my-cashback-header"]//span[@class="lifetime-cashback-amount"]'));
        // Approved Cash Back Rewards
        $cbRewards = $this->http->FindSingleNode('//div[@class="cashback-balance cashback-balance-small"]');
        $this->AddSubAccount([
            "Code"        => "retailmenotCashBackRewards",
            "DisplayName" => "Approved Cash Back Rewards",
            "Balance"     => $cbRewards,
        ]);
        // pending
        $pending = $this->http->FindSingleNode('//div[@class="cashback-balance-pending"]', null, false, '/([\$\d\.]+)/');
        $this->AddSubAccount([
            "Code"        => "retailmenotPending",
            "DisplayName" => "Pending",
            "Balance"     => $pending,
        ]);
        // Balance
        if (!$cbRewards = $this->http->FindPreg('/([\d\.]+)/ims', false, $cbRewards)) {
            return false;
        }

        if (!$pending = $this->http->FindPreg('/([\d\.]+)/ims', false, $pending)) {
            return false;
        }
        $this->SetBalance($cbRewards + $pending);
        // Name
        $this->http->GetURL('https://secure.retailmenot.com/profile');
        $this->SetProperty('Name', beautifulName($this->http->FindSingleNode('//div[@class="welcome-message"]', null, false, '/Hi\s*(.*?$)/ims')));
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindSingleNode('//a[contains(@href, "/logout")]/@href')) {
            return true;
        }

        return false;
    }

    private function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        return false;
    }

    private function parseReCaptcha()
    {
        $this->logger->notice(__METHOD__);
        $key = $this->http->FindPreg('/"siteKey":"([^\"]+)/');
        $this->logger->debug("data-sitekey: {$key}");

        if (!$key) {
            return false;
        }
        $this->recognizer = $this->getCaptchaRecognizer(self::CAPTCHA_RECOGNIZER_RUCAPTCHA);
        $this->recognizer->RecognizeTimeout = 120;
        $parameters = [
            "pageurl" => $this->http->currentUrl(),
            "proxy"   => $this->http->GetProxy(),
        ];
        $captcha = $this->recognizeByRuCaptcha($this->recognizer, $key, $parameters);
        $this->logger->debug("captcha: {$captcha}");

        return $captcha;
    }
}
