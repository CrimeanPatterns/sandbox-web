<?php

class TAccountCheckerSlickdeals extends TAccountChecker
{
    /**
     * @var CaptchaRecognizer
     */
    private $recognizer;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->setHttp2(true);
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

        $this->http->RetryCount = 0;
        $this->http->GetURL('https://redeem.slickdeals.net/env-config-v2.js');
        $recaptchaKey = $this->http->FindPreg("/recaptchaPublicKey:\s*\"(.*?)\"/imu");

        if (!$recaptchaKey) {
            return false;
        }

        $this->http->PostURL('https://redeem.slickdeals.net/api/auth/redemption/token/generate', []);
        $response = $this->http->JsonLog();

        if (!isset($response->token)) {
            return false;
        }

        $responseCaptchaToken = $this->parseReCaptcha($recaptchaKey);

        if ($responseCaptchaToken === false) {
            return false;
        }

        $data = [
            'username'       => $this->AccountFields['Login'],
            'password'       => $this->AccountFields['Pass'],
            'recaptchaToken' => $responseCaptchaToken,
            'loginType'      => '',
            'requestedRoles' => ["ROLE_LOYALTY_PORTAL"],
        ];
        $headers = [
            'Accept'        => 'application/json, text/plain, */*',
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $response->token,
        ];

        $this->http->PostURL('https://redeem.slickdeals.net/api/auth/redemption/login', json_encode($data), $headers);
        $this->http->RetryCount = 2;

        return true;
    }

    public function Login()
    {
        $response = $this->http->JsonLog();

        if (isset($response->token)) {
            $this->State['Authorization'] = "Bearer {$response->token}";
            $this->captchaReporting($this->recognizer);

            return $this->loginSuccessful();
        }

        if (isset($response->errors->message) && $response->errors->message == 'UNAUTHORIZED') {
            $this->captchaReporting($this->recognizer);

            throw new CheckException('E-mail or password are invalid, please try again', ACCOUNT_INVALID_PASSWORD);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        $response = $this->http->JsonLog();
        // Name
        $this->SetProperty('UserName', $response->username);
        $this->http->GetURL('https://redeem.slickdeals.net/api/v1/balance');
        $response = $this->http->JsonLog();
        // Cashback Balance
        $confirmedPoints = $response->confirmedPoints;
        $this->SetBalance($confirmedPoints);
        // Points lifetime
        $this->SetProperty('LifetimePoints', $response->totalPointsLifetime);
        // Pending points
        $this->SetProperty('PendingPoints', $response->pendingPoints);
        // Redemption value
        $this->http->GetURL('https://redeem.slickdeals.net/api/v1/rewards/redeem-settings');
        $response = $this->http->JsonLog();
        $redemptionValue = round($confirmedPoints / $response->pointsToDollar, 2);
        $this->SetProperty('RedemptionValue', $redemptionValue);
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
            "pageurl" => $this->http->currentUrl(),
            "proxy"   => $this->http->GetProxy(),
        ];

        return $this->recognizeByRuCaptcha($this->recognizer, $key, $parameters);
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if (!isset($this->State['Authorization'])) {
            return false;
        }

        $headers = ['Authorization' => $this->State['Authorization']];
        $this->http->RetryCount = 0;
        $this->http->GetURL('https://redeem.slickdeals.net/api/loyalty/1/user', $headers);
        $this->http->RetryCount = 2;
        $response = $this->http->JsonLog();

        if (!isset($response->userId)) {
            return false;
        }

        $email = $response->emailAddress ?? null;
        $this->logger->debug("[Email]: {$email}");

        if (strtolower($email) == strtolower($this->AccountFields['Login'])) {
            $this->http->setDefaultHeader("Authorization", $this->State['Authorization']);

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
