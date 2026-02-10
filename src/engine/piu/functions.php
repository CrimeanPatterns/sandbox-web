<?php

class TAccountCheckerPiu extends TAccountChecker
{
    private const REWARDS_PAGE_URL = 'https://biglietti.italotreno.it/Customer_Account_Loyalty_MieiPunti.aspx?Culture=en-US';

    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams($targetURL);
        $arg['CookieURL'] = 'https://www.italotreno.it/en/';

        return $arg;
    }

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
        $this->http->GetURL('https://biglietti.italotreno.it/Customer_Account_Login.aspx?Culture=en-US');

        if (!$this->http->ParseForm('SkySales')) {
            return $this->checkErrors();
        }

        $this->http->SetInputValue('RestylingOverrideCustomerAccountLoginViewRestylingOverrideCustomerAccountLoginView$TextBoxUserID', $this->AccountFields['Login']);
        $this->http->SetInputValue('RestylingOverrideCustomerAccountLoginViewRestylingOverrideCustomerAccountLoginView$PasswordFieldPassword', $this->AccountFields['Pass']);
        $this->http->SetInputValue('RestylingOverrideCustomerAccountLoginViewRestylingOverrideCustomerAccountLoginView$ButtonLogIn', '');
        $this->http->SetInputValue('RestylingOverrideCustomerAccountLoginViewRestylingOverrideCustomerAccountLoginView$CheckBoxRemainLogged', 'on');

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

        if ($message = $this->http->FindSingleNode('//span[@class="inBaloonErr"]')) {
            $this->logger->error("[Error]: {$message}");

            if (
                strstr($message, 'Login failed, please try again')
            ) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            return false;
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        if ($this->http->currentUrl() != self::REWARDS_PAGE_URL) {
            $this->http->GetURL(self::REWARDS_PAGE_URL);
        }

        // Name
        $this->SetProperty('Name', beautifulName($this->http->FindSingleNode('//span[@class="ada-titolo"]/span[@style="text-transform: capitalize;"]')));
        // Balance -  Point
        $this->SetBalance($this->http->FindSingleNode('//span[@class="ada-titolo"]/span[not(@style)]'));
        // Member Type
        $this->SetProperty('MemberType', $this->http->FindSingleNode('//span[contains(@class,"ada-linea") and contains(., "Member Type:")]/span[contains(@class,"ada-bold")]'));
        // Membership Number
        $this->SetProperty('MembershipNumber', $this->http->FindSingleNode('//span[contains(@class,"ada-linea") and contains(., "Italo Più code")]/span[contains(@class,"ada-bold")]'));
        // Points to Next Level
        $this->SetProperty('PointsToNextLevel', $this->http->FindSingleNode('//span[contains(@class,"ada-linea") and contains(., "Missing")]/span[contains(@class,"ada-bold")]', null, '', '/(.*?)\sto\sreach\sPrivilege\slevel/'));
    }

    public function ParseItineraries()
    {
        $result = [];
        $this->http->GetURL('https://biglietti.italotreno.it/Customer_Account_MieiAcquisti_MieiViaggi.aspx');

        if ($this->http->FindSingleNode('//span[contains(text(), "There are no active reservations")]')) {
            $this->itinerariesMaster->setNoItineraries(true);
            return [];
        }

        $this->sendNotification("itineraries were found - refs #5816 // RR");

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

        if ($this->http->FindSingleNode('//*[@onclick="submitLogout()"]')) {
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
        $result = [];
        $bookNumber = '';
        $this->logger->info("Parse itinerary #{$bookNumber}", ['Header' => 3]);

        return $result;
    }
}
