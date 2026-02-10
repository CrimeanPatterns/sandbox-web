<?php

class TAccountCheckerClubq extends TAccountChecker
{
    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams($targetURL);
        $arg['CookieURL'] = 'https://cqrewards.com/login';

        return $arg;
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->setHttp2(true);
        $this->http->setDefaultHeader('Accept-Encoding', 'gzip, deflate, br');
    }

    public function IsLoggedIn()
    {
        if ($this->attempt > 0) {
            return false;
        }

        $this->http->RetryCount = 0;
        $this->http->GetURL('https://clubquartershotels.com/', [], 20);
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
        $this->http->GetURL('https://cqrewards.com/login');

        if (!$this->http->ParseForm(null, 1, true, '//div[@class="login-left"]/form')) {
            return $this->checkErrors();
        }
        $this->http->SetInputValue('email', $this->AccountFields['Login']);
        $this->http->SetInputValue('password', $this->AccountFields['Pass']);

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

        if ($message = $this->http->FindSingleNode('//p[contains(text(), "Invalid login credentials")]')) {
            throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        // Balance - My Point Balance
        $this->SetBalance($this->http->FindSingleNode('//p[@class="point-balance"]/span'));
        // CQ Rewards Number
        $this->SetProperty('Number', $this->http->FindSingleNode('//div[@class="account-sidebar-organization"]', null, false, '/CQ Rewards Number:\s*(.*?$)/ims'));
        // Name
        $this->http->GetURL('https://cqrewards.com/view-profile');
        $name = $this->http->FindSingleNode('//span[contains(text(), "First Name")]/following-sibling::span');
        $name .= ' ' . $this->http->FindSingleNode('//span[contains(text(), "Last Name")]/following-sibling::span');
        $this->SetProperty('Name', beautifulName($name));
    }

    public function ConfirmationNumberURL($arFields)
    {
        return "https://clubquartershotels.com/";
    }

    public function CheckConfirmationNumberInternal($arFields, &$it)
    {
        $this->http->GetURL($this->ConfirmationNumberURL($arFields));

        $this->notificationsFromConfirmation($arFields);

        $chain = $this->http->FindSingleNode("//a[contains(normalize-space(), 'View/Modify Reservations')]/@href", null, false, "/chain:%20'([^']+)'/ims");
        $start = $this->http->FindSingleNode("//a[contains(normalize-space(), 'View/Modify Reservations')]/@href", null, false, "/start:%20'([^']+)'/ims");
        $promo = $this->http->FindSingleNode("//a[contains(normalize-space(), 'View/Modify Reservations')]/@href", null, false, "/promo:%20'([^']+)'/ims");

        if (!$chain || !$start || !$promo) {
            $this->notificationsFromConfirmation($arFields);

            return null;
        }

        $data = [
            'chain' => $chain,
            'start' => $start,
            'promo' => $promo,
        ];
        $headers = [
            'Accept'       => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
        $this->http->RetryCount = 0;
        $this->http->PostURL('https://gc.synxis.com/', $data, $headers);
        $this->http->RetryCount = 2;

        if (!$this->http->ParseForm("XbeForm")) {
            $this->notificationsFromConfirmation($arFields);

            return null;
        }
        $this->http->SetInputValue('V155$C1$LocateCustomerCntrl$EmailConfirmTextBox', $arFields['Email']);
        $this->http->SetInputValue('V155$C1$LocateCustomerCntrl$ConfirmTextbox', $arFields['ConfNo']);
        $this->http->SetInputValue('V155$C1$LocateCustomerCntrl$ConfirmSearchButton', 'Search');

        if (!$this->http->PostForm()) {
            $this->notificationsFromConfirmation($arFields);

            return null;
        }

        if ($message = $this->http->FindSingleNode("//span[@id='V156_C0_NoReservationMessageLabel']")) {
            return $message;
        }

        $it = $this->ParseItinerary();

        return null;
    }

    public function GetConfirmationFields()
    {
        return [
            "ConfNo" => [
                "Caption"  => "Confirmation #",
                "Type"     => "string",
                "Size"     => 20,
                "Cols"     => 40,
                "Required" => true,
            ],
            "Email"  => [
                "Type"     => "string",
                "Caption"  => "E-mail",
                "Size"     => 40,
                "Cols"     => 40,
                "Value"    => $this->GetUserField('Email'),
                "Required" => true,
            ],
        ];
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindSingleNode('//nav[@id="site-navigation"]/a[@href="/logout"]')) {
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

    private function notificationsFromConfirmation($arFields)
    {
        $this->logger->notice(__METHOD__);
        $this->sendNotification("failed to retrieve itinerary by conf #", 'all', true,
            "Conf #: <a target='_blank' href='https://awardwallet.com/manager/loyalty/logs?ConfNo={$arFields['ConfNo']}'>{$arFields['ConfNo']}</a><br/>Email: {$arFields['Email']}");
    }
}
