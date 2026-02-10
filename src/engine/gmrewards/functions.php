<?php

class TAccountCheckerGmrewards extends TAccountChecker
{
    private $clientRequestId = "343eb309-8299-402e-baee-84a04a4df6d7";
    private $clientId = "43b9895e-a54a-412e-b11d-eaf11dac570d";

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->setHttp2(true);
    }

    public function IsLoggedIn()
    {
        if (!isset($this->State['token'])) {
            return false;
        }

        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL("https://experience.gm.com/");

        if ($this->http->Response['code'] != 200) {
            return $this->checkErrors();
        }

        $this->http->RetryCount = 0;
        $this->http->GetURL("https://custlogin.gm.com/gmb2cprod.onmicrosoft.com/b2c_1a_seamlessmigration_signuporsignin/oauth2/v2.0/authorize?client_id={$this->clientId}&scope=openid%20profile&redirect_uri=https%3A%2F%2Fexperience.gm.com%2F_gbpe%2Fcode%2Fprod1%2Fauth-waypoint.html&client-request-id={$this->clientRequestId}&response_mode=fragment&response_type=code&x-client-SKU=msal.js.browser&x-client-VER=2.11.0&x-client-OS=&x-client-CPU=&client_info=1&code_challenge=G2uXhMCNJeBU3KQ0lBAwOuw_GP_1FvSdH3HKSHQt-Dc&code_challenge_method=S256&nonce=ddada8b3-f731-49fc-8f87-379c10a90ecb&state=eyJpZCI6IjkzYWY3Y2E3LTU3YWQtNGVhOS1hZmFlLTNhNGVmZThkZjE1NSIsIm1ldGEiOnsiaW50ZXJhY3Rpb25UeXBlIjoicmVkaXJlY3QifX0%3D%7Chttps%3A%2F%2Fexperience.gm.com%2F%7Cen-US&brand=GM&channel=globalnav&requiredMissingInfo=true&ui_locales=en-US");
        $stateProperties = $this->http->FindPreg('/"StateProperties=(.+?)",/');
        $csrf = $this->http->FindPreg('/"csrf":"(.+?)",/');
        $tenant = $this->http->FindPreg("/\"tenant\"\s*:\s*\"([^\"]+)/");
        $transId = $this->http->FindPreg("/\"transId\"\s*:\s*\"([^\"]+)/");
        $remoteResource = $this->http->FindPreg("/\"remoteResource\"\s*:\s*\"([^\"]+)/");
        $pageViewId = $this->http->FindPreg("/\"pageViewId\"\s*:\s*\"([^\"]+)/");
        $p = $this->http->FindPreg("/\"policy\"\s*:\s*\"([^\"]+)/");

        if (!$stateProperties || !$csrf || !$transId || !$remoteResource || !$pageViewId) {
            return false;
        }

        $postData = [
            "request_type"    => "RESPONSE",
            "logonIdentifier" => $this->AccountFields['Login'],
            "password"        => $this->AccountFields['Pass'],
        ];
        $headers = [
            "Accept"           => "application/json, text/javascript, */*; q=0.01",
            "Content-Type"     => "application/x-www-form-urlencoded; charset=UTF-8",
            "X-CSRF-TOKEN"     => $csrf,
            "X-Requested-With" => "XMLHttpRequest",
        ];

        $this->http->PostURL("https://custlogin.gm.com{$tenant}/SelfAsserted?tx={$transId}&p={$p}", $postData, $headers);
        $response = $this->http->JsonLog();
        $status = $response->status ?? null;

        if ($status !== "200") {
            $message = $response->message ?? null;

            if ($message) {
                $this->logger->error("[Error]: {$message}");

                if ($message == 'unauthorized, please try again') {
                    throw new CheckException("Invalid. Please try again.", ACCOUNT_INVALID_PASSWORD);
                }

                if ($message == 'Wrong email or password') {
                    throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
                }

                $this->DebugInfo = $message;
            }

            return false;
        }

        $this->logger->notice("Logging in...");
        $param = [];
        $param['rememberMe'] = "true";
        $param['csrf_token'] = $csrf;
        $param['tx'] = $transId;
        $param['p'] = $p;
        $param['diags'] = '{"pageViewId":"37e38c8b-81ef-48f3-949d-26bc894617a5","pageId":"CombinedSigninAndSignup","trace":[{"ac":"T005","acST":1703059453,"acD":1},{"ac":"T021 - URL:https://accounts.gm.com/common/login/index.html","acST":1703059453,"acD":1027},{"ac":"T019","acST":1703059454,"acD":2},{"ac":"T004","acST":1703059454,"acD":2},{"ac":"T003","acST":1703059455,"acD":1},{"ac":"T035","acST":1703059455,"acD":0},{"ac":"T030Online","acST":1703059455,"acD":0},{"ac":"T002","acST":1703059483,"acD":0},{"ac":"T018T010","acST":1703059481,"acD":1643}]}';
        $this->http->GetURL("https://custlogin.gm.com{$tenant}/api/CombinedSigninAndSignup/confirmed?" . http_build_query($param));

        $this->http->RetryCount = 2;

        return true;
    }

    public function Login()
    {
        $code = $this->http->FindPreg("/code=([^&]+)/", false, $this->http->currentUrl());

        if ($code) {
            $postData = [
                "redirect_uri"      => "https://experience.gm.com/_gbpe/code/prod1/auth-waypoint.html",
                "scope"             => "openid profile",
                "grant_type"        => "authorization_code",
                "code"              => $code,
                "code_verifier"     => "e7zxbm_ZvqDfORz0csrqRHW4dZSB3sCIkKP5Gf-wpoY",
                "client_id"         => $this->clientId,
                "client_info"       => "1",
                "client-request-id" => $this->clientRequestId,
            ];
            $headers = [
                'Accept'       => '*/*',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ];
            $this->http->PostURL('https://custlogin.gm.com/gmb2cprod.onmicrosoft.com/b2c_1a_seamlessmigration_signuporsignin/oauth2/v2.0/token', $postData, $headers);

            $loginResponse = $this->http->JsonLog();

            if (!empty($loginResponse->id_token)) {
                $this->State['token'] = $loginResponse->id_token;

                return $this->loginSuccessful();
            }
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        $log = $this->http->JsonLog(null, 0);

        // Name
        $fullName = $log->first_name . " " . $log->last_name;
        $this->SetProperty('Name', beautifulName($fullName));

        $headers = [
            'Accept'        => '*/*',
            "Authorization" => $this->State['token'],
            "Referer"       => "https://experience.gm.com/",
            "locale"        => "en-US",
            "region"        => "US",
        ];

        $this->http->RetryCount = 0;
        $this->http->GetURL("https://experience.gm.com/api/_gbpe/v2/rewards", $headers);
        $response = $this->http->JsonLog();

        // POINTS AVAILABLE
        $this->SetBalance($response->reward->points);
        // TIER MEMBER
        $this->SetProperty('EliteLevel', $response->reward->tier);

        $this->http->GetURL("https://experience.gm.com/api/rewards/Account/getAccountInformation?idToken={$this->State['token']}");
        $this->http->RetryCount = 2;

        $response = $this->http->JsonLog();
        //MEMBER SINCE
        $date = DateTime::createFromFormat('m/d/Y H:i:s', $response->member->startDate);
        $this->SetProperty('MemberSince', $date->format('m/d/Y'));
        //MEMBER #
        $this->SetProperty('Number', $response->member->memberNumber);
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        $headers = [
            'Accept'        => '*/*',
            "Authorization" => $this->State['token'],
            "Referer"       => "https://experience.gm.com/",
            "locale"        => "en-US",
            "region"        => "US",
        ];
        $this->http->RetryCount = 0;
        $this->http->GetURL("https://experience.gm.com/api/_gbpe/v2/profiles", $headers);
        $this->http->RetryCount = 2;
        $response = $this->http->JsonLog();
        $email = $response->email_address ?? null;
        $this->logger->debug("[Email]: {$email}");

        if ($email && strtolower($email) == strtolower($this->AccountFields['Login'])) {
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
