<?php

class TAccountCheckerSteigenberger extends TAccountChecker
{
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
        $this->http->GetURL('https://hrewards.com/en/account/login');

        if (!$this->http->Response['code'] == 200) {
            return $this->checkErrors();
        }

        $data = [
            'email'     => $this->AccountFields['Login'],
            'password'  => $this->AccountFields['Pass'],
        ];
        $headers = [
            'Accept'       => 'application/json, text/plain, */*',
            'Content-Type' => 'application/json',
        ];
        $this->http->RetryCount = 0;
        $this->http->PostURL('https://hrewards.com/bff/auth/login', json_encode($data), $headers);
        $this->http->RetryCount = 2;

        return true;
    }

    public function Login()
    {
        $response = $this->http->JsonLog();

        if (isset($response->access_token)) {
            $this->State['Authorization'] = "Bearer {$response->access_token}";

            return $this->loginSuccessful();
        }

        if (isset($response->message) && $response->message == 'Unauthorized') {
            throw new CheckException('E-mail or password are invalid, please try again', ACCOUNT_INVALID_PASSWORD);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        $response = $this->http->JsonLog(null, 0);

        // Reward Points
        $points = $response->rewards_points;
        $this->SetBalance($points);
        // Name
        $fullName = $response->first_name . " " . $response->last_name;
        $this->SetProperty('Name', beautifulName($fullName));
        // Membership Number
        $this->SetProperty('MembershipNumber', $response->loyaltyNumber);
        // Reward level
        $this->SetProperty('EliteLevel', $response->reward_level);
        // Expiring status
        $this->SetProperty("StatusExpiration", strtotime($response->membership_level_valid_to_date));
        // Points till next elite status
        $this->http->GetURL('https://hrewards.com/bff/members/points-status');
        $response = $this->http->JsonLog();
        $dailyPointStatus = $response->dailypointStatus;
        $progressNextEliteStatus = $dailyPointStatus->missing_points_for_upgrade;
        $this->SetProperty('NextLevel', $progressNextEliteStatus);

        if ($dailyPointStatus->points_expire_end_of_month_after_next || $dailyPointStatus->points_expire_end_of_this_month) {
            $this->sendNotification("refs #19607 -  Points expire end 'of month after next' or 'of this month' detected");
        }

        if ($dailyPointStatus->rewards_points || $dailyPointStatus->reward_nights || $dailyPointStatus->status_nights || $dailyPointStatus->status_points) {
            $this->sendNotification("refs #19607 -  New not null fields detected");
        }

        // Vouchers
        $this->logger->info('Vouchers', ['Header' => 3]);
        $this->http->GetURL('https://hrewards.com/bff/members/vouchers');
        $response = $this->http->JsonLog();
        $rewardInfo = array_pop($response);
        $progressNextReward = $rewardInfo->attributes->points;

        while ($progressNextReward < $points and !empty($rewardInfo)) {
            $rewardInfo = array_pop($response);
            $progressNextReward = $rewardInfo->attributes->points;
        }
        $this->SetProperty('NextReward', $progressNextReward);

        $this->reservationCheck();
    }

    private function reservationCheck()
    {
        $this->logger->info('Reservations', ['Header' => 3]);
        $this->logger->notice(__METHOD__);
        $this->http->GetURL("https://hrewards.com/bff/members/bookings");
        $response = $this->http->JsonLog();

        if (!empty($response)) {
            $this->sendNotification("refs #19607 -  reservation detected");
        }
        $this->http->GetURL("https://hrewards.com/bff/members/old-bookings");
        $response = $this->http->JsonLog();

        if (!(empty($response->upcoming) || empty($response->past) || empty($response->cancelled))) {
            $this->sendNotification("refs #19607 -  reservation detected");
        }
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if (!isset($this->State['Authorization'])) {
            return false;
        }
        $headers = ['Authorization' => $this->State['Authorization']];
        $this->http->RetryCount = 0;
        $this->http->GetURL('https://hrewards.com/bff/members/profile', $headers);
        $this->http->RetryCount = 2;
        $response = $this->http->JsonLog();

        $email = $response->email->email_address ?? null;
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
