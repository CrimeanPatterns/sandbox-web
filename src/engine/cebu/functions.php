<?php

class TAccountCheckerCebu extends TAccountChecker
{
    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL('https://www.getgo.com.ph/member/quick-enroll-login');

        if (!$this->http->ParseForm('aspnetForm')) {
            return $this->checkErrors();
        }
        $data = [
            "Username" => $this->AccountFields['Login'],
            "Password" => $this->AccountFields['Pass'],
        ];
        $headers = [
            "Accept"                  => "application/json",
            "Accept-Encoding"         => "gzip, deflate, br",
            "Private-Access-Token"    => "null",
            "Public-API-Access-Token" => $this->http->getCookieByName("PublicApiAccessToken"),
            "content-type"            => "application/json",
        ];
        $this->http->RetryCount = 0;
        $this->http->PostURL("https://webapi.getgo.com.ph/api/members/login", json_encode($data), $headers);
        $this->http->RetryCount = 2;

        return true;
    }

    public function checkErrors()
    {
        $this->logger->notice(__METHOD__);
        // Sorry, the website is currently undergoing scheduled maintenance.
        if ($iframe = $this->http->FindSingleNode("//iframe[contains(@src,'/ReceiptHelper/GetGo/maintenance.html')]/@src")) {
            throw new CheckException("Sorry, the website is currently undergoing scheduled maintenance. We will be back soon. Please check back later. Thank you for your patience.", ACCOUNT_PROVIDER_ERROR);
        }
        // maintenance
        if ($this->http->FindSingleNode("//img[@src = 'maintenance/img/maintenance.png']/@src")) {
            throw new CheckException("Our site is undergoing maintenance!", ACCOUNT_PROVIDER_ERROR);
        }

        return false;
    }

    public function Login()
    {
        $response = $this->http->JsonLog();

        if (!empty($response->Data)) {
            $this->http->setCookie("PrivateAccessToken", $response->Data, ".getgo.com.ph");

            return true;
        }
        // catch errors
        if (isset($response->ErrorMessage)) {
            $message = $response->ErrorMessage;
            $this->logger->error($message);

            if (
                $message == 'Invalid username or password.'
                || $message == 'Oops! This account does not seem to be active or to exist. Please try again or contact us to check on this issue.'
                || $message == 'Oops! This account does not seem to be active or to exist. Please try again or contact us via the Help Center to check on this issue.'
                || $message == 'Oops! This account does not exist or is inactive. Please check your login details and try again, or contact us if the issue persists.'
                || $message == 'Account is Inactive'
                || $message == 'Account is Closed'
            ) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            if (
                $message == 'Account is Pending'
            ) {
                throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
            }
        }// if (isset($response->ErrorMessage))

        return $this->checkErrors();
    }

    public function Parse()
    {
        $this->http->GetURL("https://www.getgo.com.ph/member/profile/verification");

        if (
            !$this->http->FindSingleNode('//div[contains(@class, "personal-points")]/span')
            && $this->http->FindSingleNode("//span[contains(text(), 'An unexpected error has occurred.')]")
        ) {
            $this->sendNotification("cebu - retry. See logs");
            sleep(3);
            $this->http->GetURL("https://www.getgo.com.ph/member/profile/verification");
        }

        // Balance - points
        $this->SetBalance($this->http->FindSingleNode('//div[contains(@class, "personal-points")]/span', null, true, self::BALANCE_REGEXP_EXTENDED));
        // Name
        $this->SetProperty('Name', beautifulName($this->http->FindSingleNode('//label[contains(@class, "nav-private-name")]')));
        // GetGo
        $this->SetProperty('CardNumber', $this->http->FindSingleNode('//label[contains(@class, "nav-private-id")]', null, true, "/\:\s*([^<]+)/"));
    }
}
