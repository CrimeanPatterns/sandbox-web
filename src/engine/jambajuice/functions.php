<?php

use AwardWallet\Engine\ProxyList;

class TAccountCheckerJambajuice extends TAccountChecker
{
    use ProxyList;
    private const REWARDS_PAGE_URL = 'https://www.jamba.com/';

    /**
     * @var CaptchaRecognizer
     */
    private $recognizer;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->SetProxy($this->proxyReCaptcha());
        $this->http->setDefaultHeader("Accept-Encoding", "gzip, deflate, br");
    }

    public function IsLoggedIn()
    {
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
        $this->http->GetURL('https://www.jamba.com/');
        $action = $this->http->FindSingleNode('//script[@data-container="signin"]', null, true, '/\s*action\s*=\s*"([^"]*)/');

        if (!$action) {
            return $this->checkErrors();
        }

        $keyCaptcha = $this->parseCaptcha();

        if ($keyCaptcha === false) {
            return false;
        }

        $data = [
            'reCaptcha' => $keyCaptcha,
            'email'     => $this->AccountFields['Login'],
            'password'  => $this->AccountFields['Pass'],
        ];

        $this->http->RetryCount = 0;
        $this->http->PostURL('https://www.jamba.com' . $action, $data);
        $this->http->RetryCount = 2;

        return true;
    }

    public function Login()
    {
        if ($this->loginSuccessful()) {
            $this->captchaReporting($this->recognizer);

            return true;
        }

        if ($this->http->Response['body'] == 'Recaptcha not valid') {
            $this->captchaReporting($this->recognizer, false);

            throw new CheckRetryNeededException(3, 1, self::CAPTCHA_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
        }

        // Confirm the correct email and password.
        if ($this->http->Response['body'] == '{"Code":"500","Message":"Internal server error","ApiErrors":null}') {
            $this->captchaReporting($this->recognizer);

            throw new CheckException("Confirm the correct email and password.", ACCOUNT_INVALID_PASSWORD);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        // Balance - Points
        $balance = $this->http->FindSingleNode('//div[contains(@class, "radial-progress-component")]/div[contains(@class, "radial-progress")]/@data-current-points');
        $this->SetBalance($balance);
        // Points Until Next Reward
        $this->SetProperty('PointsUntilNextReward', $this->http->FindSingleNode('//div[contains(@class, "progress-content-wrapper")]/p[contains(@class, "description")]', null, true, '/You’re (\d+) point/'));

        $this->http->GetURL('https://www.jamba.com/account/rewards');
        $rewards = $this->http->XPath->query('//div[contains(@class, "account-earned-component")]/ul/li');
        $this->logger->debug("Total {$rewards->length} rewards were found");

        foreach ($rewards as $reward) {
            $displayName = $this->http->FindSingleNode('.//span[contains(@class, "item-title")]', $reward);
            $exp = strtotime($this->http->FindSingleNode('.//span[contains(@class, "item-info")]', $reward, false, "/expires\s*(.+)/"));
            $this->AddSubAccount([
                "Code"           => "Reward" . md5($displayName) . $exp,
                "DisplayName"    => $displayName,
                "Balance"        => null,
                "ExpirationDate" => $exp,
            ], true);
        }

        // Name
        $this->http->GetURL('https://www.jamba.com/account/contact-information');
        $this->SetProperty('Name', beautifulName($this->http->FindSingleNode('//input[contains(@id, "FirstName")]/@value') . ' ' . $this->http->FindSingleNode('//input[contains(@id, "LastName")]/@value')));
    }

    protected function parseCaptcha()
    {
        $this->logger->notice(__METHOD__);
        $key = $this->http->FindSingleNode('//script[@data-container="signin"]', null, true, '/data-recaptcha-sitekey\s*=\s*"([^"]*)/');

        if (!$key) {
            return false;
        }

        $this->recognizer = $this->getCaptchaRecognizer(self::CAPTCHA_RECOGNIZER_RUCAPTCHA);
        $this->recognizer->RecognizeTimeout = 120;
        $parameters = [
            "pageurl"   => $this->http->currentUrl(),
            "proxy"     => $this->http->GetProxy(),
            "invisible" => 1,
        ];

        return $this->recognizeByRuCaptcha($this->recognizer, $key, $parameters);
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindNodes('//a[contains(@class,"log-in") and contains(@href, "/account")]')) {
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
