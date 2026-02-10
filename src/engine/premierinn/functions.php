<?php

class TAccountCheckerPremierinn extends TAccountChecker
{
    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->setDefaultHeader("Accept-Encoding", "gzip, deflate, br");
    }

    public function IsLoggedIn()
    {
        if (!isset($this->State['headers']['authorization'])) {
            return false;
        }

        return $this->loginSuccessful();
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL('https://www.premierinn.com/gb/en/account/dashboard.html');

        if (!$this->http->FindSingleNode('//title[contains(., "Premier Inn Dashboard")]')) {
            $this->logger->debug('key not found');

            return $this->checkErrors();
        }

        $data = [
            'client_id'       => 'RMPYTY4kMU1SNvVMqxCeUUmic50HL7fZ',
            'credential_type' => 'http://auth0.com/oauth/grant-type/password-realm',
            'password'        => $this->AccountFields['Pass'],
            'realm'           => 'bart-users-ms-p',
            'username'        => $this->AccountFields['Login'],
        ];
        $headers = [
            "Accept"          => "application/json, text/plain, */*",
            'Content-Type'    => 'application/json',
        ];
        $this->http->RetryCount = 0;
        $this->http->PostURL('https://auth0.premierinn.com/co/authenticate', json_encode($data), $headers);
        $this->http->RetryCount = 2;

        return true;
    }

    public function Login()
    {
        $response = $this->http->JsonLog(null, 3);

        if (!isset($response->login_ticket)) {
            $this->logger->error("login_ticket not found");

            $message = $response->error_description ?? null;
            // Login or pass incorrect
            if ($message == 'Incorrect email/password.') {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            return false;
        }
        
        // Get token page
        $get_request = [
            'client_id'     => 'RMPYTY4kMU1SNvVMqxCeUUmic50HL7fZ',
            'response_type' => 'token id_token',
            'redirect_uri'  => 'https://secure2.premierinn.com/gb/en/common/login.html',
            'scope'         => 'openid profile read:data',
            'audience'      => 'https://wbprod.eu.auth0.com/userinfo',
            'realm'         => 'bart-users-ms-p',
            'nonce'         => 'jip1tQKzY5AJlWK7gkcbWRUBv6vaBhDQ',
            'state'         => 'N1IdgCBSENMw0po8gbzrhS06kJxEnrrj',
            'login_ticket'  => $response->login_ticket,
            'auth0Client'   => 'eyJuYW1lIjoiYW5ndWxhci1hdXRoMCIsInZlcnNpb24iOiIzLjAuNiIsImVudiI6e319',
        ];
        $this->http->GetURL('https://auth0.premierinn.com/authorize?' . http_build_query($get_request));
        // Get token from url
        parse_str(parse_url($this->http->currentUrl(), PHP_URL_FRAGMENT), $output);
        $token = $output['id_token'] ?? null;

        if ($token) {
            $this->State['headers'] = [
                'authorization' => 'Bearer ' . $token,
            ];

            return $this->loginSuccessful();
        }

        $this->logger->error("token not found");

        return $this->checkErrors();
    }

    public function Parse()
    {
        $response = $this->http->JsonLog(null, 0);
        // Name
        $name = beautifulName($response->contactDetail->firstName . ' ' . $response->contactDetail->lastName);
        $this->SetProperty('Name', $name);

        if (!empty($this->Properties['Name'])) {
            $this->SetBalanceNA();
        }

        //Load Booking Page
        $headers = [
            'session-id' => $response->sessionId,
        ];
        $this->http->GetURL('https://api.whitbread.co.uk//customers/hotels/' . $this->AccountFields['Login'] . '/stays?business=false&employeeId=undefined&companyId=undefined&pageIndex=1&pageSize=1000&sortOrder=DEFAULT&includeCheckInBookings=true', $headers);
        $json = $this->http->JsonLog(null, 3, true);
        //Find Booking Records
        $crc = 0;

        if (
            isset($json['totals']['cancelled'])
            && isset($json['totals']['upcoming'])
            && isset($json['totals']['past'])
            && isset($json['totals']['checkedIn'])
        ) {
            $crc = $json['totals']['cancelled']
                + $json['totals']['upcoming']
                + $json['totals']['past']
                + $json['totals']['checkedIn'];
            $this->logger->notice("crc: {$crc}");
        }

        if ($crc > 0) {
            $this->sendNotification('Ref #13519 Found Booking records');
        }
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);
        $this->http->GetURL('https://api.whitbread.co.uk//customers/hotels/' . $this->AccountFields['Login'] . '?business=false', $this->State['headers']);
        $response = $this->http->JsonLog(null, 3);
        $email = $response->contactDetail->email ?? null;
        $this->logger->debug("[Email]: {$email}");

        if ($email && strtolower($email) == strtolower($this->AccountFields['Login'])) {
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
