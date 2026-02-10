<?php

class TAccountCheckerBelly extends TAccountChecker
{
    private const REWARDS_PAGE_URL = 'https://www.bellycard.com/account';

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->setDefaultHeader("Accept-Encoding", "gzip, deflate, br");
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
        $this->http->GetURL('https://www.bellycard.com/users/sign_in');

        if (!$this->http->FindSingleNode('//form[contains(@action, "/users/sign_in")]')) {
            return $this->checkErrors();
        }

        $authenticity_token = $this->http->FindSingleNode('//form[contains(@action, "/users/sign_in")]/input[contains(@name, "authenticity_token")]/@value');

        if (!$authenticity_token) {
            return $this->checkErrors();
        }

        $data = [
            'authenticity_token' => $authenticity_token,
            'user[email]'        => $this->AccountFields['Login'],
            'user[password]'     => $this->AccountFields['Pass'],
        ];

        $this->http->RetryCount = 0;
        $this->http->PostURL('https://www.bellycard.com/users/sign_in', $data);
        $this->http->RetryCount = 2;

        return true;
    }

    public function Login()
    {
        if ($this->loginSuccessful()) {
            return true;
        }

        // login or password incorrect
        if ($message = $this->http->FindSingleNode('//div[contains(@class, "error messaging")]')) {
            $this->logger->error("[Error]: {$message}");

            if (strstr($message, 'Invalid email or password, please try again.')) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            return false;
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        // Name
        $this->SetProperty('Name', $this->http->FindSingleNode('//div[contains(@id, "tab_container")]/span[contains(@class, "greeting") and contains(text(), "My Account")]/span'));
        // Balance - Total Belly Visits
        $this->SetBalance($this->http->FindSingleNode('//div[contains(@class, "num count_checkins")]'));
        // Rewards Redeemed
        $this->SetProperty('RewardsRedeemed', $this->http->FindSingleNode('//div[contains(@class, "num count_redemptions")]'));
        // Belly'd Businesses
        $this->SetProperty('BellydBusinesses', $this->http->FindSingleNode('//div[contains(@class, "num count_businesses")]'));
        // Member Since
        $this->SetProperty('MemberSince', $this->http->FindSingleNode('//div[contains(@id, "usinesses")]/header[contains(@class, "left_right_header")]/div[contains(@class, "addtl addtl-alt") and contains(text(), "Member since")]', null, true, '/Member\ssince\s([\d\w\W]*)/'));

        // Get secret token
        $token = json_decode(urldecode($this->http->getCookieByName('current_user')), true);

        if (!isset($token['access_token'])) {
            return;
        }

        $this->http->GetUrl('https://api.bellycard.com/api/bites?access_token=' . $token['access_token'] . '&expand[]=businesses&expand[]=image&expand[]=reward&expand[]=blurred_image&include_user_claim=true&state=active&per_page=100&claimed=true');
        $bb = $this->http->JsonLog(null, 10, true);

        if (isset($bb['pagination']['total_count']) && $bb['pagination']['total_count'] > 0) {
            foreach ($bb['data'] as $card) {
                if ($card['user_claim']['state'] == 'expired') {
                    continue;
                }

                $subAcc = [
                    'Code'        => 'belly' . $card['user_claim']['stored_reward_id'],
                    'DisplayName' => 'Belly ' . $card['reward']['description'],
                    'Balance'     => null,
                ];

                $exp_date = strtotime($card['user_claim']['expires_at']);

                if ($exp_date) {
                    $subAcc['ExpirationDate'] = $exp_date;
                }

                $this->AddSubAccount($subAcc);
            }
        }
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindSingleNode('//div[contains(@id, "tab_container")]/span[contains(@class, "greeting") and contains(text(), "My Account")]')) {
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
