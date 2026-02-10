<?php

class TAccountCheckerIherb extends TAccountChecker
{
    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
    }

    public function LoadLoginForm()
    {
        $this->sendNotification("refs #XXXX: New valid account");

        $this->http->removeCookies();
        $this->http->GetURL('https://secure.iherb.com/account/login/');

        if (!$this->http->ParseForm('sign_in_password')) {
            return false;
        }
        $this->http->SetInputValue('Username', $this->AccountFields['Login']);
        $this->http->SetInputValue('Password', $this->AccountFields['Pass']);

        return true;
    }

    public function Login()
    {
        $this->sendNotification('Login: start');

        if (!$this->http->PostForm()) {
            $this->sendNotification('Login: fail post form');

            return false;
        }

        if ($this->loginSuccessful()) {
            $this->sendNotification('Login: end');

            return true;
        }

        $this->sendNotification('Login: error');

        return false;
    }

    public function Parse()
    {
        $this->sendNotification('Parse: start');

        $name = $this->http->FindSingleNode('//div[@class="cust-name col-xs-24"][1][last()]');
        $this->sendNotification($name);

        $this->SetBalance('123');
        $this->SetProperty('Name', 'mn');

        return;

        $this->SetBalance($this->http->FindSingleNode('//li[contains(@id, "balance")]'));
        $this->SetProperty('Name', beautifulName($this->http->FindSingleNode('//li[contains(@id, "name")]')));
        $this->SetProperty('Number', $this->http->FindSingleNode('//li[contains(@id, "number")]'));

        $this->logger->info('Expiration Date', ['Header' => 3]);
        $exp = $this->http->FindSingleNode("//p[contains(text(), 'Expiration Date')]", null, true, "/expiring on ([^<]+)/ims");
        $expiringBalance = $this->http->FindSingleNode("//p[contains(., 'CashPoints expiring on')]", null, true, "/([\d\.\,]+) CashPoints? expiring/ims");
        $this->SetProperty("ExpiringBalance", $expiringBalance);

        if ($expiringBalance > 0 && strtotime($exp)) {
            $this->SetExpirationDate(strtotime($exp));
        }
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        $logoutBtn = $this->http->FindSingleNode('//a[contains(text(), "Sign Out")]');

        return (bool) $logoutBtn;
    }
}
