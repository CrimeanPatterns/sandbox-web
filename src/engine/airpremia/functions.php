<?php

class TAccountCheckerAirpremia extends TAccountChecker
{
    private const REWARDS_PAGE_URL = 'https://www.airpremia.com/mypage/myInfo';

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
        $this->http->GetURL('https://www.airpremia.com/login');

        if (!$this->http->FindSingleNode('//div[@id = "fn_login"]')) {
            return $this->checkErrors();
        }

        $data = [
            'email'     => $this->AccountFields['Login'],
            'password'  => $this->AccountFields['Pass'],
            'autoLogin' => "Y",
        ];

        $headers = [
            'Accept'       => 'application/json, text/plain, */*',
            'Content-Type' => 'application/json',
        ];

        $this->http->RetryCount = 0;
        $this->http->PostURL('https://www.airpremia.com/user/login', json_encode($data), $headers);
        $this->http->RetryCount = 2;

        return true;
    }

    public function Login()
    {
        $response = $this->http->JsonLog();
        $result = $response->RESULT ?? null;

        switch ($result) {
            case "success":
                return $this->loginSuccessful();

            case "notUSER":
            case "loginFail":
                //Check your ID or password.
                throw new CheckException("Check your ID or password.", ACCOUNT_INVALID_PASSWORD);

            case "Login_Lock":
                //Your login has been restricted. Please try again in a moment.
                throw new CheckException("Your login has been restricted. Please try again in a moment.", ACCOUNT_LOCKOUT);

            default:
                $this->logger->error("[Error]: {$result}");

                return $this->checkErrors();
        }
    }

    public function Parse()
    {
        $userDataJson = $this->http->FindPreg("/'({\"agree_personal_collection_option.*)',\n\s*'/imu");
        $userData = $this->http->JsonLog($userDataJson, 3, true);

        if (!$userData) {
            $this->logger->error("Failed to parse user json data");

            return;
        }

        // Name
        $fullName = $userData['passport_first_name'] . " " . $userData['passport_last_name'];
        $this->SetProperty('Name', beautifulName($fullName));
        // Membership Number
        $this->SetProperty('Number', $userData["customer_number"]);
        // Points
        $this->SetBalance($this->http->FindSingleNode('//span[@id="showPointNum"]'));
        // My member status
        $this->SetProperty('EliteLevel', $userData["grade"]);

        $couponsDataJson = $this->http->JsonLog($this->http->FindPreg("/var\s*couponList\s*=\s*'({\"myCouponList\".*)\s*';/imu"));
        // My vouchers
        $this->SetProperty('VouchersTotal', count($couponsDataJson->myCouponList));

        if (!empty($couponsDataJson->myCouponList)) {
            $this->sendNotification("refs #23078 -  Voucher detected");
        }
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        $this->http->RetryCount = 0;
        $this->http->GetURL(self::REWARDS_PAGE_URL, [], 20);
        $this->http->RetryCount = 2;

        if ($this->http->FindSingleNode('//strong[@id="loginUserName"]')) {
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
