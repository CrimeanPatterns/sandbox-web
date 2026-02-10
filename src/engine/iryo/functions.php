<?php

use AwardWallet\Common\Parser\Util\PriceHelper;

class TAccountCheckerIryo extends TAccountChecker
{
    use SeleniumCheckerHelper;
    public const WAIT_TIMEOUT = 15;
    public $browser;
    private $token;

    /**
     * @var CaptchaRecognizer
     */
    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->UseSelenium();
        $this->useGoogleChrome(\SeleniumFinderRequest::CHROME_95);

        $this->http->saveScreenshots = true;
    }

    public function IsLoggedIn()
    {
        $this->http->GetURL('https://iryo.eu/es/yo', [], 20);

        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL('https://auth.iryo.eu/auth/realms/ilsa/protocol/openid-connect/auth?client_id=b2c&redirect_uri=https%3A%2F%2Firyo.eu&state=e14cc797-e2f8-430b-b454-a7d87d4355de&response_mode=fragment&response_type=code&scope=openid&nonce=559ecac4-34b8-4a89-b03a-46bd7b250d1c&ui_locales=en&code_challenge=LtbtYRAvT6bZShP28f6a2SvqQNxqKMR-mCgzlTkufV4&code_challenge_method=S256');
        $login = $this->waitForElement(WebDriverBy::xpath('//input[@id="username"]'), self::WAIT_TIMEOUT);
        $pass = $this->waitForElement(WebDriverBy::xpath('//input[@id="password"]'), 0);
        $rememberMe = $this->waitForElement(WebDriverBy::xpath('//label[@for="rememberMe"]'), 0);
        $loginButton = $this->waitForElement(WebDriverBy::xpath('//input[@id="kc-login"]'), 0);
        $this->saveResponse();

        if (!$login || !$pass || !$rememberMe || !$loginButton) {
            $this->logger->error("Failed to find form fields");

            return $this->checkErrors();
        }
        $login->clear();
        $login->sendKeys($this->AccountFields['Login']);
        $pass->sendKeys($this->AccountFields['Pass']);
        $rememberMeChecked = $this->driver->executeScript("document.getElementById('rememberMe').checked");

        if (!$rememberMeChecked) {
            $rememberMe->click();
        }
        $loginButton->click();

        return true;
    }

    public function Login()
    {
        $this->waitForElement(WebDriverBy::xpath('//div[contains(@class, "user-avatar__initials")] | //div[@id="input-error"]'), self::WAIT_TIMEOUT);
        $this->saveResponse();

        if ($this->loginSuccessful()) {
            return true;
        }

        if ($message = $this->http->FindSingleNode('//div[@id="input-error"]')) {
            $this->logger->error($message);

            if (strstr($message, 'Invalid username or password')) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            $this->DebugInfo = $message;

            return $this->checkErrors();
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        $this->parseWithCurl();

        // https://api.loyaltysp.es/user/members/1164952?additionalData=true&addressData=true
        // $this->browser->GetURL("https://iryo.eu/es/account/user");
        // $response = $this->http->JsonLog($this->browser->Response['body'], 3, true);

        $data = $this->getData('https://api.loyaltysp.es/user/members/1164952?additionalData=true&addressData=true');

        // Number
        $this->SetProperty('Number', $data['externalIdentity']);

        // Elite level
        $this->SetProperty('Level', $data['additional_info']['level']['value']['en']);

        // $this->http->GetURL('https://iryo.eu/es/account/user');
        // $nameFullElement = $this->waitForElement(WebDriverBy::xpath('//div[@class="ilsa-account-user__name-summary"]'), self::WAIT_TIMEOUT);
        // $this->saveResponse();

        // if (!$nameFullElement) {
        //     $this->logger->error("Failed to parse user full name");

        //     return;
        // }

        // Name
        $this->SetProperty('Name', beautifulName($data['firstname'] . ' ' . $data['surname1']));

        // $this->http->GetURL('https://iryo-clubyo.loyaltysp.es/home/myIryos');

        // $this->waitForElement(WebDriverBy::xpath('//img[contains(@class, "img-target")]'), self::WAIT_TIMEOUT);
        // $this->saveResponse();

        $data = $this->getData('https://api.loyaltysp.es/user/members/1164952/balance?loyaltyUnitId=16');

        // Balance - iryos
        $iryos = $data[0]['available'];
        $this->SetBalance($iryos);

        // $data = $this->getData('https://api.loyaltysp.es/user/members/1164952/nextlevelrequirementsv2?customerId=1164952&strategy=by_trips_number');

        // $this->SetProperty('SpendToNextLevel', $spendToNextLevelEn ?? $spendToNextLevelEs ?? null);
        // $this->SetProperty('TripsToNextLevel', $TripsToNextLevelEn ?? $TripsToNextLevelEs ?? null);
    }

    public function ParseItineraries()
    {
        $this->http->GetURL('https://iryo.eu/es/my-bookings');
        $ltContainer = $this->waitForElement(WebDriverBy::xpath('//div[@class="ilsa-my-bookings"]'), self::WAIT_TIMEOUT);
        $this->saveResponse();

        if (!$ltContainer) {
            $this->logger->error("Failed to load itineraries page");

            return [];
        }

        $upcomingItinerariesIsPresent = $this->itinerariesIsPresent(1);
        $this->logger->info('Upcoming itineraries is present: ' . (int) $upcomingItinerariesIsPresent);

        if ($upcomingItinerariesIsPresent) {
            $this->parseItinerariesPage();
        }

        $previousItinerariesIsPresent = $this->itinerariesIsPresent(2);
        $this->logger->info('Previous itineraries is present: ' . (int) $previousItinerariesIsPresent);

        if ($previousItinerariesIsPresent && $this->ParsePastIts) {
            $this->parseItinerariesPage();
        }

        // check for the no its
        $seemsNoIts = !$upcomingItinerariesIsPresent && !$previousItinerariesIsPresent;
        $this->logger->info('Seems no itineraries: ' . (int) $seemsNoIts);

        if ($seemsNoIts) {
            $this->itinerariesMaster->setNoItineraries(true);

            return [];
        }

        $cancelledItinerariesIsPresent = $this->itinerariesIsPresent(3);
        $this->logger->info('Cancelled itineraries is present: ' . (int) $cancelledItinerariesIsPresent);

        if (!$this->itinerariesMaster->getNoItineraries() && $cancelledItinerariesIsPresent) {
            $this->parseItinerariesPage();
        }

        return [];
    }

    public function parseWithCurl()
    {
        $this->logger->notice(__METHOD__);
        // parse with curl
        $this->browser = new HttpBrowser("none", new CurlDriver());
        $this->http->brotherBrowser($this->browser);
        $cookies = $this->driver->manage()->getCookies();

        // foreach ($cookies as $cookie) {
        //     $this->browser->setCookie($cookie['name'], $cookie['value'], $cookie['domain'], $cookie['path'], $cookie['expiry'] ?? null);
        // }

        $authData = $this->http->JsonLog($this->driver->executeScript("return localStorage.getItem('ilsa-ui-auth');"), 3, true);
        $this->token = $authData['token'] ?? null;
        $this->logger->debug('set auth token: ' . $this->token);

        $this->browser->LogHeaders = true;
        $this->browser->setProxyParams($this->http->getProxyParams());
    }

    private function itinerariesIsPresent($tabId)
    {
        $tab = $this->waitForElement(WebDriverBy::xpath('(//div[contains(@class,"ilsa-nav-carrousel__item-label")])' . "[$tabId]"));

        if (!$tab) {
            $this->saveResponse();
            $this->logger->error("Failed to open itineraries tab element");

            return false;
        }

        $tab->click();

        $this->waitForElement(WebDriverBy::xpath('//div[@class="ilsa-my-bookings__summary"]'), 3);

        $this->saveResponse();

        if ($this->http->FindSingleNode('//div[@id="without-results"]')) {
            return false;
        }

        return true;
    }

    private function parseItinerariesPage()
    {
        $rootPath = '(//div[@class="ilsa-my-bookings__summary"])';
        $nodes = $this->http->FindNodes($rootPath);
        $this->logger->debug("count nodes: " . count($nodes));

        for ($i = 1; $i <= count($nodes); $i++) {
            $confNo = $this->http->FindSingleNode($rootPath . "[$i]" . '//span[@class="ilsa-my-bookings__title"][2]/text()');
            $this->logger->info("Parse Itinerary #{$confNo}", ['Header' => 3]);

            $t = $this->itinerariesMaster->createTrain();
            $t->addConfirmationNumber($confNo, 'Booking');

            $segments = $this->http->FindNodes($rootPath . "[$i]" . '//div[@class="ilsa-my-bookings__ticket"]');

            for ($si = 1; $si <= count($segments); $si++) {
                $segmentPath = $rootPath . "[$i]" . '//div[@class="ilsa-my-bookings__ticket"]' . "[$si]";
                $s = $t->addSegment();
                $s->setNoNumber(true);
                $s->setDepCode($this->http->FindSingleNode('(' . $segmentPath . '//span[contains(@class,"ilsa-ticket-summary__station-code")])[1]/text()'));
                $s->setDepName($this->http->FindSingleNode('(' . $segmentPath . '//p[contains(@class,"ilsa-ticket-summary__station-name")])[1]/text()'));
                $depDate = $this->ModifyDateFormat($this->http->FindSingleNode($segmentPath . '//h3[@class="ilsa-ticket-summary__travel-train--date-travel"]/text()'));
                $depTime = $this->http->FindSingleNode('(' . $segmentPath . '//h3[@class="ilsa-ticket-summary__base-bold"])[1]/text()');
                $s->parseDepDate("$depDate $depTime");
                $s->setArrCode($this->http->FindSingleNode('(' . $segmentPath . '//span[contains(@class,"ilsa-ticket-summary__station-code")])[2]/text()'));
                $s->setArrName($this->http->FindSingleNode('(' . $segmentPath . '//p[contains(@class,"ilsa-ticket-summary__station-name")])[2]/text()'));
                $arrTime = $this->http->FindSingleNode('(' . $segmentPath . '//h3[@class="ilsa-ticket-summary__base-bold"])[2]/text()');
                $s->parseArrDate("$depDate $arrTime");
                $s->setCarNumber($this->http->FindSingleNode('(' . $segmentPath . '//div[contains(@class, "ilsa-ticket-summary-passenger__ticket--level")]//div[@class="ilsa-ticket-summary-passenger__labelL"])[1]//p[2]', null, true, '/[^A-z\s]+/'));
                $s->addSeat($this->http->FindSingleNode('(' . $segmentPath . '//div[contains(@class, "ilsa-ticket-summary-passenger__ticket--level")]//div[@class="ilsa-ticket-summary-passenger__labelL"])[2]//p[2]', null, true, '/[0-9A-z]+/'));
                $s->setCabin($this->http->FindSingleNode('(' . $segmentPath . '//div[contains(@class, "ilsa-ticket-summary-passenger__ticket--level")]//div[@class="ilsa-ticket-summary-passenger__labelL"])[3]//p[2]', null, true, '/[^0-9\s]+/'));
                $s->setDuration($this->http->FindSingleNode($segmentPath . '//p[contains(@class,"ilsa-ticket-summary__duration-travel")]'));
                $name = $this->http->FindSingleNode($segmentPath . '//div[@class="ilsa-ticket-summary-passenger__name-surname"]/span[1]/text()');
                $surname = $this->http->FindSingleNode($segmentPath . '//div[@class="ilsa-ticket-summary-passenger__name-surname"]/span[2]/text()');
                $t->addTraveller(beautifulName("$name $surname"), true);
                $t->price()->total(PriceHelper::parse($this->http->FindSingleNode('(' . $segmentPath . '//div[contains(@class, "ilsa-ticket-summary-passenger__ticket--level")]//div[@class="ilsa-ticket-summary-passenger__labelL"])[4]//p[2]', null, true, '/[^A-z\s]+/')));
                $t->price()->currency($this->http->FindSingleNode('(' . $segmentPath . '//div[contains(@class, "ilsa-ticket-summary-passenger__ticket--level")]//div[@class="ilsa-ticket-summary-passenger__labelL"])[4]//p[2]', null, true, '/[^A-z0-9,\s]+/'));
            }
        }

        $secondPage = $this->http->FindNodes('//span[@id="page-2"]');

        if (count($secondPage) > 0) {
            $this->sendNotification('refs #22415 need to add a pagination mechanism // IZ');
        }
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);
        $logout = $this->waitForElement(WebDriverBy::xpath('//ilsa-icon[@name="exit-circle"]'), self::WAIT_TIMEOUT, false);
        $this->saveResponse();

        if ($logout) {
            return true;
        }

        return false;
    }

    private function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        return false;
    }

    private function getData(string $url, $logs = 0, $convertToArray = false)
    {
        $this->logger->notice(__METHOD__);
        $this->browser->GetURL($url, [
            'Accept'        => 'application/json, text/plain, */*',
            'token'         => $this->token,
        ]);

        return $this->http->JsonLog(null, $logs, $convertToArray);
    }
}
