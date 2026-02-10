<?php

use AwardWallet\Common\Parsing\Html;

class TAccountCheckerWaycom extends TAccountChecker
{
    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->RetryCount = 0;
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
        $this->http->GetURL('https://www.way.com/app/modules/landingPage/login/loginLinksNew.tmpl.html?v1.0.491');

        if ($this->http->Response['code'] != 200) {
            return false;
        }

        $this->securityCheckWorkaround();

        $this->http->FormURL = 'https://www.way.com/way-auth/auth/login';
        $this->http->SetInputValue('grant_type', 'password');
        $this->http->SetInputValue('username', $this->AccountFields['Login']);
        $this->http->SetInputValue('password', $this->AccountFields['Pass']);

        return true;
    }

    public function Login()
    {
        $headers = [
            'Accept'        => 'application/json, text/plain, */*',
            'Authorization' => 'Basic ' . base64_encode('way-web-consumer:35413210-85ea-4c06-9204-94dc23ced73c'),
        ];
        $this->http->PostForm($headers);
        $data = $this->http->JsonLog(null, 3, true);
        $message = $data['message'] ?? null;
        $token = $this->http->Response['headers']['access_token'] ?? null;

        if (strtolower($data["email"]) !== strtolower($this->AccountFields['Login'])) {
            $this->logger->error("the data does not match the requested account");

            return false;
        }

        if (is_string($message)) {
            if ($message == 'Successfully Loggedin' && $token) {
                $this->http->setCookie('Bearer', $token);

                return $this->loginSuccessful();
            }

            if ($message == 'Your username or password is not correct') {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            $this->DebugInfo = $message;
        }

        return false;
    }

    public function Parse()
    {
        $data = $this->http->JsonLog(null, 0, true);
        // Name
        $this->SetProperty('Name', beautifulName(Html::cleanXMLValue($data['userFullName'] ?? null)));

        $data = $this->getData('https://www.way.com/way-consumer/v1/user/waybucks', 3, true);
        // Balance - Way Bucks Available
        $this->SetBalance($data['waybuckAvailable'] ?? null);
        // Way Bucks Earned
        $this->SetProperty('WaybackEarned', $data['waybuckEarned'] ?? null);
        // Way Bucks Redeemed
        $this->SetProperty('WaybackRedeemed', $data['waybuckRedeemed'] ?? null);
    }

    public function ParseItineraries()
    {
        $params = [
            "categories" => [
                "Parking",
                "Dining",
                "Movies",
                "Events",
                "Activities",
                "Carwash",
            ],
            "orderType"     => "UpcomingOngoing",
            "paginationDto" => [
                "pageNumber" => 1,
                "pageSize"   => 1,
            ],
        ];

        if ($orders = $this->postData("https://www.way.com/way-orders/v1/users/orders", json_encode($params), 3,
            true)) {
            if (count($orders["rows"]) != 0) {
                $this->sendNotification("refs #23114 itineraries were found // IZ");
            }
        }

        return [];
    }

    protected function parseReCaptcha($key)
    {
        $this->logger->notice(__METHOD__);
        $key = $key ?? $this->http->FindSingleNode("//div[contains(@class, 'g-recaptcha')]/@data-sitekey");

        if (!$key) {
            return false;
        }

        $recognizer = $this->getCaptchaRecognizer(self::CAPTCHA_RECOGNIZER_RUCAPTCHA);
        $recognizer->RecognizeTimeout = 120;
        $parameters = [
            "pageurl" => $this->http->currentUrl(),
            "proxy"   => $this->http->GetProxy(),
        ];

        return $this->recognizeByRuCaptcha($recognizer, $key, $parameters);
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        $data = $this->getData('https://www.way.com/way-service/security/userProfileManagement/user', 3, true);
        $status = $data['status'] ?? null;

        if ($status == 'Success') {
            return true;
        }

        return false;
    }

    private function securityCheckWorkaround()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->ParseForm('challenge-form')) {
            $key = $this->http->FindSingleNode("//script[@data-sitekey]/@data-sitekey");

            if (!$key) {
                return false;
            }

            $captcha = $this->parseReCaptcha($key);

            if ($captcha === false) {
                return false;
            }

            $this->http->SetInputValue('g-recaptcha-response', $captcha);
            $this->http->PostForm();
        }

        return true;
    }

    private function getData(string $url, $logs = 0, $convertToArray = false)
    {
        $this->logger->notice(__METHOD__);
        $this->http->GetURL($url, [
            'Accept'        => 'application/json, text/plain, */*',
            'Authorization' => 'Bearer ' . $this->http->getCookieByName('Bearer', 'www.way.com'),
        ]);

        return $this->http->JsonLog(null, $logs, $convertToArray);
    }

    private function postData(string $url, $params = [], $logs = 0, $convertToArray = false)
    {
        $this->logger->notice(__METHOD__);
        $this->http->PostURL($url, $params, [
            'Accept'        => 'application/json, text/plain, */*',
            'Authorization' => 'Bearer ' . $this->http->getCookieByName('Bearer'),
            'Content-Type'  => 'application/json',
        ]);

        return $this->http->JsonLog(null, $logs, $convertToArray);
    }
}
