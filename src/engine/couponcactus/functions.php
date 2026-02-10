<?php

use AwardWallet\Engine\ProxyList;

class TAccountCheckerCouponcactus extends TAccountChecker
{
    use ProxyList;
    private const REWARDS_PAGE_URL = 'https://www.couponcactus.com/profile/cash_dashboard';
    /**
     * @var CaptchaRecognizer
     */
    private $recognizer;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->SetProxy($this->proxyReCaptcha());
    }

    public function IsLoggedIn()
    {
        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL('https://www.couponcactus.com/login');

        if (!$this->http->ParseForm('login_form')) {
            return $this->checkErrors();
        }
        $keyCaptcha = $this->parseCaptcha();

        if ($keyCaptcha === false) {
            $this->captchaReporting($this->recognizer);

            return false;
        }

        $this->http->SetInputValue('email', $this->AccountFields['Login']);
        $this->http->SetInputValue('password', $this->AccountFields['Pass']);
        $this->http->SetInputValue('g-recaptcha-response', $keyCaptcha);
        $this->http->SetInputValue('remember_me', '1');

        return true;
    }

    public function Login()
    {
        // one request with invalid password
        $this->http->RetryCount = 0;
        $this->http->PostForm();
        $this->http->RetryCount = 2;

        if ($this->loginSuccessful()) {
            $this->captchaReporting($this->recognizer);

            return true;
        }

        // if email or password is not corrected
        if ($message = $this->http->FindSingleNode('//ul[@class="form_errors"]/li[contains(text(), "The email/password you entered is incorrect")]')) {
            $this->captchaReporting($this->recognizer);

            throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        // if captcha is bad
        if ($message = $this->http->FindSingleNode('//ul[@class="form_errors"]/li[contains(text(), "Captcha verification failed")]')) {
            $this->captchaReporting($this->recognizer, false);

            throw new CheckRetryNeededException(3, 1, self::CAPTCHA_ERROR_MSG);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        if ($this->http->currentUrl() != self::REWARDS_PAGE_URL) {
            $this->http->GetURL(self::REWARDS_PAGE_URL);
        }
        // Balance -
        $this->SetBalance($this->http->FindSingleNode('//div[@id="usercp"]//div[contains(text(), "Total Cash Back Balance")]/span', null, true, self::BALANCE_REGEXP_EXTENDED));
        // Name
        $this->SetProperty('Name', beautifulName($this->http->FindSingleNode('//div[@class="cp-welcome"]/span[@class="cp-highlight"]')));
        // Number
        $this->SetProperty('Member', $this->http->FindSingleNode('//table[@id="member-info"]//td[contains(text(), "Member #:")]/following-sibling::td'));
        // Member Since
        $this->SetProperty('MemberSince', $this->http->FindSingleNode('//table[@id="member-info"]//td[contains(text(), "Member Since:")]/following-sibling::td'));
        // Payment Amount
        $this->SetProperty('PaymentAmount', $this->http->FindSingleNode('//tbody[@class="striped"]/tr[1]/td[@align="right"]'));    // this is best node, because this haven't other name of properties
        // Next Payment
        $this->SetProperty('NextPayment', $this->http->FindSingleNode('//td[span[contains(text(), "Next Payment")]]//following-sibling::span[@class="cb-paid-num"]'));
    }

    protected function parseCaptcha()
    {
        $this->logger->notice(__METHOD__);
        $key = $this->http->FindSingleNode('(//form[@id="login_form"]//div[@class="g-recaptcha"]/@data-sitekey)');

        if (!$key) {
            return false;
        }

        $this->recognizer = $this->getCaptchaRecognizer(self::CAPTCHA_RECOGNIZER_RUCAPTCHA);
        $this->recognizer->RecognizeTimeout = 120;
        $parameters = [
            "pageurl" => $this->http->currentUrl(),
            "proxy"   => $this->http->GetProxy(),
        ];

        return $this->recognizeByRuCaptcha($this->recognizer, $key, $parameters);
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindSingleNode('//div[@id="user-menu2"]//span[@class="cp-highlight"]')) {
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
