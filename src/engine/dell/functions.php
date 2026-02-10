<?php

class TAccountCheckerDell extends TAccountChecker
{
    use SeleniumCheckerHelper;

    private const REWARDS_PAGE_URL = 'https://www.dell.com/en-us/myaccount/';

    private const WAIT_TIMEOUT = 7;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->UseSelenium();
        $this->useFirefox();
        $this->disableImages();
        $this->useCache();
        $this->http->saveScreenshots = true;
    }

    public function IsLoggedIn()
    {
        $this->http->GetURL(self::REWARDS_PAGE_URL);

        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL('https://www.dell.com/Identity/global/LoginOrRegister/98254675-ac6f-47d9-a7e4-4537724f135d?c=us&l=en&r=us&s=corp&~ck=mn&redirectUrl=https%3A%2F%2Fwww.dell.com%2Fen-us&pn=LoginOrRegister&feir=1');
        $loginFormLink = $this->waitForElement(WebDriverBy::xpath("//button[contains(text(), 'Sign In')]"), self::WAIT_TIMEOUT);
        $this->saveResponse();

        if (!$loginFormLink) {
            $this->logger->error("something went wrong");

            return false;
        }
        $loginFormLink->click();

        $login = $this->waitForElement(WebDriverBy::id('EmailAddress'), self::WAIT_TIMEOUT);
        $pass = $this->waitForElement(WebDriverBy::id('Password'), 0);

        if (!$login || !$pass) {
            $this->logger->error("something went wrong");

            return false;
        }

        // Entered data
        $login->sendKeys($this->AccountFields['Login']);
        $pass->sendKeys($this->AccountFields['Pass']);
        $pass->sendKeys(WebDriverKeys::ENTER);

        return true;
    }

    public function Login()
    {
        $this->waitForElement(WebDriverBy::xpath('//div[@class="dds__d-flex"][1]/div[2] | //form[@id="frmSignIn"]//div[@id="validationSummaryText"]'), self::WAIT_TIMEOUT);
        $this->saveResponse();

        if ($this->loginSuccessful()) {
            return true;
        }

        if ($message = $this->http->FindSingleNode('//form[@id="frmSignIn"]//div[@id="validationSummaryText"]')) {
            $this->logger->error($message);

            if (strstr($message, 'We are unable to match the details you entered with our records.')) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }
        }

        return false;
    }

    public function Parse()
    {
        if ($this->http->currentUrl() != self::REWARDS_PAGE_URL) {
            $this->http->GetURL(self::REWARDS_PAGE_URL);
        }

        $rewardsLink = $this->waitForElement(WebDriverBy::xpath("//a[@href='/en-us/myaccount/rewards']"), self::WAIT_TIMEOUT);
        $this->saveResponse();

        if (!$rewardsLink) {
            $this->logger->error("something went wrong");

            return false;
        }
        $rewardsLink->click();
        $this->waitForElement(WebDriverBy::xpath('//div[h1[contains(text(), "Dell Rewards")]]//div[h3[contains(text(), "Pending Rewards*")]]/h2'), self::WAIT_TIMEOUT);
        $this->saveResponse();

        // Available Rewards - General
        $this->SetBalance($this->http->FindSingleNode('//div[h4[contains(text(), "Dell Rewards")]]//div[contains(text(), "Available Rewards*")]/following-sibling::div', null, true, '/\\$(.+)/'));
        // Name
        $this->SetProperty('Name', beautifulName($this->http->FindSingleNode('//div[@class="customer-name-wrapper"]/h2')));
        // Member Since
        $this->SetProperty('MemberSince', $this->http->FindSingleNode('//div[h4[contains(text(), "Dell Rewards")]]//div[contains(text(), "Member since")]/following-sibling::div'));
        // Expiring Balance
        $expBalance = $this->http->FindSingleNode('//div[h1[contains(text(), "Dell Rewards")]]//div[h3[contains(text(), "Expiring on")]]/h2');
        $this->SetProperty("ExpiringBalance", $expBalance);
        // Expiring date
        $expDate = $this->http->FindSingleNode('//div[h1[contains(text(), "Dell Rewards")]]//div[h3[contains(text(), "Expiring on")]]/h3', null, true, '/[0-9].+/');
        $expDate = $this->ModifyDateFormat($expDate, '/', true);

        if ($expBalance > 0 && strtotime($expDate)) {
            $this->SetExpirationDate(strtotime($expDate));
        }
        //Pending
        $pending = $this->http->FindSingleNode('//div[h1[contains(text(), "Dell Rewards")]]//div[h3[contains(text(), "Pending Rewards*")]]/h2', null, true, '/\\$(.+)/');

        if (!$pending) {
            return false;
        }
        $this->AddSubAccount([
            "Code"        => "dellPending",
            "DisplayName" => "Pending",
            "Balance"     => $pending,
        ]);
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);
        $accName = $this->waitForElement(WebDriverBy::xpath('//span[@class="mh-si-label"]'), self::WAIT_TIMEOUT);
        $this->saveResponse();

        if ($accName) {
            return true;
        }

        return false;
    }
}
