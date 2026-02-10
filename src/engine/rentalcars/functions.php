<?php

class TAccountCheckerRentalcars extends TAccountChecker
{
    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams($targetURL);
        $arg['CookieURL'] = 'http://www.rentalcars.com/';

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
        $this->http->GetURL('https://secure.rentalcars.com/account/Dashboard.do', [], 20);
        $this->http->RetryCount = 2;

        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL('https://secure.rentalcars.com/CRMLogin.do');

        if (!$this->http->ParseForm('loyaltySignInForm')) {
            return $this->checkErrors();
        }
        $this->http->SetInputValue('j_username', $this->AccountFields['Login']);
        $this->http->SetInputValue('j_password', $this->AccountFields['Pass']);
        $this->http->SetInputValue('remember-me', 'true');
        $this->http->SetInputValue('submitted', 'true');
        $this->http->SetInputValue('promoCode', '');
        $this->http->SetInputValue('crmOrigin', 'https://secure.rentalcars.com/CRMLogin.do');
        $this->http->unsetInputValue('chatVerbatim');
        $this->http->unsetInputValue('');

        return true;
    }

    public function Login()
    {
        if (!$this->http->PostForm()) {
            return $this->checkErrors();
        }

        if ($this->loginSuccessful()) {
            return true;
        }

        if ($message = $this->http->FindSingleNode('//p[contains(text(), "Invalid email address or password")]')) {
            throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        $this->http->GetURL('https://secure.rentalcars.com/account/Settings.do');
        $firstName = trim($this->http->FindSingleNode('//input[@id="firstName"]'));
        $lastName = trim($this->http->FindSingleNode('//input[@id="surname"]'));
        // Name
        $this->SetProperty('Name', beautifulName($firstName . ' ' . $lastName));
        // check property email
        if (!empty($this->Properties['Name']) || $this->http->FindSingleNode('//div[@class="form-row"]//span[@class="answer"]')) {
            $this->SetBalanceNA();
        }
    }

    public function ParseItineraries()
    {
        $this->sendNotification("car - refs #13477. Possible itineraries were found");

        $this->http->GetURL('https://secure.rentalcars.com/account/Bookings.do');

        if ($this->http->FindSingleNode('//p[contains(text(), "You don\'t have any upcoming trips. Would you like to search for a car now?")]')) {
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

        if ($this->http->FindSingleNode('//a[contains(text(), "Sign out")]')) {
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
