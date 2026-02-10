<?php

class TAccountCheckerTravelzoo extends TAccountChecker
{
    private const REWARDS_PAGE_URL = 'https://www.travelzoo.com/MyAccount/MyPurchases/?view=1';

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
        $this->http->GetURL("https://www.travelzoo.com/");

        if (!$this->http->ParseForm("register-form")) {
            return false;
        }
        // Sending form for authorization
        $this->http->Form = [];
        $this->http->FormURL = 'https://www.travelzoo.com/Authentication/login';
        $this->http->SetInputValue("email", $this->AccountFields['Login']);
        $this->http->SetInputValue("password", $this->AccountFields['Pass']);
        $this->http->SetInputValue("rememberme", "true");

        return true;
    }

    public function Login()
    {
        // Search RequestVerificationToken
        $verificationToken = $this->http->FindSingleNode("//input[@name='__RequestVerificationToken']/@value");

        if (!$verificationToken) {
            return $this->checkErrors();
        }

        $headers = [
            'Accept'                   => '*/*',
            'Content-Type'             => 'application/x-www-form-urlencoded; charset=UTF-8',
            'RequestVerificationToken' => $verificationToken,
        ];

        if (!$this->http->PostForm($headers)) {
            return $this->checkErrors();
        }

        $response = $this->http->JsonLog();
        $status = $response->Status ?? null;

        if ($status === 1) {
            return $this->loginSuccessful();
        }

        if ($status === 0) {
            throw new CheckException('The email address or password entered is incorrect. Please try again.', ACCOUNT_INVALID_PASSWORD);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        // Name
        $name = $this->http->FindSingleNode('//span[contains(@id, "memberFirstNameLabel")]') . ' ' . $this->http->FindSingleNode('//span[contains(@id, "memberLastNameLabel")]') ?? '';
        $this->SetProperty('Name', beautifulName(trim($name)));
        // Member Since
        $this->SetProperty('MemberSince', $this->http->FindSingleNode('//span[@class="memberIn"]', null, true, '/[0-9]+/'));

        if (!empty($this->Properties['Name']) && !empty($this->Properties['MemberSince'])) {
            $this->SetBalanceNA();
        }

        // Reservation
        if (!empty($this->http->FindSingleNode("//div[@id='chunk-0']"))) {
            $this->sendNotification("reservations were found - refs #6936");
        }
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        $this->http->RetryCount = 0;
        $this->http->GetURL(self::REWARDS_PAGE_URL, [], 20);
        $this->http->RetryCount = 2;

        if ($this->http->FindSingleNode("//li[@class='member-menu']//a[contains(text(), 'Sign Out')]")) {
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
