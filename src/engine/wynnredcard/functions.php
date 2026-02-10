<?php

class TAccountCheckerWynnredcard extends TAccountChecker
{
    private const REWARDS_PAGE_URL = 'https://profile.wynnresorts.com/Profile/';

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->setHttp2(true);
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
        $this->http->GetURL(self::REWARDS_PAGE_URL);

        if (!$this->http->ParseForm(null, '//form[@action="/Account/Login"]')) {
            return $this->checkErrors();
        }

        $this->http->SetInputValue('Username', $this->AccountFields['Login']);
        $this->http->SetInputValue('Password', $this->AccountFields['Pass']);
        $this->http->SetInputValue('RememberLogin', "true");

        return true;
    }

    public function Login()
    {
        if (!$this->http->PostForm()) {
            return $this->checkErrors();
        }

        if ($this->http->ParseForm(null, "//form[@action='https://profile.wynnresorts.com']")) {
            $this->http->PostForm();
        }

        if ($this->loginSuccessful()) {
            return true;
        }

        if ($message = $this->http->FindSingleNode('//div[contains(@class, "alert-danger")]/p[2]/text()')) {
            $this->logger->error("[Error]: {$message}");

            if (strstr($message, 'The username or password entered is incorrect')) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            $this->DebugInfo = $message;

            return false;
        }

        return $this->checkErrors();
    }

    public static function FormatBalance($fields, $properties)
    {
        if (isset($properties['SubAccountCode']) && (stristr($properties['SubAccountCode'], 'WinnredcardCompdollars') || stristr($properties['SubAccountCode'], 'WinnredcardFreecreditLasVegasOnly') || stristr($properties['SubAccountCode'], 'WinnredcardFreecreditLasVegasBostonOrWinBet'))) {
            return formatFullBalance($fields['Balance'], $fields['ProviderCode'], "%0.2f");
        }

        return parent::FormatBalance($fields, $properties);
    }

    public function Parse()
    {
        // Name
        $this->SetProperty('Name', beautifulName($this->http->FindSingleNode('//div[@class="rc-user-info-wrap"]/p[1]/text()')));
        // Number
        $Number = $this->http->FindSingleNode('//div[@class="rc-user-info-wrap"]/p[2]/text()');
        $this->SetProperty('Number', $Number);
        // Balance - points
        $this->SetBalance($this->http->FindSingleNode('//p[contains(@aria-label, "Slot Point Balance is")]', null, false, "<[0-9]+>"));
        // Points to next level
        $this->SetProperty('PointsToNextLevel', $this->http->FindSingleNode('//*[contains(text(), "Tier Credits needed by")]/@aria-label', null, false, '<[^A-z\s]+>'));

        // SubAccounts
        // Winnredcard Freecredit Las Vegas, Boston Or WinBet
        $WRCFCLVBWB = $this->http->FindSingleNode('//*[contains(@aria-label, "Las Vegas, Boston, or WynnBET Reward")]/text()', null, false, '<[^$A-z\s]+>');
        // Winnredcard Freecredit Las Vegas only
        $WRCFCLV = $this->http->FindSingleNode('//*[contains(@aria-label, "Las Vegas only")]/text()', null, false, '<[^$A-z\s]+>');
        // Winnredcard Compdollars
        $WRCCD = $this->http->FindSingleNode('//*[contains(@aria-label, "comp dollars gathered ")]/text()', null, false, '<[^$A-z\s]+>');
        // Winnredcard Tier Credits
        $WRCTC = $this->http->FindSingleNode('//*[contains(@aria-label, "tier credits")]', null, false, '<[0-9]+>');

        if (isset($WRCFCLVBWB)) {
            $this->AddSubAccount([
                "Code"        => "WinnredcardFreecreditLasVegasBostonOrWinBet{$Number}",
                "DisplayName" => "Winnredcard Freecredit Las Vegas, Boston Or WinBet",
                "Balance"     => $WRCFCLVBWB,
            ]);
        }

        if (isset($WRCFCLV)) {
            $this->AddSubAccount([
                "Code"        => "WinnredcardFreecreditLasVegasOnly{$Number}",
                "DisplayName" => "Winnredcard Freecredit Las Vegas only",
                "Balance"     => $WRCFCLV,
            ]);
        }

        if (isset($WRCCD)) {
            $this->AddSubAccount([
                "Code"        => "WinnredcardCompdollars{$Number}",
                "DisplayName" => "Winnredcard Compdollars",
                "Balance"     => $WRCCD,
            ]);
        }

        if (isset($WRCTC)) {
            $this->AddSubAccount([
                "Code"        => "WinnredcardTierCredits{$Number}",
                "DisplayName" => "Winnredcard Tier Credits",
                "Balance"     => $WRCTC,
            ]);
        }
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindSingleNode('//a[text()="Sign Out"]')) {
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
