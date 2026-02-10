<?php

class TAccountCheckerBambooair extends TAccountChecker
{
    private $headers = [
        'Accept'       => 'application/json, text/plain, */*',
        'Content-Type' => 'application/json',
    ];

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->setDefaultHeader("Accept-Encoding", "gzip, deflate, br");
    }

    public function IsLoggedIn()
    {
        if (!isset($this->State['access_token'])) {
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
        $this->http->GetURL('https://bambooclub.bambooairways.com/BambooAirways/login?locale=en');

        if (!$this->http->FindSingleNode('//title[contains(., "Bamboo Club")]')) {
            return false;
        }

        $headers = [
            'Authorization' => 'Basic SUZMWVJFUy1TU08tQ0xJRU5UOlRlc3RAMTIzNA==',
        ];
        $query = [
            'username'   => $this->AccountFields['Login'],
            'password'   => $this->AccountFields['Pass'],
            'grant_type' => 'password',
        ];

        $this->http->RetryCount = 0;
        $this->http->PostURL('https://bambooclub.bambooairways.com/services/bamboo-club/account/oauth/token', $query, $headers);
        $this->http->RetryCount = 2;

        return true;
    }

    public function Login()
    {
        $json = $this->http->JsonLog();

        if (isset($json->access_token)) {
            $this->State['access_token'] = $json->access_token;

            return $this->loginSuccessful();
        }

        if (
            $this->http->Response['code'] == 400
            && isset($json->error_description)
            && $json->error_description == "Cant access the server to authenticate"
        ) {
            throw new CheckException('Invalid Username or Password', ACCOUNT_INVALID_PASSWORD);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        $json = $this->http->JsonLog(null, 0);
        // Loyalty Number
        $this->SetProperty('LoyaltyNumber', $json->object->memberProfile->membershipNumber);
        // Name
        $this->SetProperty('Name', beautifulName($json->object->memberProfile->individualInfo->givenName . ' ' . $json->object->memberProfile->individualInfo->familyName));
        // Balance (Member Point Balance)
        $this->SetBalance($json->object->accountSummery->bonusPointDetails->currentPoint);
        // Current Tier
        $this->SetProperty('CurrentTier', $json->object->accountSummery->currentTier);
        // Total Bonus Point Accured
        $this->SetProperty('TotalPointAccured', (int) $json->object->accountSummery->bonusPointDetails->totalBonusPointAccural);
        // Qualifying Points
        $this->SetProperty('QualifyingPoints', (int) $json->object->accountSummery->qualifyingPointsDetails->currentPoints);
        // Points to Next Tier
        $this->SetProperty('PointsToNextTier', (int) $json->object->accountSummery->qualifyingPointsDetails->qualifyingPointLeftOut);
        // Flights to Next Tier
        $this->SetProperty('FlightsToNextTier', (int) $json->object->accountSummery->qualifyingPointsDetails->ecoFlightsLeftOut);
        // Notification if bonusPointExpiryThisMonth not 0
        if ($json->object->accountSummery->bonusPointDetails->bonusPointExpiryThisMonth > 0) {
            $this->sendNotification('refs #20464  Bamboo Airways (Bamboo Club) - Bonus Points Expiring this Month: not 0');
        }
        // Expiration Date
        if (!empty($json->object->accountSummery->currentTierExpirationDate)) {
            $this->SetProperty('TierExpiration', str_replace('-', ' ', $json->object->accountSummery->currentTierExpirationDate));
        }

        $this->http->RetryCount = 0;
        $this->http->GetURL('https://bambooclub.bambooairways.com/services/bamboo-club/account/member/retrieveUpcomingFlightDetails', $this->headers);
        $this->http->RetryCount = 2;
        $json = $this->http->JsonLog();

        if (
            isset($json->object->flightLists)
            && count($json->object->flightLists) > 0
        ) {
            $this->sendNotification('refs #20464 Bamboo Airways (Bamboo Club) - Reservations not 0');
        }
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);
        $headers = $this->headers + [
            'Authorization' => 'bearer ' . $this->State['access_token'],
        ];
        $this->http->RetryCount = 0;
        $this->http->GetURL('https://bambooclub.bambooairways.com/services/bamboo-club/account/member/profilesummary', $headers);
        $this->http->RetryCount = 2;
        $json = $this->http->JsonLog();
        $emailAddress = $json->object->memberProfile->individualInfo->memberContactInfos[0]->emailAddress ?? null;
        $membershipNumber = $json->object->memberProfile->membershipNumber ?? null;
        $this->logger->debug("[Email]: {$emailAddress}");
        $this->logger->debug("[Number]: {$membershipNumber}");

        if (
            strtolower($this->AccountFields['Login']) == strtolower($emailAddress)
            || $this->AccountFields['Login'] == $membershipNumber
        ) {
            $this->headers = $headers;

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
