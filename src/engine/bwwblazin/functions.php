<?php

use AwardWallet\Engine\ProxyList;

class TAccountCheckerBwwblazin extends TAccountChecker
{
    use ProxyList;
    /**
     * @var CaptchaRecognizer
     */
    private $recognizer;

    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams($targetURL);
        $arg['CookieURL'] = 'http://www.buffalowildwings.com/';

        return $arg;
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->SetProxy($this->proxyReCaptcha());
        $this->http->setDefaultHeader('Accept-Encoding', 'gzip, deflate, br');
    }

    public function IsLoggedIn()
    {
        if ($this->attempt > 0) {
            return false;
        }

        $this->http->RetryCount = 0;
        $this->http->GetURL('https://www.blazinrewards.com/Profile/ProfileManagement/ProfileLandingPage', [], 20);
        $this->http->RetryCount = 2;

        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL('https://www.blazinrewards.com/Authorization/AuthorizationClient/LogIn');

        if (!$this->http->ParseForm('login')) {
            return $this->checkErrors();
        }
        $keyCaptcha = $this->parseCaptcha();

        if ($keyCaptcha === false) {
            return false;
        }
        $this->http->SetInputValue('username', $this->AccountFields['Login']);
        $this->http->SetInputValue('password', $this->AccountFields['Pass']);
        $this->http->SetInputValue('g-recaptcha-response', $keyCaptcha);

        return true;
    }

    public function Login()
    {
        if (!$this->http->PostForm()) {
            return $this->checkErrors();
        }

        if ($this->loginSuccessful()) {
            $this->recognizer->reportGoodCaptcha();

            return true;
        }

        if ($message = $this->http->FindSingleNode('//li[contains(text(), "Your email or password didn\'t match our records.")]')) {
            throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        if ($this->http->FindSingleNode('//li[contains(text(),\'The reCaptcha has not been resolved properly.\')]')) {
            $this->recognizer->reportIncorrectlySolvedCAPTCHA();

            throw new CheckRetryNeededException(3, 1, self::CAPTCHA_ERROR_MSG);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        // Balance
        $this->SetBalance($this->http->FindSingleNode('//div[@class="info"]//div[2]'));
        // Name
        $this->SetProperty('Name', beautifulName($this->http->FindSingleNode('//div[@id="profileFullName"]')));
        // Member since
        $this->SetProperty('MemberSince', $this->http->FindSingleNode('//div[@class="info"]//div[@class="date"]/strong[1]'));
        // Check Certificates
        $reqVerifi = $this->http->FindSingleNode('//input[@name="__RequestVerificationToken"]/@value');

        if (!$reqVerifi) {
            return;
        }
        $data = [
            'sort'                       => '',
            'group'                      => '',
            'fiter'                      => '',
            '__RequestVerificationToken' => $reqVerifi,
            'Status'                     => 'A',
        ];
        $headers = [
            'X-Requested-With' => 'XMLHttpRequest',
            'Content-Type'     => 'application/x-www-form-urlencoded; charset=UTF-8',
        ];
        $urlCert = 'https://www.blazinrewards.com/Profile/ProfileRewardCertificates/SearchProfileRewardCertificatesSummary';
        $this->http->RetryCount = 0;
        $this->http->PostURL($urlCert, $data, $headers);
        $this->http->RetryCount = 2;

        if (!empty($this->http->Response['body'])) {
            $this->sendNotification("Blazin - refs #15157. Possible certificates found");
        }
    }

    protected function parseCaptcha()
    {
        $this->logger->notice(__METHOD__);
        $key = $this->http->FindPreg("/'sitekey'\s*:\s*'([^\']+)'\,/");
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

        return $captcha;
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindNodes('//a[contains(@href, "LogOut")]/@href')) {
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
