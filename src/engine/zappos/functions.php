<?php

class TAccountCheckerZappos extends TAccountChecker
{
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
        $this->http->GetURL('https://www.zappos.com/', [], 20);
        $this->http->RetryCount = 2;

        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();

        //form page
        $this->http->GetURL('https://www.zappos.com/zap/preAuth/signin?openid.return_to=/c/zappos-homepage');

        //parsing form
        if (!$this->http->ParseForm('signIn')) {
            return false;
        }

        $this->http->SetInputValue('email', $this->AccountFields['Login']);
        $this->http->SetInputValue('password', $this->AccountFields['Pass']);
        $this->http->Form['metadata1'] = 'ECdITeCs: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';

        return true;
    }

    public function Login()
    {
        //send form
        if (!$this->http->PostForm()) {
            return $this->checkErrors();
        }

        //check auth
        if ($this->loginSuccessful()) {
            return true;
        }

        if ($message = $this->http->FindPreg("/Important Message!/")) {
            throw new CheckException('To better protect your account, please re-enter your password and then enter the characters as they are shown in the image below.', ACCOUNT_INVALID_PASSWORD);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        //parsing the page
        $this->http->GetURL('https://secure-www.zappos.com/rewards/dashboard');

        //Redeemable Points
        $this->SetBalance($this->http->FindSingleNode("//div[@class='redeemablePoints']//table[1]//td[@class='value']"));
        //Dollar Amount Redeemable
        $this->SetProperty("DollarAmount", $this->http->FindSingleNode("//div[@class='redeemablePoints']//table[2]//td[@class='value']"));
        //points (count,value)
        $this->SetProperty("TierPoints", $this->http->FindSingleNode("//span[@id='loyaltyPointTotal']"));
        //status (...silver, gold...)
        $this->SetProperty("Status", $this->http->FindSingleNode("//div[@class='progressDetails']/span[@class='tierStatus']"));
        //Points to next level(silver,gold...)
        $this->SetProperty("PointsLevel", $this->http->FindSingleNode("//span[@id='loyaltyUpgradeProcess']", null, true, '/^([0-9]+)\s.*/'));
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindSingleNode("//a[contains(text(),'Sign Out')]")) {
            return true;
        }

        return false;
    }
}
