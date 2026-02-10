<?php

class TAccountCheckerPeachavia extends TAccountChecker
{
    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
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
        $this->http->GetURL('https://myaccount.flypeach.com/login?lang=en');

        if ($this->http->Response['code'] != 200) {
            return $this->checkErrors();
        }

        $data = [
            'email'    => $this->AccountFields['Login'],
            'password' => $this->AccountFields['Pass'],
        ];
        $this->http->RetryCount = 0;
        $this->http->PostURL('https://myaccount.flypeach.com/api/session?lang=en', $data);
        $this->http->RetryCount = 2;

        return true;
    }

    public function Login()
    {
        // code=204 - auth success
        if ($this->http->Response['code'] == 204 && $this->loginSuccessful()) {
            return true;
        }
        //email && password validation
        $json = $this->http->JSonLog(null, 5, true);
        $key = $json['validationErrors'][0]['key'] ?? null;
        $message = $json['validationErrors'][0]['messages'][0] ?? null;

        if (
            $key == 'email'
            && $message == 'Email address or passwords are incorrect.'
        ) {
            throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        $json = $this->http->JsonLog(null, 0);
        // Name
        $this->SetProperty('Name', beautifulName($json->firstname . ' ' . $json->lastname));

        // peachpoints
        $this->http->RetryCount = 0;
        $this->http->GetUrl('https://myaccount.flypeach.com/api/peachpoints/summary?lang=en');
        $response = $this->http->JsonLog();

        if ($this->http->Response['body'] == '{"error":"System error occurred."}') {
            $this->SetBalanceNA();
        } else {
            $this->sendNotification("refs #20277 . Peachpoints is avalable. // NI");
        }

        // reservations
        $this->http->GetUrl('https://myaccount.flypeach.com/api/reservations?lang=en&lastDepartDateFrom=2021-05-19&status=ACTIVE&order=asc&page=1&perPage=5');

        if ($this->http->Response['body'] != '{"error":"System error occurred."}') {
            $this->sendNotification("refs #20277 Reservations is avalable.  // NI");
        }
    }

    public function ParseItineraries()
    {
        $result = [];
        $this->http->GetURL('https://www.flypeach.com/en');
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

    private function parseItinerary()
    {
        $result = [];
        $bookNumber = '';
        $this->logger->info("Parse itinerary #{$bookNumber}", ['Header' => 3]);

        return $result;
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        $this->http->RetryCount = 0;
        $this->http->GetURL('https://myaccount.flypeach.com/api/user?lang=en&disableCache=false', [], 20);
        $this->http->RetryCount = 2;
        $json = $this->http->JsonLog();

        if (isset($json->email) && strtolower($this->AccountFields['Login']) == strtolower($json->email)) {
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
