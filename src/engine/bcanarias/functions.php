<?php

class TAccountCheckerBcanarias extends TAccountChecker
{
    private const REWARDS_PAGE_URL = 'https://www.bintercanarias.com/eng/services/points-statement';

    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams();

        return $arg;
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->setDefaultHeader("Accept-Encoding", "gzip, deflate, br");
    }

    public function TuneFormFields(&$arFields, $values = null)
    {
        parent::TuneFormFields($arFields);
        $arFields['Login2']['Options'] = [
            ''              => 'Select your Identification document',
            'userName'      => 'User name',
            'binterMasCard' => 'BinterMás Card',
            'spanishIdNum'  => 'Spanish ID number',
            'nie'           => 'NIE',
            'passport'      => 'Passport',
            'nonSpanichId'  => 'Non-Spanish ID',
        ];
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
        $this->http->GetURL('https://www.bintercanarias.com/eng');

        if (!$this->http->ParseForm('UserHeaderLoginForm')) {
            return $this->checkErrors();
        }

        switch ($this->AccountFields['Login2']) {
            case 'userName':
                $loginType = 'u';

                break;

            case 'spanishIdNum':
                $loginType = 'd';

                break;

            case 'nie':
                $loginType = 't';

                break;

            case 'passport':
                $loginType = 'p';

                break;

            case 'nonSpanichId':
                $loginType = 'x';

                break;

            case 'binterMasCard':
            default:
                $loginType = 'n';

                break;
        }

        $this->http->SetInputValue('data[User][login_type]', $loginType);
        $this->http->SetInputValue('data[User][username]', $this->AccountFields['Login']);
        $this->http->SetInputValue('data[User][password]', $this->AccountFields['Pass']);
        $this->http->SetInputValue('data[User][rememberMe]', '1');
        $this->http->SetInputValue('data[User][redirectUrl]', '');

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

        // Incorrect login or pass
        $message = $this->http->FindSingleNode('//div[contains(@class, "clear-warning")]/div[contains(@id, "flashMessage")]/text()');

        if (!is_null($message)) {
            $this->logger->error("[Error]: {$message}");

            if ($message == "Login details are not correct") {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            return false;
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        if ($this->http->currentUrl() != self::REWARDS_PAGE_URL) {
            $this->http->GetURL(self::REWARDS_PAGE_URL);
        }

        // Name
        $this->SetProperty('Name', beautifulName($this->http->FindSingleNode('//div[contains(@class,"login-block")]/span[contains(@class, "user-name")]')));
        // Status
        $this->SetProperty('Status', $this->http->FindSingleNode('//div[contains(@class,"login-block")]/span[contains(@class, "card-level-")]'));
        // Balance - BinterMás points
        $this->SetBalance($this->http->FindSingleNode('//div[contains(@class,"login-block")]/span[contains(@class, "card-points")]', null, true, '/(\d*)\sptos/'));
        // BinterMas Card Number
        $this->SetProperty('Number', $this->http->FindSingleNode('//h3[contains(., "BinterMás card ")]/span'));
        // Level points
        $this->SetProperty('LevelPoints', $this->http->FindSingleNode('//h3[contains(., "Level points (12M) ")]/span'));

        // if Expire point exist
        if ((int) $this->http->FindSingleNode('//h3[contains(., "Points to expire")]/span') > 0) {
            $this->sendNotification('refs #14001: Expiry of points not 0');
        }

        // Check reservations
        $this->http->GetURL('https://www.bintercanarias.com/OnlineServices/myReservationsAjax/lang:eng?from=&to=&since=&until=&ticketNum=');
        /* Send notification if reservations exists
         * If is logged in successful - return html, where string: No results for search - no reservations.
         */
        if (!$this->http->FindSingleNode('//div[contains(@id, "noReservations")]/p[contains(., "No results for search")]')) {
            $this->sendNotification('refs #14001: Find reservations');
        }
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindSingleNode('//div[contains(@class,"login-block")]/span[contains(@class, "user-name")]')) {
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
