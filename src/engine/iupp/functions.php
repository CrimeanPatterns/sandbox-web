<?php

class TAccountCheckerIupp extends TAccountChecker
{
    private $headers = [
        'Ocp-Apim-Subscription-Key' => '4c8e61c3270c44bb8d274d090a2a97c5',
        'Accept'                    => 'application/json, text/plain, */*',
        'Accept-Encoding'           => 'gzip, deflate, br',
        'Content-Type'              => 'application/json;charset=UTF-8',
        'Origin'                    => 'https://www.iupp.com.br',
        'Referer-Api'               => 'prod',
    ];

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->RetryCount = 0;
    }

    public function IsLoggedIn()
    {
        $this->logger->notice(__METHOD__);

        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL('https://www.iupp.com.br/auth/login');
        // Get main page
        $this->http->GetURL('https://auth.iupp.com.br/oauth2/authorize?client_id=2raqh4sjj73q10efeguphmk4gn&redirect_uri=https%3A%2F%2Fwww.iupp.com.br%2Fauth%2Fcallback&response_type=token&scope=profile%20email%20openid%20aws.cognito.signin.user.admin%20webpremios.campaigns%2F40455&state=8eea3096d3ee438db0902ec4c58035cd&nonce=5d6abaf4f7464c60bfa76633324b4f91');

        if (!$this->http->FindSingleNode('//body[contains(@class, "body-login")]/@class')) {
            return $this->checkErrors();
        }

        // preAuth part 1 - check for participant (exist or no)
        $headers = [
            'Ocp-Apim-Subscription-Key' => '4c8e61c3270c44bb8d274d090a2a97c5',
            'Accept'                    => 'application/json, text/plain, *\/*',
            'Content-Type'              => 'application/json;charset=UTF-8',
        ];
        $data = [
            'encodedDocumentNumber' => base64_encode($this->AccountFields['Login']),
            'projectId'             => '40455',
        ];
        $this->http->PostURL('https://api.ltm.digital/onboarding/v1/api/participant', json_encode($data), $headers);

        return true;
    }

    public function Login()
    {
        $json = $this->http->JsonLog();
        $statusCode = $json->statusCode ?? null;
        /*
         * If user not registered.
         * Propertry StatusCode in Json struct checked on availables in LoadLoginForm method
         */
        if ($statusCode != 200) {
            $code = $json->errors[0]->code ?? null;
            $message = $json->errors[0]->message ?? null;

            if (
                $code == "113"
                && $message == "Participante não encontrado."
            ) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            return false;
        }// if ($statusCode != 200)

        //Auth part 2 - login
        $data = [
            'cognitoAsfData' => "",
            'username'       => $this->AccountFields['Login'],
            'password'       => $this->AccountFields['Pass'],
        ];
        $query = [
            'client_id'     => '2raqh4sjj73q10efeguphmk4gn',
            'nonce'         => '5d6abaf4f7464c60bfa76633324b4f91',
            'redirect_uri'  => 'https://www.iupp.com.br/auth/callback',
            'response_type' => 'token',
            'scope'         => 'profile email openid aws.cognito.signin.user.admin webpremios.campaigns/40455',
            'state'         => '8eea3096d3ee438db0902ec4c58035cd',
        ];
        $headers = [
            'Accept'       => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
        $this->http->PostURL('https://auth.iupp.com.br/login?' . http_build_query($query), $data, $headers);
        //Parse URL to get access_token and save his in State array
        parse_str(parse_url($this->http->currentUrl(), PHP_URL_FRAGMENT), $output);

        if (!isset($output['access_token'])) {
            $this->logger->error("access_token not found");

            return false;
        }

        $this->State['access_token'] = $output['access_token'];

        if ($this->loginSuccessful()) {
            return true;
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        $json = $this->http->JsonLog();
        // Name
        $this->SetProperty('Name', beautifulName($json->name));

        $headers = $this->headers + [
            'Authorization' => 'Bearer ' . $this->State['access_token'],
        ];
        $this->http->GetURL('https://api.ltm.digital/cloudloyalty/v1/participants/me/simpleBalance', $headers);
        $response = $this->http->JsonLog();
        // Balance
        $this->SetBalance($response->pointsValue);

        $this->http->GetURL('https://api.ltm.digital/cloudloyalty/v1/participants/me/extract', $headers);
        $response = $this->http->JsonLog();

        if (!strstr($this->http->Response['body'], ',"accountHolderId":0,"creditBalance":0.0,"creditCurrencyBalance":0.0,"debitBalance":0.0,"debitCurrencyBalance":0.0,"redeemBalance":0.0,"redeemCurrencyBalance":0.0,"expiredBalance":0.0,"expiredCurrencyBalance":0.0,"lockedBalance":0.0,"lockedCurrencyBalance":0.0}')) {
            $this->sendNotification("need to check properties - refs #19692 // RR");
        }

        if ($this->Balance <= 0) {
            return;
        }

        $this->logger->info("Expiration date", ['Header' => 3]);
        $periods = [
            2,// 1 mes
            4,// 3 meses
            5,// 6 meses
            6,// 1 ano
        ];

        foreach ($periods as $period) {
            $this->http->GetURL('https://api.ltm.digital/cloudloyalty/v1/participants/me/toExpire/'. $period, $headers);
            $response = $this->http->JsonLog();

            if (!$this->http->FindPreg("/^\[\]$/")) {
                $this->sendNotification("need to check exp date - refs #19692 // RR");
            }
        }
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if (!isset($this->State['access_token'])) {
            return false;
        }

        $headers = $this->headers + [
            'Authorization' => 'Bearer ' . $this->State['access_token'],
        ];
        //load main user struct
        $this->http->GetURL('https://api.ltm.digital/cloudloyalty/v1/participants/me', $headers);
        $json = $this->http->JsonLog(null, 3, true);

        if (
            isset($json['username'])
            && $json['username'] == $this->AccountFields['Login']
        ) {
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
