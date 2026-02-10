<?php

class TAccountCheckerJet extends TAccountChecker
{
    public const REWARDS_PAGE_URL = 'https://jet.com/account';

    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams($targetURL);
        $arg['CookieURL'] = 'https://jet.com';

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
        $success = $this->loginSuccessful();
        $this->http->RetryCount = 2;

        if ($success) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL(self::REWARDS_PAGE_URL);

        if (!$this->http->Response['code'] == 200) {
            return $this->checkErrors();
        }

        $session_id = $this->http->FindSingleNode('//div[@data-id="session_id"]/@data-val');
        $csrfToken = $this->http->FindSingleNode('//div[@data-id="csrf"]/@data-val');
        $tiket = $this->http->FindSingleNode('//div[@data-id="loginTicket"]/@data-val');
        $zipCode = $this->http->getCookieByName("ipZipcode");

        if (!$session_id || !$csrfToken || !$tiket || !$zipCode) {
            return $this->checkErrors();
        }

        $data = [
            'email'       => $this->AccountFields['Login'],
            'password'    => $this->AccountFields['Pass'],
            'sensor_data' => '0a07G5m99Vrp4o0c2017129.94-4,9,-36,-998,Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.86 Safari/537.36 (AwardWallet Service. For questions please contact us at https://awardwallet.com/contact),uaend,97276,96452286,ru,Gecko,7,2,8,8,134011,1385808,8031,0790,3498,9709,4157,718,9167,,cpen:6,i3:8,dm:5,cwen:4,non:9,opc:9,fc:2,sc:8,wrc:0,isc:2,vib:9,bat:8,x33:8,x80:2,6391,2.437855538046,191856759360,loc:-3,0,-62,-498,do_en,dm_en,t_en-5,4,-82,-618,-8,6,-02,-887,3,7,6,2,-9,-8,5;3,7,7,2,2990,9615,7;7,2,2,9,8216,-8,6;2,-9,8,8,2721,7856,8;8,-6,3,7,7086,9541,5;4,-8,4,2,9674,-2,9;7,-3,1,9,8004,-8,6;-3,0,-62,-495,-5,4,-82,-623,7,7,9726,457,497;8,5,9601,348,659;8,3,8316,486,239;7,3,5418,128,034;6,2,6305,626,038;7,9,5321,542,814;7,0,4404,176,658;5,9,5175,855,439;7,8,2798,823,306;7,9,8961,795,269;88,2,6352,358,043;62,0,4235,204,829;41,8,1945,092,643;80,5,9805,65,95;81,5,9812,61,83;82,5,9834,69,66;83,5,9841,69,52;84,5,9861,69,59;85,5,9863,69,53;86,5,9866,69,49;97,5,9990,69,39;98,5,9993,69,34;99,5,9997,69,29;90,5,9917,69,25;91,5,9929,61,8;073,7,37385,097,059,5323;084,9,45333,467,069,6231;862,4,95440,375,846,3281;884,6,83444,325,867,4079,7;463,1,09614,938,484,9140;543,0,46761,959,563,7887;045,0,37551,696,064,9978;924,5,32796,787,942,3673,1;039,2,01501,225,105,-8;821,5,23957,724,996,-9;068,3,22946,645,122,-4;-8,6,-02,-892,-0,9,-16,-098,5,10,-7,-3,-0;8,66886,-5,-2,-9;0,36424,-3,-9,-8;-2,1,-58,-288,7,31,-8,-5,-2,-9,-6,-0,-7,-3,-0;8,66874,-5,-2,-9,-6,-0,-7,-3,-0,-9;3,10298,-2,-9,-6,-0,-7,-3,-0,-9,-4;-8,6,-02,-899,-0,9,-16,-080,7,5129;4,31842;9,28353;7,37389;0,22189;4,30040;9,36399;7,44227;0,46541;7,56970;-6,5,-60,-320,-8,7,-81,-537,8,250602,7,93461,27989,3,236065,14273,2846219184500,9062422505922,474,29550,4,409,0465,1,7,99636,340595,9,b6gljc5zh0jqeffe564l_2567,0503,136,3895232067,27925800-0,9,-07,-860,-2,9-8,7,-81,-12,-9471241897;76,36,04,92,79;,3,0;true;true;true;-489;true;11;88;true;false;8-7,4,-73,-65,8238-5,4,-82,-629,2627938-0,9,-07,-872,14358-9,3,-61,-342,;24;26;8',
            'session_id'  => $session_id,
            'ticket'      => trim($tiket, '"'),
            'zipcode'     => trim($zipCode, '"'),
        ];
        //set headers
        $headers = [
            "jet-referer"      => '/register?login',
            "content-Type"     => 'application/json',
            "Accept"           => 'application/json, text/javascript, */*; q=0.01',
            "x-csrf-token"     => trim($csrfToken, '"'),
            "x-requested-with" => 'XMLHttpRequest',
        ];

