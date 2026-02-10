<?php

class TAccountCheckerHomeaway extends TAccountChecker
{
    public const REWARDS_PAGE_URL = 'https://www.homeaway.com/';

    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams($targetURL);
        $arg['CookieURL'] = 'https://www.homeaway.com/';

        return $arg;
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
    }

    public function IsLoggedIn()
    {
        if ($this->attempt > 0) {
            return false;
        }

        $this->http->RetryCount = 0;
        $success = $this->loginSuccessful();
        $this->http->RetryCount = 2;

        if ($success) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL(self::REWARDS_PAGE_URL);

        if (!$this->http->Response['code'] == 200) {
            return $this->checkErrors();
        }
        $data = [
            "userName"		 => $this->AccountFields['Login'],
            "secret"		   => $this->AccountFields['Pass'],
            "authType"		 => 'HOMEAWAY',
            "rememberMe"	=> true,
        ];
        //set headers
        $headers = [
            "Content-Type"  => 'application/json',
            "Accept"        => '*/*',
        ];
        //send post
        $this->http->RetryCount = 0;
        $this->http->PostURL('https://www.homeaway.com/auth/aam/v3/authenticate', json_encode($data), $headers);
        $this->http->RetryCount = 2;

        return true;
    }

    public function Login()
    {
        $response = $this->http->JsonLog(null, true, true);

        if (!$response) {
            return $this->checkErrors();
        }

        if (isset($response['publicUuid']) && $this->loginSuccessful()) {
            return true;
        }
        /**** the error if bad authorization
        * The password you entered is incorrect.
        * {"code":401,"message":"HTTP 401 Unauthorized"}
        */
        if (isset($response['message']) && $response['message'] == 'HTTP 401 Unauthorized') {
            throw new CheckException('The username or password you entered is incorrect.', ACCOUNT_INVALID_PASSWORD);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        $response = $this->http->JsonLog();
        // Name
        $name = $response->avatarSummary->name ?? null;
        $this->SetProperty('Name', beautifulName($name));
        // Member since
        $memberSince = $response->travelerProfile->memberSince ?? null;
        $this->SetProperty('MemberSince', $this->http->FindPreg('/^([\d]{4})-/', false, $memberSince));

        if (!empty($name) && !empty($memberSince)) {
            $this->SetBalanceNA();
        }
    }

    public function ParseItineraries()
    {
        $this->sendNotification("hotel - refs #3323. Possible itineraries were found");

        $this->http->GetURL('https://www.homeaway.com/traveler/th/bookings');

        if ($this->http->FindSingleNode('//span[contains(text(), "You don\'t have any past or upcoming trips.")]')) {
            return $this->noItinerariesArr();
        }

        $result = [];
        $nodes = $this->http->XPath->query("//its");

        foreach ($nodes as $node) {
            $this->http->GetURL($node->nodeValue);
            $it = $this->parseItinerary();
            $this->logger->debug('Parsed itinerary:');
            $this->logger->debug(var_export($it, true), ['pre' => true]);
            $result[] = $it;
        }

        return $result;
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);
        $this->http->GetURL('https://www.homeaway.com/traveler/profile/profiles', [], 20);
        $response = $this->http->JsonLog();

        if (isset($response->travelerProfile->accountUuid)) {
            return true;
        }

        return false;
    }

    private function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        return false;
    }

    private function parseItinerary()
    {
        $bookNumber = '';
        $this->logger->info("Parse itinerary #{$bookNumber}", ['Header' => 3]);
    }
}
