<?php

class TAccountCheckerAdvantagecar extends TAccountChecker
{
    private $data = [];

    public function LoadLoginForm()
    {
        if (filter_var($this->AccountFields['Login'], FILTER_VALIDATE_EMAIL) === false) {
            throw new CheckException('Invalid e-mail and/or password', ACCOUNT_INVALID_PASSWORD);
        }

        $this->http->removeCookies();
        $this->http->FilterHTML = false;
        $this->http->GetURL("https://www.advantage.com/user");

        $advlogin = $this->http->FindPreg("/\"advlogintNonce\":\"([^\"]+)/");

        if (!$this->http->ParseForm("adv_login") || !$advlogin) {
            return $this->checkErrors();
        }
        $this->http->FormURL = 'https://www.advantage.com/wp-admin/admin-ajax.php';
        $this->http->SetInputValue('email', $this->AccountFields['Login']);
        $this->http->SetInputValue('password', $this->AccountFields['Pass']);
        $this->http->SetInputValue('action', "advLogin");
        $this->http->SetInputValue('advlogin', $advlogin);
        $this->http->unsetInputValue("return_url");

        return true;
    }

    public function checkErrors()
    {
        $this->logger->notice(__METHOD__);
        // The website encountered an unexpected error. Please try again later.
        if ($message = $this->http->FindPreg("/The website encountered an unexpected error\. Please try again later\./")) {
            throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }

        return false;
    }

    public function Login()
    {
        // getting an intermediate page
        if (!$this->http->PostForm()) {
            return $this->checkErrors();
        }
        // Access is allowed
        $response = $this->http->JsonLog();

        if (isset($response->memberNumber)) {
            return true;
        }// if (isset($response->memberNumber))

        // Login failed. Please try again
        if (isset($response->error->errorMessage) && $response->error->errorMessage == 'Client authentication failed') {
            throw new CheckException("Login failed. Please try again", ACCOUNT_INVALID_PASSWORD);
        }

        if (
            isset($response->error->errorMessage)
            && in_array($response->error->errorMessage, [
                'Invalid Credentials.',
                'Persuade create user failed - nothing returned.',
                'Login failed, invalid username or password. Please try again.',
                'Undefined index: d',
            ])
        ) {
            throw new CheckException("Login failed. Please try again", ACCOUNT_INVALID_PASSWORD);
        }

        return $this->checkErrors();
    }

    public function getRewardsPage($response = null)
    {
        $this->logger->notice(__METHOD__);

        if (empty($this->data)) {
            $this->data = [
                "hash"         => ArrayVal($response, 'SSO_HASH'),
                "id"           => ArrayVal($response, 'userGUID'),
                "membernumber" => ArrayVal($response, 'memberNumber'),
                "access_token" => ArrayVal($response, 'access_token'),
            ];
        }
        $this->http->PostURL("https://www.advantage.com/expressway", $this->data);
    }

    public function Parse()
    {
        $response = $this->http->JsonLog(null, false, true);
        $user = ArrayVal($response, 'user');
        // Name
        $this->SetProperty('Name', beautifulName(CleanXMLValue(ArrayVal($user, 'FirstName') . " " . ArrayVal($user, 'LastName'))));
        // Member ID
        $this->SetProperty('MemberID', ArrayVal($response, 'memberNumber'));

        // get Rewards page
        $this->getRewardsPage($response);

        // Status
        $this->SetProperty('Status', $this->http->FindSingleNode("//node()[contains(., \"Status:\")]/following-sibling::b[1]"));
        // Balance - Available Rewards
        $this->SetBalance($this->http->FindSingleNode("//node()[contains(., \"Available Rewards:\")]/following-sibling::b[1]"));

        // SubAccounts
        $this->http->GetURL("https://www.advantage.com/expressway/activity");
        $awards = $this->http->XPath->query("//div[div[h1[contains(text(), 'Available Rewards')]]]/following-sibling::div[1]//table//tr[td]");
        $this->logger->debug("Total {$awards->length} rewards were found");
        $this->SetProperty("CombineSubAccounts", false);

        foreach ($awards as $award) {
            $displayName = $this->http->FindSingleNode("td[3]", $award);
            $code = $this->http->FindSingleNode("td[4]", $award);
            $exp = $this->http->FindSingleNode("td[5]", $award);

            if (isset($displayName, $code)) {
                $subAcc = [
                    'Code'        => 'advantagecar' . $code,
                    'DisplayName' => $displayName . " #" . $code,
                    'Balance'     => null,
                ];
                $subAcc['ExpirationDate'] = strtotime($exp);
                $this->AddSubAccount($subAcc);
            }// if (isset($displayName, $code))
        }// foreach ($awards as $award)
    }

    public function ParseItineraries()
    {
        $result = [];
        $this->http->GetURL('https://www.advantage.com/awards/');
        // Reservation History
        $itineraries = $this->http->XPath->query("//h1[contains(text(), 'Reservation History')]/following-sibling::table//tbody");
        $this->logger->debug("Total {$itineraries->length} itineraries were found");

        foreach ($itineraries as $itinerary) {
            if (strstr($itinerary->nodeValue, 'No reservations to display.')) {
                return $this->noItinerariesArr();
            }

            $it = $this->ParseItinerary($itinerary);

            if (!empty($it)) {
                $result[] = $it;
            }
        }

        return $result;
    }

    private function ParseItinerary($row)
    {
        $this->logger->notice(__METHOD__);
        $result = ['Kind' => 'L'];
        $result['Number'] = $this->http->FindSingleNode("tr[1]/td[2]", $row);

        $result['DropoffDatetime'] = strtotime($this->http->FindSingleNode('tr[2]//div[strong[contains(text(), "Return")]]/following-sibling::div[1]', $row), false);

        if (!$this->ParsePastIts && $result['DropoffDatetime'] < time()) {
            return [];
        }

        $this->logger->info("Parse itinerary #{$result['Number']}", ['Header' => 3]);

        $result['Status'] = $this->http->FindSingleNode("tr[1]/td[4]", $row);

        if (strtolower($result['Status']) == 'cancelled') {
            $result['Cancelled'] = true;
        }

        $result['RenterName'] = beautifulName($this->http->FindSingleNode("tr[1]/td[5]", $row));

        $result['PickupLocation'] = $this->http->FindSingleNode("tr[1]/td[6]", $row);
        $result['PickupDatetime'] = strtotime($this->http->FindSingleNode('tr[2]//div[strong[contains(text(), "Pickup")]]/following-sibling::div[1]', $row), false);

        $result['DropoffLocation'] = $result['PickupLocation'];
        $result['CarType'] = $this->http->FindSingleNode('tr[2]//div[strong[contains(text(), "Car Class")]]/following-sibling::div[1]', $row);

        $this->logger->debug('Parsed itinerary:');
        $this->logger->debug(var_export($result, true), ['pre' => true]);

        return $result;
    }
}
