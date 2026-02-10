<?php

class TAccountCheckerPalladium extends TAccountChecker
{
    private const REWARDS_PAGE_URL = 'https://www.palladiumhotelgroup.com/en';
    private $transId = null;
    private $tenant = null;
    private $policy = null;
    private $token = null;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
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
        $this->http->GetURL('https://www.palladiumhotelgroup.com/en/loyalty/summary');

        $this->transId = $this->http->FindPreg("/\"transId\":\"([^\"]+)/");
        $this->tenant = $this->http->FindPreg("/\"tenant\":\"([^\"]+)/");
        $this->policy = $this->http->FindPreg("/\"policy\":\"([^\"]+)/");

        if (!$this->transId || !$this->tenant || !$this->policy) {
            return $this->checkErrors();
        }

        $this->http->FormURL = "https://login.palladiumhotelgroup.com{$this->tenant}/SelfAsserted?tx={$this->transId}&p={$this->policy}";

        $this->http->SetInputValue('signInName', $this->AccountFields['Login']);
        $this->http->SetInputValue('password', $this->AccountFields['Pass']);
        $this->http->SetInputValue('request_type', 'RESPONSE');

        return true;
    }

    public function Login()
    {
        $csrf = $this->http->FindPreg("/csrf\":\"([^\"]+)/");

        if (!$csrf) {
            return $this->checkErrors();
        }
        $headers = [
            "Accept"           => "application/json, text/javascript, */*; q=0.01",
            "Content-Type"     => "application/x-www-form-urlencoded; charset=UTF-8",
            "X-CSRF-TOKEN"     => $csrf,
            "X-Requested-With" => "XMLHttpRequest",
            "Host"             => "login.palladiumhotelgroup.com",
        ];
        $this->http->RetryCount = 0;

        if (!$this->http->PostForm($headers)) {
            return $this->checkErrors();
        }
        $this->http->RetryCount = 2;

        $response = $this->http->JsonLog();
        $status = $response->status ?? null;

        if ($status == 200) {
            $param = [
                'rememberMe' => 'true',
                'csrf_token' => $csrf,
                'tx'         => $this->transId,
                'p'          => $this->policy,
                'diags'      => '{"pageViewId":"7ea4f1d6-fd73-4cb8-b35e-56f96cd2d3d9","pageId":"CombinedSigninAndSignup","trace":[{"ac":"T005","acST":1708586766,"acD":4},{"ac":"T021 - URL:https://www.palladiumhotelgroup.com/content/dam/palladium/msazure/MSA/miregistro.html","acST":1708586766,"acD":9},{"ac":"T019","acST":1708586766,"acD":9},{"ac":"T004","acST":1708586766,"acD":2},{"ac":"T003","acST":1708586766,"acD":1},{"ac":"T035","acST":1708586766,"acD":0},{"ac":"T030Online","acST":1708586766,"acD":0},{"ac":"T002","acST":1708586773,"acD":0},{"ac":"T018T010","acST":1708586772,"acD":759}]}',
            ];
            $this->http->RetryCount = 0;
            $this->http->GetURL("https://login.palladiumhotelgroup.com{$this->tenant}/api/CombinedSigninAndSignup/confirmed?" . http_build_query($param));
            $this->http->RetryCount = 2;

            $this->http->RetryCount = 0;
            $this->http->GetURL("https://www.palladiumhotelgroup.com/bin/logintoken?{}=");
            $this->http->RetryCount = 2;

            $token = $this->token = $this->http->getCookieByName('ms-access-token', 'www.palladiumhotelgroup.com', '/', true);
            $this->State['token'] = $token;

            return $this->loginSuccessful();
        }

        $message = $response->message ?? null;

        if ($message) {
            $this->logger->error("[Error]: {$message}");

            if (
                $message == 'Incorrect user ID or password.'
            ) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            $this->DebugInfo = $message;

            return false;
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        $data = $this->http->JsonLog();

        //Name user
        $fullName = $data->programMember->firstName . ' ' . $data->programMember->lastName;
        $this->SetProperty('Name', beautifulName($fullName));
        //EliteStatus
        $status = $data->memberTier->name ?? null;
        $this->SetProperty('EliteStatus', $status);
        //Account number
        $account = $data->programMember->membershipNumber ?? null;
        $this->SetProperty('Number', $account);
        //Registration date
        $enrollment = $data->programMember->enrollmentDate ?? null;
        $this->SetProperty('MemberSince', $enrollment);
        //Earn points to reach the next level
        $threshold = $data->memberTier->numberOfPointsToNextTier ?? null;
        $this->SetProperty('PointsToNextEliteStatus', $threshold);
        //Points expiration date

        //Balance, Points expiration date. Sub-account "Balance and points expiration date"
        foreach ($data->memberCurrency as $currency) {
            if ($currency->name === "Reward Points") {
                $this->SetBalance($currency->pointsBalance ?? null);

                $start = $currency->nextResetDate ?? null;
                $exp = strtotime($start);

                if ($exp) {
                    $this->SetExpirationDate($exp);
                }
            }

            if ($currency->name === "Elite") {
                $expiarationDate = $currency->nextResetDate ?? null;

                $this->AddSubAccount([
                    "Code"             => 'Elite',
                    "DisplayName"      => 'Elite Points',
                    "Balance"          => $currency->pointsBalance,
                    "ExpirationDate"   => strtotime($expiarationDate),
                ]);
            }
        }
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);
        $this->http->RetryCount = 0;
        $memberId = $this->http->getCookieByName("member-id", "www.palladiumhotelgroup.com");

        if (!$memberId) {
            return false;
        }

        $this->State['memberid'] = $memberId;

        $this->http->GetURL("https://e-palladium-aem-loyalty-api-pro.de-c1.cloudhub.io/loyalty/api/v1/members/" . $memberId, ["Authorization" => "Bearer " . $this->token]);
        $this->http->RetryCount = 2;

        $response = $this->http->JsonLog();
        $email = $response->account->email ?? null;

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
