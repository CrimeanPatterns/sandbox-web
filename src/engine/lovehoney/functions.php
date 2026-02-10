<?php

class TAccountCheckerLovehoney extends TAccountChecker
{
    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams($targetURL);
        $arg['CookieURL'] = 'https://www.lovehoney.co.uk/your-account/sign-in.html';

        return $arg;
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->setDefaultHeader('Accept-Encoding', 'gzip, deflate, br');
    }

    public function IsLoggedIn()
    {
        if ($this->attempt > 0) {
            return false;
        }

        $this->http->RetryCount = 0;
        $this->http->GetURL('https://www.lovehoney.co.uk/your-account/', [], 20);
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
        $this->http->GetURL('https://www.lovehoney.co.uk/your-account/sign-in.html');

        if (!$this->http->ParseForm(null, 1, true, '//form[contains(@action, "your-account/sign-in.html")]')) {
            return $this->checkErrors();
        }
        $this->http->SetInputValue('email', $this->AccountFields['Login']);
        $this->http->SetInputValue('password', $this->AccountFields['Pass']);
        $this->http->SetInputValue('Action', 'login');

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

        if ($message = $this->http->FindSingleNode("//p[contains(text(), 'Either your email address or password was incorrect.')]",
            null, false, "/(Either your email address or password was incorrect\.)/ims")) {
            throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        // Balance - You have ... Points worth
        $this->SetBalance($this->http->FindSingleNode("//div[@class='loyalty-rewards']//span[contains(text(), 'You have')]",
            null, true, "/You have\s*(\d+)\s*Point/ims"));
        // Name
        $this->SetProperty('Name', beautifulName($this->http->FindSingleNode("//p[@class='loginstatus']", null, true, "/Hello\s*(.*?)\s*\./ims")));
        // Balance worth - Points worth ... to take off your next order
        $this->logger->info('Balance worth', ['Header' => 3]);
        $this->SetProperty('BalanceWorth', $this->http->FindSingleNode("//div[@class='loyalty-rewards']//span[contains(text(), 'You have')]",
            null, true, "/Points? worth\s*(.*?)\s*to take off your next order/ims"));
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindSingleNode('//a[contains(@href, "sign-out") and contains(text(), "Sign out")]/@href')) {
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
