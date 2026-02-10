<?php

class TAccountCheckerSandals extends TAccountChecker
{
    private const REWARDS_PAGE_URL = 'https://www.sandalsselect.com/points/';
    /**
     * @var CaptchaRecognizer
     */
    private $recognizer;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
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
        $this->http->GetURL("https://www.sandalsselect.com/");

        if ($this->http->Response['code'] !== 200) {
            return $this->checkErrors();
        }

        $this->http->GetURL("https://www.sandalsselect.com/assets/js/master.js?v=1.2.0");
        $recaptchaKey = $this->http->FindPreg("/recaptcha\",sitekey:\"([^\"]+)\"/");

        if (!$recaptchaKey) {
            return false;
        }

        $responseCaptchaToken = $this->parseReCaptcha($recaptchaKey);

        if ($responseCaptchaToken === false) {
            return false;
        }

        $headers = [
            "Accept"       => "application/json, application/xml, text/plain, text/html, *.*",
            "Content-Type" => "application/x-www-form-urlencoded; charset=utf-8",
        ];

        $this->http->RetryCount = 0;
        $this->http->PostURL('https://www.sandalsselect.com/sbmtRecaptcha/', ['response' => $responseCaptchaToken], $headers);
        $response = $this->http->JsonLog();

        if ($response->status != 'success' || !isset($response->data->token)) {
            return false;
        }

        $data = [
            'csrfToken'      => '',
            'retoken'        => $response->data->token,
            'username'       => $this->AccountFields['Login'],
            'password'       => $this->AccountFields['Pass'],
        ];
        $this->http->PostURL('https://www.sandalsselect.com/sbmtLogin/', $data, $headers);
        $this->http->RetryCount = 2;

        return true;
    }

    public function Login()
    {
        $response = $this->http->JsonLog();

        if (isset($response->data->gnId)) {
            $this->captchaReporting($this->recognizer);

            return $this->loginSuccessful();
        }

        $message = $response->data->messgage ?? null;

        if ($message) {
            $this->logger->error("[Error]: {$message}");

            switch ($message) {
                case "We are sorry, but the login information provided does not match any records in our system. Please check and try again.":
                case "We are sorry, but the password provided does not match the login information. Please check and try again.":
                    $this->captchaReporting($this->recognizer);

                    throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);

                case "Error validating ReCaptcha!":
                    $this->captchaReporting($this->recognizer, false);

                    throw new CheckRetryNeededException(3, 0, self::CAPTCHA_ERROR_MSG);

                default:
                    $this->DebugInfo = $message;

                    return false;
            }
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        // Total Balance Points
        $this->SetBalance($this->http->FindSingleNode("//span[@class='total-number']"));
        // Name
        $this->SetProperty('Name', beautifulName($this->http->FindSingleNode("//div[@class='acct-info']/h2")));
        // Member Since
        $this->SetProperty('MemberSince', $this->http->FindSingleNode("//p[contains(text(),'Member Since:')]/strong"));
        // Membership
        $this->SetProperty('Membership', $this->http->FindSingleNode("//p[contains(text(),'Membership #:')]/strong"));
        // Total Paid Nights
        $this->SetProperty('TotalPaidNights', $this->http->FindSingleNode("//p[contains(text(),'Total Paid Nights:')]/strong"));
        // Your Level
        $this->SetProperty('Level', $this->http->FindSingleNode("//p[contains(text(),'Your Level:')]/strong"));

        $this->http->GetURL("https://www.sandalsselect.com/load-stays/");
        $response = $this->http->JsonLog();

        if (!empty($response->data->futureStays)) {
            $this->sendNotification("refs #13129 - Reservation detected");
        }

        if (!empty($response->data->pastStays)) {
            $this->sendNotification("refs #13129 - Past reservation detected");
        }
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);
        $this->http->RetryCount = 0;
        $this->http->GetURL(self::REWARDS_PAGE_URL, [], 20);
        $this->http->RetryCount = 2;

        if ($this->http->FindSingleNode("//div[@class='menu-welcome-message']")) {
            return true;
        }

        return false;
    }

    private function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        return false;
    }

    private function parseReCaptcha($key)
    {
        $this->logger->notice(__METHOD__);

        if (!$key) {
            return false;
        }

        $this->recognizer = $this->getCaptchaRecognizer();
        $this->recognizer->RecognizeTimeout = 120;
        $parameters = [
            "pageurl" => "https://www.sandalsselect.com/",
            "proxy"   => $this->http->GetProxy(),
        ];

        return $this->recognizeByRuCaptcha($this->recognizer, $key, $parameters);
    }
}
