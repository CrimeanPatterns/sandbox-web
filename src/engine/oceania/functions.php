<?php

class TAccountCheckerOceania extends TAccountChecker
{
    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams($targetURL);
        //$arg['SuccessURL'] = 'https://www.oceaniacruises.com/myaccount/oceania-club/';

        return $arg;
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->http->setHttp2(true);
        $this->KeepState = true;
        $this->http->setDefaultHeader('Accept-Encoding', 'gzip, deflate, br');
    }

    public function IsLoggedIn()
    {
        if ($this->attempt > 0) {
            return false;
        }

        $this->http->RetryCount = 0;
        $this->http->GetURL('https://www.oceaniacruises.com/myaccount/oceania-club/', [], 20);
        $this->http->RetryCount = 2;

        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        // Login must be correct email
        if (filter_var($this->AccountFields['Login'], FILTER_VALIDATE_EMAIL) === false) {
            throw new CheckException('Invalid e-mail and/or password', ACCOUNT_INVALID_PASSWORD);
        }

        $this->http->removeCookies();
        $this->http->GetURL('https://www.oceaniacruises.com/login/?ReturnUrl=%2fexperience%2foceania-club%2f');

        if (!$this->http->ParseForm('aspnetForm')) {
            return $this->checkErrors();
        }
        $this->http->SetInputValue('ctl00$MainContentPlaceHolder$MemberLogin1$CtrlLogin$UserName', $this->AccountFields['Login']);
        $this->http->SetInputValue('ctl00$MainContentPlaceHolder$MemberLogin1$CtrlLogin$Password', $this->AccountFields['Pass']);
        $this->http->SetInputValue('ctl00$MainContentPlaceHolder$MemberLogin1$CtrlLogin$cbxRemeberme', 'on');
        $this->http->SetInputValue('ctl00$MainContentPlaceHolder$MemberLogin1$CtrlLogin$btnLogin', 'Sign In');

        return true;
    }

    public function Login()
    {
        if (!$this->http->PostForm()) {
            return $this->checkErrors();
        }

        if ($message = $this->http->FindSingleNode('//div[@class="notification error"]')) {
            throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        $this->logger->debug('>>> go to /myaccount/oceania-club');
        $this->http->GetURL('https://www.oceaniacruises.com/myaccount/oceania-club/');

        if ($this->loginSuccessful()) {
            return true;
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        // Get user data
        $headers = [
            'Accept'          => 'application/json, text/plain, */*',
            'Accept-Encoding' => 'gzip, deflate, br',
        ];
        $result = $this->http->PostURL('https://www.oceaniacruises.com/api/guest/getpastpassenger', '', $headers);
        $passenger = $this->http->JsonLog(null);

        if (!$result || !isset($passenger->firstName) || !isset($passenger->lastName) || !isset($passenger->credits)) {
            return;
        }

        // Balance - You have ... Cruise Credit(s)
        $this->SetBalance($passenger->credits);
        // Name
        $this->SetProperty('Name', $passenger->firstName . ' ' . $passenger->lastName);
        // Status
        $this->SetProperty('Status', $passenger->currentLevel->name ?? null);
        // Account Number - Loyalty Number: ...
        $this->SetProperty('AccountNumber', $passenger->societyNumber);
    }

    public function ParseItineraries()
    {
        $this->logger->notice(__METHOD__);
        $headers = [
            "Accept"           => "application/json, text/plain, */*",
            "X-Requested-With" => "XMLHttpRequest",
        ];
        $this->http->GetURL('https://www.oceaniacruises.com/api/bookedcruises/getbookedcruises/', $headers);
        $result = $this->http->JsonLog(null, false);

        if ($this->http->FindPreg("/^\{\"results\":\[\],\"ships\":/")) {
            return $this->noItinerariesArr();
        }

        $cruises = $result->results ?? [];
        $ships = $result->ships ?? [];
        $this->logger->debug("Total " . count($cruises) . " reservations were found");

        foreach ($cruises as $cruise) {
            $this->parseItinerary($cruise, $ships);
        }

        return [];
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindSingleNode("//h1[normalize-space()='My Account: Oceania Club']")) {
            return true;
        }

        return false;
    }

    private function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        return false;
    }

    private function parseItinerary($cruise, $ships)
    {
        $this->logger->debug(var_export($cruise, true), ["pre" => true]);

        $bookNumber = $cruise->bookingNumber ?? null;
        $this->logger->info("Parse itinerary #{$bookNumber}", ['Header' => 3]);
        $c = $this->itinerariesMaster->add()->cruise();

        // Travellers, Account Numbers
        $travellers = $accNumbers = [];

        foreach ($cruise->guestDetails as $guest) {
            $travellers[] = beautifulName($guest->fullName);
            $accNumbers[] = $guest->oceaniaClubNumber;
        }

        // General
        $c->general()
            ->confirmation($bookNumber, 'Booking #', true)
            ->status($cruise->status)
            ->date2($cruise->depositDueDate)
            ->travellers($travellers, true);

        // Program
        $c->program()
            ->accounts($accNumbers, false);

        // Details
        $shipCode = '';

        foreach ($ships as $ship) {
            if ($ship->name == $cruise->shipName) {
                $shipCode = $ship->id;

                break;
            }
        }
        $c->details()
            ->number($cruise->voyageId)
            ->deck($cruise->stateroomNumber > 1000 ? (int) ($cruise->stateroomNumber / 1000) : null)
            ->shipCode($shipCode)
            ->ship($cruise->shipName)
            ->description($cruise->voyageName)
            ->room($cruise->stateroomNumber)
            ->roomClass($cruise->stateroomDescription);

        // Segments
        if (strpos($cruise->portToPort, ' to ')) {
            $ports = explode(' to ', $cruise->portToPort);
            $c->addSegment()->setName(trim($ports[0]))
                ->parseAboard($cruise->startDay . ' ' . $cruise->startMonth . ' ' . $cruise->startYear);
            $c->addSegment()->setName(trim($ports[1]))
                ->parseAshore($cruise->endDay . ' ' . $cruise->endMonth . ' ' . $cruise->endYear);
        }

        $this->logger->debug("Parsed Itinerary:");
        $this->logger->debug(var_export($c->toArray(), true), ["pre" => true]);
    }
}
