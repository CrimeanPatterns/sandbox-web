<?php

class TAccountCheckerBwtravelcard extends TAccountChecker
{
    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams($targetURL);
        $arg['SuccessURL'] = 'https://wwws-usa2.givex.com/cws4.0/bwiusd/my-account/manage-cards.html';

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
        $this->http->GetURL('http://travelcard.bestwestern.com/travelcard/index.jsp?lang=en', [], 20);
        $this->http->RetryCount = 2;

        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL('https://wwws-usa2.givex.com/cws4.0/bwiusd/login/');

        if (!$this->http->ParseForm('cws_frm_login')) {
            return $this->checkErrors();
        }

        return true;
    }

    public function Login()
    {
        // Request to dc_958.rpc
        $data = [
            'id'     => 958,
            'params' => [
                'en',
                958,
                'mqid',
                'mqpass',
                $this->AccountFields['Login'],
                $this->AccountFields['Pass'],
                't',
            ],
        ];
        $headers = [
            'Content-Type'     => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ];
        $this->http->RetryCount = 0;
        $this->http->PostURL('https://wwws-usa2.givex.com/cws40_svc/bwiusd/consumer/dc_958.rpc', json_encode($data), $headers);

        $result = $this->http->JsonLog(null, true, true);

        if (empty($result['result']['I4'])) {
            throw new CheckException('Email and/or password invalid', ACCOUNT_INVALID_PASSWORD);
        }

        // Request to dc_948.rpc
        $data = [
            'id'     => 88,
            'params' => [
                'en',
                99,
                'mqid',
                'mqpass',
                $result['result']['I4'],
                '',
            ],
        ];
        $headers = [
            'Content-Type'     => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ];
        $this->http->RetryCount = 0;

        if ($this->http->PostURL('https://wwws-usa2.givex.com/cws40_svc/bwiusd/consumer/dc_948.rpc', json_encode($data), $headers)) {
            return true;
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        $result = $this->http->JsonLog(null, true, true);

        // Cards > 1
        if (count($result['result']['I5']) > 1) {
            $this->sendNotification("refs #5203: Need to check: Cards > 1");
        }

        // Name
        $this->SetProperty('Name', beautifulName($result['result']['I3'] . ' ' . $result['result']['I4']));
        // Balance and Cards
        if (!empty($result['result']['I5'])) {
            // Balance
            $this->SetBalance($result['result']['I5'][0][5]);
            // Card Number
            $this->SetProperty('Number', $result['result']['I5'][0][0] . '*****' . $result['result']['I5'][0][1] . '*');
            // Status
            if ($result['result']['I5'][0][2] == 1) {
                $this->SetProperty('Status', 'Active');
            } else {
                $this->sendNotification("refs #5203: Need to check inactive Card Status");
            }
            // Expiration Date & Balance
            $this->logger->info('Expiration Date', ['Header' => 3]);
            $this->SetProperty("ExpiringBalance", $result['result']['I5'][0][5]);
            $expDate = strtotime($result['result']['I5'][0][8]);

            if ($result['result']['I5'][0][5] > 0 && $expDate) {
                $this->SetExpirationDate($expDate);
                $this->logger->debug(date('>>> d M Y', $expDate));
            }
        }
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindSingleNode('//a[contains(text(), "Sign Out")]')) {
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