        //send post
        $this->http->RetryCount = 0;
        $this->State['json'] = $this->http->PostURL('https://jet.com/api/auth/login', json_encode($data), $headers);
        $this->http->RetryCount = 2;

        return true;
    }

    public function Login()
    {
        $response = $this->http->JsonLog();
        $success = $response->success ?? null;

        if ($success === true && $this->loginSuccessful()) {
            return true;
        }

        if (isset($response->error->message)) {
            if ($response->error->code == 400 && $response->error->message == 'downstream request received bad code.') {
                throw new CheckException('This combination of email and password does not match an account on record. Please try again.', ACCOUNT_INVALID_PASSWORD);
            }

            if ($response->error->code == 'locked' && $response->error->message == 'Having trouble logging in? Please wait a little while before you try again.') {
                throw new CheckException('Having trouble logging in? Please wait a little while before you try again.', ACCOUNT_LOCKOUT);
            }
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        // Name
        $this->SetProperty('Name', beautifulName($this->http->FindSingleNode('//body//div[@class="base__Box-sc-1l64hnd-1 hVlLzU"]//div[@class="base__Box-sc-1l64hnd-1 hVlLzU"]//div//span[1]', null, true, '/^Name:\s([\S\s]*)/')));
        // You've Saved
        $this->SetProperty("YouSaved", beautifulName($this->http->FindSingleNode('//span[@class="base__BaseStyledComponent-sc-1l64hnd-0 typography__Text-sc-1lwzhqv-0 ipYZET"]', null, true, "/\\$(.+)/")));
        $balAvailable = $this->http->FindSingleNode('//div[@class="core__Box-sc-1qfvr3i-0 rCqSc"]//div[2]//span[2]//span[1]', null, true, "/\\$(.+)/");
        $balPending = $this->http->FindSingleNode('//div[@class="core__Box-sc-1qfvr3i-0 rCqSc"]//div[3]//span[2]/span[1]', null, true, "/\\$(.+)/");
        $balCredits = $this->http->FindSingleNode('//div[@class="core__Box-sc-1qfvr3i-0 rCqSc"]//div[4]//span[2]/span[1]', null, true, "/\\$(.+)/");
        // Available
        $this->AddSubAccount([
            "Code"        => "jetAvailable",
            "DisplayName" => "Available",
            "Balance"     => $balAvailable,
        ]);
        // Pending
        $this->AddSubAccount([
            "Code"        => "jetPending",
            "DisplayName" => "Pending",
            "Balance"     => $balPending,
        ]);
        // Credits
        $this->AddSubAccount([
            "Code"        => "jetCredits",
            "DisplayName" => "Credits",
            "Balance"     => $balCredits,
        ]);

        if (isset($balAvailable, $balPending, $balCredits)) {
            // Balance - (Available + Pending + Credits)
            $this->SetBalance($balAvailable + $balPending + $balCredits);
        }
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);
        $this->http->GetURL(self::REWARDS_PAGE_URL);

        if ($this->http->FindSingleNode("//a[contains(text(),'Log Out')]")) {
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
