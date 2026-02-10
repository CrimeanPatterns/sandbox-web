<?php

class TAccountCheckerBarnesnoble extends TAccountChecker
{
    private const REWARDS_PAGE_URL = 'https://www.barnesandnoble.com/account/manage/memberships/associate-membership.jsp';

    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams($targetURL);
        $arg['CookieURL'] = 'https://www.barnesandnoble.com/';

        return $arg;
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->setDefaultHeader("Accept-Encoding", "gzip, deflate, br");
    }

    public function IsLoggedIn()
    {
        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL('https://www.barnesandnoble.com/account/login-frame-ra.jsp?tplName=login&parentUrl=https%3a%2f%2fwww.barnesandnoble.com%2faccount%2fmanage%2fmemberships%2fassociate-membership.jsp&isCheckout=&isNookLogin=&isEgift=&customerkey=&intent=&emailSub=&membershipIDLink=false');

        if (!$this->http->ParseForm('loginForm')) {
            return $this->checkErrors();
        }

        $this->http->SetInputValue('/atg/userprofiling/ProfileFormHandler.value.login', $this->AccountFields['Login']);
        $this->http->SetInputValue('/atg/userprofiling/ProfileFormHandler.value.password', $this->AccountFields['Pass']);
        $this->http->SetInputValue('/atg/userprofiling/ProfileFormHandler.value.autoLogin', "true");

        return true;
    }

    public function Login()
    {
        if (!$this->http->PostForm()) {
            return $this->checkErrors();
        }

        $response = $this->http->JsonLog();
        $success = $response->success ?? null;
        $email = $response->data->uid ?? null;

        $this->logger->debug("[Email]: {$email}");
        $this->logger->debug("[Success]: {$success}");

        if ($success === true && strtolower($email) == strtolower($this->AccountFields['Login'])) {
            return $this->loginSuccessful();
        }

        $message = $response->response->items[0] ?? null;

        if ($message) {
            $this->logger->error("[Error]: {$message}");

            if (strstr($message, 'Your email and password combination does not match our records. Please try again.')) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            $this->DebugInfo = $message;

            return false;
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        // Balance - main balance
        $MainBalanceCurrency = $this->http->FindSingleNode('//p[normalize-space()="REWARDS BALANCE"]/../div/p[contains(@class,"reward-number")]');
        $MainBalanceValue = $this->http->FindSingleNode('//p[normalize-space()="REWARDS BALANCE"]/../div/p[contains(@class,"reward-num")][not (contains(@class,"reward-number"))]');
        $this->SetBalance($MainBalanceCurrency . $MainBalanceValue);

        if ($MainBalanceValue > 0) {
            $this->sendNotification("refs #22770 Balance > 0: $MainBalanceValue");
        }
        // Name
        $this->SetProperty('Name', beautifulName($this->http->FindSingleNode('//p[normalize-space()="Member"]/../p[contains(@class,"member-contents")][1]')));
        // STAMPS BALANCE
        $Stamps = $this->http->FindSingleNode('//p[normalize-space()="STAMPS BALANCE"]/../div/p[contains(@class,"reward-num")]');
        if (!is_null($Stamps))
        {
            $this->AddSubAccount([
                "Code" => "barnesnobleStamps",
                "DisplayName" => "Stamps",
                "Balance" => $Stamps,
            ]);
        }
        // REWARDS REDEEMED
        $RedeemedCurrency = $this->http->FindSingleNode('//*[contains(@class,"d-lg-none")]//p[contains(text(),"REWARDS REDEEMED*")]/../div/p[contains(@class,"reward-number")]');
        $RedeemedValue = $this->http->FindSingleNode('//*[contains(@class,"d-lg-none")]//p[contains(text(),"REWARDS REDEEMED*")]/../div/p[contains(@class,"reward-num")][not(contains(@class,"reward-number"))]');
        $this->SetProperty('Redeemed', $RedeemedCurrency . $RedeemedValue);
        // Stamps till next Reward
        $this->SetProperty('StampsTillNextReward', $this->http->FindSingleNode('//*[contains(@class,"d-lg-none")]//p[contains(@class,"member-contents")]/b[contains(text(),"stamps")]', null, true, "/(\d+) stamps?/ims"));
        // Member Number
        $this->SetProperty('Number', $this->http->FindSingleNode('//*[contains(@class,"d-lg-none")]//p[contains(normalize-space(),"Member Number")]/../p/b'));
        // additional info (MEMBER SINCE)
        $this->SetProperty('MemberSince', $this->http->FindSingleNode('//*[contains(@class,"d-lg-none")]//p[normalize-space()="Member Since"]/../p[contains(@class,"member-contents")]/b'));

        if ($status = $this->http->FindSingleNode("//img[@class = 'image-resize']/@src")) {
            $status = basename($status);
            $this->logger->debug(">>> Status: " . $status);

            switch ($status) {
                case 'RewardsCard@2x.jpg':
                    $this->SetProperty('Status', "Rewards");

                    break;
//
//                case 'PremiumCard@2x.jpg':
//                    $this->SetProperty('Status', "Premium");
//
//                    break;

                default:
                    $this->sendNotification("refs #22770 - newStatus: $status");
            }// switch ($status)
        }// if ($status = $this->http->FindSingleNode("//img[@class = 'image-resize']/@src"))
        $this->giftCardCheck();
    }

    private function giftCardCheck()
    {
        $this->logger->notice(__METHOD__);
        $this->http->RetryCount = 0;
        $this->http->GetURL("https://www.barnesandnoble.com/account/giftcard/manage/", [], 20);
        $this->http->RetryCount = 2;

        if ($this->http->FindSingleNode('//table//td[normalize-space()="You don\'t have any gift cards saved."]') !== "You don't have any gift cards saved.") {
            $this->sendNotification("refs #22770 -  Gift card detected");
        }
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);
        $this->http->RetryCount = 0;
        $this->http->GetURL(self::REWARDS_PAGE_URL, [], 20);
        $this->http->RetryCount = 2;

        if ($this->http->FindSingleNode('//i[contains(text(),"Your Rewards")]')) {
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
