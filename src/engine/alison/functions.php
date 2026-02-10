<?php

class TAccountCheckerAlison extends TAccountChecker
{
    public const REWARDS_PAGE_URL = 'https://alison.com/dashboard';

    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams($targetURL);
        $arg['CookieURL'] = 'https://alison.com/login/';
        $arg['SuccessURL'] = self::REWARDS_PAGE_URL;

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
        $this->http->GetURL("https://alison.com/login");

        if (!$this->http->ParseForm('login-form')) {
            return false;
        }
        $this->http->SetInputValue("email", $this->AccountFields['Login']);
        $this->http->SetInputValue("password", $this->AccountFields['Pass']);
        $this->http->SetInputValue("remember", "om");

        return true;
    }

    public function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        return false;
    }

    public function Login()
    {
        if (!$this->http->PostForm()) {
            return $this->checkErrors();
        }

        if ($this->loginSuccessful()) {
            return true;
        }
        // Invalid log in, please try again
        if ($message = $this->http->FindSingleNode("//span[contains(text(), 'Invalid log in, please try again')]")) {
            throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }
        // These credentials do not match our records.
        if ($message = $this->http->FindSingleNode("//div[contains(text(), 'These credentials do not match our records.')]")) {
            throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        if ($this->http->currentUrl() != self::REWARDS_PAGE_URL) {
            $this->http->GetURL(self::REWARDS_PAGE_URL);
        }
        // Name
        $this->SetProperty("Name", beautifulName($this->http->FindSingleNode("//div[@id = 'avatar-outer']/following-sibling::h1")));
        // Alison ID
        $this->SetProperty("ID", $this->http->FindSingleNode("//div[@id = 'avatar-outer']/following-sibling::h4", null, true, "/\:\s*([^<]+)/"));
        // Courses completed
        $this->SetProperty("Courses", $this->http->FindPreg("/completed = parseInt\(\"([^\"]+)/"));
        // Active courses
        $this->SetProperty("CourseCoursesInProgress", $this->http->FindSingleNode("//a[@title = 'Active Courses']/span[@class = 'active-courses']"));

        if (
            $this->ErrorCode == ACCOUNT_ENGINE_ERROR
            && !empty($this->Properties['Name'])
            && !empty($this->Properties['ID'])
            && isset($this->Properties['Courses'])
        ) {
            $this->SetBalanceNA();
        }
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindSingleNode("(//a[contains(@href, 'logout')]/@href)[1]")) {
            return true;
        }

        return false;
    }
}
