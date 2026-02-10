<?php

use AwardWallet\Common\Selenium\FingerprintFactory;
use AwardWallet\Common\Selenium\FingerprintRequest;
use AwardWallet\Engine\ProxyList;

class TAccountCheckerLner extends TAccountChecker
{
    use ProxyList;
    use SeleniumCheckerHelper;

    private const REWARDS_PAGE_URL = 'https://www.lner.co.uk/';
    /**
     * @var CaptchaRecognizer
     */
    private $recognizer;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->setHttp2(true);
        $this->http->SetProxy($this->proxyReCaptcha());
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
        $this->http->GetURL('https://www.lner.co.uk/quick-registration/');
        // $clientId = $this->http->FindPreg("/\"clientId\":\s*\"([^\"]+)/");
        // $apiKey = $this->http->FindPreg("/\"apiKey\":\s*\"([^\"]+)/");

        if (!$this->http->ParseForm(null, '//form[@id="login-complete-form"]') || !$clientId) {
            return $this->checkErrors();
        }

        $this->seleniumInit();
        $this->seleniumInstance->http->GetURL('https://www.lner.co.uk/quick-registration/');

        $mover = $this->mouseInit();

        $acceptCookiesButton = $this->seleniumInstance->waitForElement(WebDriverBy::xpath('//button[@id="onetrust-accept-btn-handler"]'), 5);

        if ($acceptCookiesButton) {
            $mover->moveToElement($acceptCookiesButton);
            $mover->click();
            // $acceptCookiesButton->click();
        }

        $username = $this->seleniumInstance->waitForElement(WebDriverBy::xpath('//input[@id="lner-authentication-email"]'), 5);
        $password = $this->seleniumInstance->waitForElement(WebDriverBy::xpath('//input[@id="lner-authentication-password"]'), 0);
        $button = $this->seleniumInstance->waitForElement(WebDriverBy::xpath('//button[@id="lner-authentication-sign-in-button"]'), 0);
        $this->saveToLogs($this->seleniumInstance);

        if (!$username || !$password || !$button) {
            return $this->checkErrors();
        }

        // $mover->moveToElement($loginInput);
        // $mover->click();
        // $mover->sendKeys($loginInput, $this->AccountFields['Login'], 7);

        $mover->moveToElement($username);
        $mover->click();
        $this->someSleep();
        $mover->sendKeys($username, $this->AccountFields['Login'], 7);
        $this->someSleep();

        $mover->moveToElement($password);
        $mover->click();
        $this->someSleep();
        $mover->sendKeys($password, $this->AccountFields['Pass'], 7);
        $this->someSleep();

        $mover->moveToElement($button);
        $mover->click();
        $this->someSleep();

        // $username->sendKeys($this->AccountFields['Login']);
        // sleep(1);
        // $password->sendKeys($this->AccountFields['Pass']);
        // sleep(1);
        // $this->saveToLogs($this->seleniumInstance);
        // sleep(1);
        // $button->click();

        // $captcha = $this->parseCaptcha();

        // if (!$captcha) {
        //     return false;
        // }

        // $postData = [
        //     'emailAddress'          => $this->AccountFields['Login'],
        //     'password'              => $this->AccountFields['Pass'],
        //     'reCaptchaVerifyResult' => [
        //         "executionTime" => rand(100, 1500),
        //         "token"         => $captcha,
        //     ],
        // ];

        // $postHeaders = [
        //     "Accept"         => "*/*",
        //     "Accept-Version" => 1,
        //     "Content-Type"   => "application/json",
        //     "Origin"         => "https://www.lner.co.uk",
        //     "Referer"        => "https://www.lner.co.uk/",
        //     "X-Api-Key"      => $apiKey,
        //     "X-Client-Id"    => $clientId,
        // ];

        // $this->http->RetryCount = 0;
        // $this->http->PostURL("https://auth.lner.co.uk/login", json_encode($postData), $postHeaders);
        // $this->http->RetryCount = 2;

