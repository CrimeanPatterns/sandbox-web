<?php

use AwardWallet\Engine\ProxyList;

class TAccountCheckerRebatesme extends TAccountChecker
{
    use ProxyList;

    private const REWARDS_PAGE_URL = 'https://www.rebatesme.com/user-center/cashback';
    /**
     * @var CaptchaRecognizer
     */
    private $recognizer;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->setDefaultHeader("Accept-Encoding", "gzip, deflate, br");
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
        if (filter_var($this->AccountFields['Login'], FILTER_VALIDATE_EMAIL) === false) {
            throw new CheckException('Please enter a valid email address, e.g. john@gmail.com', ACCOUNT_INVALID_PASSWORD);
        }

        $this->http->removeCookies();
        $this->http->GetURL('https://www.rebatesme.com/login');
        $this->metaRedirect();

        if (!$this->http->ParseForm('frm-login')) {
            return $this->checkErrors();
        }

        $token = $this->parseReCaptcha();

        if ($token === false) {
            return false;
        }

        $data = [
            'email'       => $this->AccountFields['Login'],
            'pwd'         => $this->AccountFields['Pass'],
            'code'        => $token,
            'codeid'      => 'login-page',
            'time'        => date('Y-m-d H:i:s'),
            'redirect-to' => '',
            'tip'         => '',
        ];
        $this->http->RetryCount = 0;
        $this->http->PostURL('https://www.rebatesme.com/Restful/signIn', $data);
        $this->http->RetryCount = 2;

        return true;
    }

    public function Login()
    {
        $json = $this->http->JsonLog(null, 3, true);
        $code = ArrayVal($json, 'code', null);
        $message = ArrayVal($json, 'msg', null);

        if ($code == '200') {
            return $this->loginSuccessful();
        }

        if (
            ($code == 302 && $message == "We do not recognize this email address. Please Join now.")
            || ($code == 301 && $message == "Wrong Password. Please try again.")
        ) {
            throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        // Balance - Account Balance
        $this->SetBalance($this->http->FindSingleNode('//p[contains(., "Account Balance")]/following-sibling::p'));
        // RebatesMe has paid you a total of ...
        $this->SetProperty('TotalPaid', $this->http->FindSingleNode('//p[contains(text(), "RebatesMe has paid you a total of")]/span'));
        // Pending
        $pending = $this->http->FindSingleNode('//p[contains(., "Pending")]/following-sibling::p');

        if (isset($pending)) {
            $this->AddSubAccount([
                'Code'        => 'Pending',
                'DisplayName' => 'Pending',
                'Balance'     => $pending,
            ]);
        }

        // Available
        $available = $this->http->FindSingleNode('//p[contains(., "Available")]/following-sibling::p');

        if (isset($available)) {
            $this->AddSubAccount([
                'Code'        => 'Available',
                'DisplayName' => 'Available',
                'Balance'     => $available,
            ]);
        }

        // Name
        $this->http->GetURL('https://www.rebatesme.com/user-center/setup');
        $this->SetProperty('Name', $this->http->FindSingleNode('//p[contains(text(), "User Name")]/following-sibling::p'));
    }

    private function parseReCaptcha()
    {
        $this->http->RetryCount = 0;
        $key = $this->http->FindSingleNode('//head/meta[contains(@name, "recaptcha")]/@content');

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
        $this->http->RetryCount = 0;
        $this->http->GetURL(self::REWARDS_PAGE_URL, [], 20);
        $this->metaRedirect();
        $this->http->RetryCount = 2;

        if ($this->http->FindSingleNode('//a[contains(text(), "Logout")]')) {
            return true;
        }

        return false;
    }

    private function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        return false;
    }

    private function metaRedirect()
    {
        $this->logger->notice(__METHOD__);

        if ($url = $this->http->FindSingleNode("//meta[@http-equiv='Refresh']/@content", null, true, "/URL=([^\']+)/")) {
            $this->http->NormalizeURL($url);
            $this->http->GetURL($url);
        }
    }
}
