<?php

class TAccountCheckerKrispy extends TAccountChecker
{
    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams($targetURL);
        $arg['CookieURL'] = 'http://www.krispykreme.co.uk/';

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
        $this->http->GetURL('https://friends.krispykremerewards.co.uk/Friends/MyRewards/MyRewards.aspx', [], 20);
        $this->http->RetryCount = 2;

        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL('https://friends.krispykremerewards.co.uk/Friends/Login/login.aspx');

        if (!$this->http->ParseForm('form1')) {
            return $this->checkErrors();
        }
        $this->http->SetInputValue('__EVENTTARGET', '');
        $this->http->SetInputValue('__EVENTARGUMENT', '');
        $this->http->SetInputValue('ctl00$ContentPlaceHolder1$txtUsuario', $this->AccountFields['Login']);
        $this->http->SetInputValue('ctl00$ContentPlaceHolder1$txtClave', $this->AccountFields['Pass']);
        $this->http->SetInputValue('ctl00$ContentPlaceHolder1$btnLogin', 'LOGIN TO YOUR ACCOUNT');
        $this->http->SetInputValue('ctl00$ContentPlaceHolder1$txtCorreo4got', '');
        $this->http->SetInputValue('ctl00$ContentPlaceHolder1$wucDate$ddlDia', '-1');
        $this->http->SetInputValue('ctl00$ContentPlaceHolder1$wucDate$ddlMes', '-1');
        $this->http->SetInputValue('ctl00$ContentPlaceHolder1$wucDate$ddlAnio', '-1');
        $this->http->SetInputValue('ctl00$ContentPlaceHolder1$hidSocioID', '');

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

        if ($message = $this->http->FindPreg('/(The Email or Card Number and Password you provided do not match\. Please verify and try again\.)/')) {
            throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        $this->http->GetURL('https://friends.krispykremerewards.co.uk/Friends/MyInfo/MyInfo.aspx');
        $firstName = trim($this->http->FindSingleNode('//input[@id=\'ContentPlaceHolder1_wucSignInStep1_txtFName\']/@value'));
        $lastName = trim($this->http->FindSingleNode('//input[@id=\'ContentPlaceHolder1_wucSignInStep1_txtLName\']/@value'));
        // Name
        $this->SetProperty('Name', beautifulName($firstName . ' ' . $lastName));
        // Rewards
        $this->http->GetURL('https://friends.krispykremerewards.co.uk/Friends/MyRewards/CourtesiesView.aspx');
        $rewards = $this->http->XPath->query('//div[@id= "ContentPlaceHolder1_CourtesiesView1_divMyRewards"]//table/tbody/tr');
        $this->logger->debug("Total {$rewards->length} rewards were found");

        foreach ($rewards as $reward) {
            $displayName = $this->http->FindSingleNode('td[1]/span[1]', $reward);
            $readyToRedeem = $this->http->FindSingleNode('td[2]/span[1]', $reward);

            if ($readyToRedeem != 'Yes') {
                $this->logger->notice("skip non redeemable rewards -> '$displayName'");

                continue;
            }
            // Expiration Date
            $date = date_create_from_format('M-j-Y', $this->http->FindSingleNode('td[3]/span[1]', $reward));

            if (!$date || !$displayName) {
                $this->logger->error("something went wrong");

                continue;
            }
            $exp = $date->getTimestamp();
            $this->AddSubAccount([
                'Code'           => 'krispy' . md5($displayName) . $exp,
                'DisplayName'    => $displayName,
                'Balance'        => null,
                'ExpirationDate' => $exp,
            ]);
        }// foreach ($rewards as $reward)

        // set BalanceNA
        if (!empty($this->Properties['Name'])) {
            $this->SetBalanceNA();
        }

        // History request
        if (!$this->http->ParseForm('form1')) {
            return;
        }
        $this->http->SetInputValue('ctl00$ContentPlaceHolder1$CourtesiesView1$btnHistory', 'HISTORY');

        if (!$this->http->PostForm()) {
            return;
        }
        // History check
        if ($message = $this->http->FindSingleNode('//span[@id=\'ContentPlaceHolder1_CourtesiesView1_lblNoHistory\']', null, true, '/There are no records to display\./')) {
            $this->logger->notice($message);
        } else {
            $this->sendNotification("krispy - refs #12664. History was found");
        }
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindSingleNode('//a[contains(@href, "Logout")]/@href')) {
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
