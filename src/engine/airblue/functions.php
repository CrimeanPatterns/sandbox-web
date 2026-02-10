<?php

class TAccountCheckerAirblue extends TAccountChecker
{
    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams();
        $arg['SuccessURL'] = 'https://www.airblue.com/bluemiles/myaccount';

        return $arg;
    }

    public function LoadLoginForm()
    {
        // Incorrect Login or Password
        if (filter_var($this->AccountFields['Login'], FILTER_VALIDATE_EMAIL) !== false) {
            throw new CheckException("Incorrect Login or Password", ACCOUNT_INVALID_PASSWORD);
        }

        $this->http->removeCookies();
        $this->http->GetURL('https://www.airblue.com/rewards/login.asp');

        if (!$this->http->ParseForm('frm_Login')) {
            return false;
        }
        $this->http->SetInputValue('login_action', 'dologin');
        $this->http->SetInputValue('login', $this->AccountFields['Login']);
        $this->http->SetInputValue('password', $this->AccountFields['Pass']);

        return true;
    }

    public function Login()
    {
        if (!$this->http->PostForm()) {
            return false;
        }

        if ($this->http->FindSingleNode('//*[contains(@href, "logout.asp") and text()="Log Out"]')) {
            return true;
        }
        // Incorrect Login or Password
        if ($message = $this->http->FindSingleNode('//div[@class="loginButton"]/text()[contains(., "Incorrect Login or Password")]')) {
            throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        return false;
    }

    public function Parse()
    {
        // Name
        $this->SetProperty('Name', $this->http->FindSingleNode('//span[starts-with(text(),"Member Name:")]/following-sibling::text()[1]'));
        // Member ID#
        $this->SetProperty('MemberId', $this->http->FindSingleNode('//span[starts-with(text(),"Member ID#:")]/following-sibling::text()[1]'));
        // Member Since
        $this->SetProperty('MemberSince', $this->http->FindSingleNode('//span[starts-with(text(),"Member Since:")]/following-sibling::text()[1]'));
        // Balance - Gunior points
        $this->SetBalance($this->http->FindSingleNode('//li[@class="memBalance"]', null, false, '/Balance:\s*(\d+)\s*$/'));

        if ($this->http->XPath->query('//*[@id="fd-table-1"]//tr')->length > 1) {
            $this->sendNotification('airblue: Appeared Itineraries');
        }
    }

    /*function ParseItineraries() {
        //$results = [];

        if ($this->http->XPath->query('//*[@id="fd-table-1"]//tr')->length > 0)
            $this->sendNotification('airblue: Appeared Itineraries');

        return [];
    }

    private function parseItinerary($details) {
        $result = ['Kind' => 'T'];

        return $result;
    }*/
}