        // return true;
    }

    // public function LoadLoginForm()
    // {
    //     $this->http->removeCookies();
    //     $this->http->GetURL('https://www.lner.co.uk/quick-registration/');
    //     $clientId = $this->http->FindPreg("/\"clientId\":\s*\"([^\"]+)/");
    //     $apiKey = $this->http->FindPreg("/\"apiKey\":\s*\"([^\"]+)/");

    //     if (!$this->http->ParseForm(null, '//form[@id="login-complete-form"]') || !$clientId) {
    //         return $this->checkErrors();
    //     }

    //     $captcha = $this->parseCaptcha();

    //     if (!$captcha) {
    //         return false;
    //     }

    //     $postData = [
    //         'emailAddress'          => $this->AccountFields['Login'],
    //         'password'              => $this->AccountFields['Pass'],
    //         'reCaptchaVerifyResult' => [
    //             "executionTime" => rand(100, 1500),
    //             "token"         => $captcha,
    //         ],
    //     ];

    //     $postHeaders = [
    //         "Accept"         => "*/*",
    //         "Accept-Version" => 1,
    //         "Content-Type"   => "application/json",
    //         "Origin"         => "https://www.lner.co.uk",
    //         "Referer"        => "https://www.lner.co.uk/",
    //         "X-Api-Key"      => $apiKey,
    //         "X-Client-Id"    => $clientId,
    //     ];

    //     $this->http->RetryCount = 0;
    //     $this->http->PostURL("https://auth.lner.co.uk/login", json_encode($postData), $postHeaders);
    //     $this->http->RetryCount = 2;

    //     return true;
    // }

    public function Login()
    {
        $response = $this->http->JsonLog(null, 2, true);

        if ($this->loginSuccessful()) {
            $this->captchaReporting($this->recognizer);

            return true;
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        return false;

        if ($this->http->currentUrl() != self::REWARDS_PAGE_URL) {
            $this->http->GetURL(self::REWARDS_PAGE_URL);
        }
        // Balance -
        $this->SetBalance($this->http->FindSingleNode('//li[contains(@id, "balance")]'));
        // Name
        $this->SetProperty('Name', beautifulName($this->http->FindSingleNode('//li[contains(@id, "name")]')));
        // Number
        $this->SetProperty('Number', $this->http->FindSingleNode('//li[contains(@id, "number")]'));

        // Expiration Date
        $this->logger->info('Expiration Date', ['Header' => 3]);
        $exp = $this->http->FindSingleNode("//p[contains(text(), 'Expiration Date')]", null, true, "/expiring on ([^<]+)/ims");
        $expiringBalance = $this->http->FindSingleNode("//p[contains(., 'CashPoints expiring on')]", null, true, "/([\d\.\,]+) CashPoints? expiring/ims");
        // Expiring Balance
        $this->SetProperty("ExpiringBalance", $expiringBalance);

        if ($expiringBalance > 0 && strtotime($exp)) {
            $this->SetExpirationDate(strtotime($exp));
        }
    }

    public function ParseItineraries()
    {
        $result = [];
        $this->http->GetURL('https://www.lner.co.uk/');
        $itineraries = $this->http->XPath->query("//its");
        $this->logger->debug("Total {} itineraries were found");

        foreach ($itineraries as $itinerary) {
            $this->http->GetURL($itinerary->nodeValue);
            $it = $this->parseItinerary();
            $this->logger->debug('Parsed itinerary:');
            $this->logger->debug(var_export($it, true), ['pre' => true]);
            $result[] = $it;
        }

        return $result;
    }

    public function GetHistoryColumns()
    {
        return [
            "Date"        => "PostingDate",
            "Description" => "Description",
            "Bonus"       => "Bonus",
            "Points"      => "Miles",
        ];
    }

    protected function parseCaptcha()
    {
        $this->logger->notice(__METHOD__);
        $key = $this->http->FindPreg("/recaptchaSiteKey\":\s*\"([^\"]+)/");

        if (!$key) {
            return false;
        }

        $this->recognizer = $this->getCaptchaRecognizer();
        $this->recognizer->RecognizeTimeout = 120;
        $parameters = [
            "pageurl"   => $this->http->currentUrl(),
            "proxy"     => $this->http->GetProxy(),
            "version"   => "enterprise",
            //            "version"   => "v3",
            "action"    => "SUBMIT_FORM", // TODO: ?
            "min_score" => 0.9,
            "invisible" => 1,
        ];

        return $this->recognizeByRuCaptcha($this->recognizer, $key, $parameters);
    }

    private function someSleep()
    {
        usleep(random_int(7, 35) * 100000);
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindSingleNode('//a[contains(@href, "logout")]/@href')) {
            return true;
        }

        return false;
    }

    private function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        return false;
    }

    private function parseItinerary()
    {
        $result = [];
        $bookNumber = '';
        $this->logger->info("Parse itinerary #{$bookNumber}", ['Header' => 3]);

        return $result;
    }

    private function seleniumInit()
    {
        $this->logger->notice(__METHOD__);
        $selenium = clone $this;
        $this->http->brotherBrowser($selenium->http);

        $this->logger->notice("Running Selenium...");
        $selenium->UseSelenium();

        $selenium->useFirefox(\SeleniumFinderRequest::FIREFOX_84);
        $request = FingerprintRequest::firefox();
        $request->browserVersionMin = \HttpBrowser::BROWSER_VERSION_MIN - 10;

        $fingerprint = $this->services->get(FingerprintFactory::class)->getOne([$request]);

        if ($fingerprint !== null) {
            $selenium->http->setUserAgent($fingerprint->getUseragent());
        }

        $selenium->http->start();
        $selenium->Start();
        $this->seleniumInstance = $selenium;
    }

    private function saveToLogs($selenium)
    {
        $this->logger->notice(__METHOD__);
        // save page to logs
        $selenium->http->SaveResponse();
        // save page to logs
        $this->http->SetBody($selenium->driver->executeScript('return document.documentElement.innerHTML'));
        $this->http->SaveResponse();
    }

    private function mouseInit()
    {
        $mover = new \MouseMover($this->seleniumInstance->driver);
        $mover->logger = $this->logger;
        $mover->duration = rand(500, 2000);
        $mover->steps = rand(20, 400);
        $mover->enableCursor();

        return $mover;
    }
}
