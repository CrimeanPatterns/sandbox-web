<?php

namespace AwardWallet\Engine\asia\RewardAvailability;

use AwardWallet\Common\Selenium\FingerprintFactory;
use AwardWallet\Common\Selenium\FingerprintRequest;
use AwardWallet\Engine\ProxyList;
use SeleniumCheckerHelper;
use WebDriverBy;

class Parser extends \TAccountCheckerAsia
{
    use SeleniumCheckerHelper;
    use \PriceTools;
    use ProxyList;

    private $parser_disabled = true;
    private $local_debug = false;
//    private $local_debug = true;
    private $hot_enabled = true;

    private $isRestartParseRA = false;
    private $warning;

    private $selenium;
    private $cookiesRefreched = false;
    private $accessDenied = false;
    private $fingerprint;

    public static function getRASearchLinks(): array
    {
        return ['https://www.cathaypacific.com/cx/en_GB/book-a-trip/redeem-flights/redeem-flight-awards.html?recent_search=rt&vs=2' => 'search page'];
    }

    public function InitBrowser()
    {
        \TAccountChecker::InitBrowser();
        $this->KeepState = true;
        $this->http->setDefaultHeader("Accept-Encoding", "gzip, deflate, br");

        if ($this->local_debug) {
            $proxy_num = 1;
        } else {
            $proxy_num = random_int(0, 2);
        }

        if ($this->isBackgroundCheck()) {
            $this->parser_disabled = true;
        }

        switch ($proxy_num) {
            case 0:
                $array = ['us', 'gb', 'fr', 'de', 'au', 'fi', 'es'];
                $targeting = $array[random_int(0, count($array) - 1)];
                $this->setProxyBrightData(null, 'static', $targeting);

                break;

            case 1:
                $array = ['fr', 'es', 'de', 'us', 'au', 'gb', 'pt', 'ca'];
                $targeting = $array[random_int(0, count($array) - 1)];

                if ($targeting === 'us' && $this->AccountFields['ParseMode'] === 'awardwallet') {
                    $this->setProxyMount();
                } elseif ($this->AccountFields['ParseMode'] === 'awardwallet') {
                    $this->setProxyGoProxies(null, $targeting);
                } else {
                    $this->setProxyNetNut(null, $targeting);
                }

                break;

            case 2:
                $this->setProxyBrightData(null, 'static', 'kr');

                break;
        }

        $request = FingerprintRequest::chrome();
        $request->browserVersionMin = 100;
        $request->platform = (random_int(0, 1)) ? 'MacIntel' : 'Win32';
        $this->fingerprint = $this->services->get(FingerprintFactory::class)->getOne([$request]);

        if (isset($this->fingerprint)) {
            $this->http->setUserAgent($this->fingerprint->getUseragent());
        }
        $this->KeepState = false;
    }

    public function IsLoggedIn()
    {
        return false;
    }

    public function LoadLoginForm()
    {
        // TODO - ra-awardwallet has no accounts for now
        if (!isset($this->AccountFields['Login'])) {
            return false;
        }

        return true;
    }

    public function Login()
    {
        return true;
    }

    public function getRewardAvailabilitySettings()
    {
        return [
            'supportedCurrencies'      => ['HKD', 'USD'],
            'supportedDateFlexibility' => 0,
            'defaultCurrency'          => 'HKD',
        ];
    }

    public function ParseRewardAvailability(array $fields)
    {
        $this->logger->info("Parse Reward Availability", ['Header' => 2]);
        $this->logger->debug("params: " . var_export($fields, true));

        if ($fields['Currencies'][0] !== 'USD') {
            $fields['Currencies'][0] = $this->getRewardAvailabilitySettings()['defaultCurrency'];
            $this->logger->notice("parse with defaultCurrency: " . $fields['Currencies'][0]);
        }

        if ($fields['DepDate'] > strtotime('+360 day')) {
            $this->SetWarning('The requested departure date is too late.');

            return [];
        }

        $this->http->RetryCount = 0;

        if (!$this->validRouteV2($fields)) {
            return ['routes' => []];
        }
        $this->http->RetryCount = 2;

        if ($this->parser_disabled) {
            $this->logger->info('Parser disabled');

            throw new \CheckException('Parser disabled', ACCOUNT_ENGINE_ERROR);
        }

        $domain = 'cathaypacific';

        $depDate = date('Ymd', $fields['DepDate']);

        $this->selenium($fields, $depDate);

        /// ===============
        if ($this->ErrorCode === ACCOUNT_WARNING) {
            return ['routes' => []];
        }

        $pageBom = $this->http->FindPreg("/pageBom = (\{.+?\}); pageTicket/");
        $this->logger->info("[pageBom]");

        if (!$pageBom) {
            throw new \CheckRetryNeededException(5, 0);
        }

        $pageBom = $this->http->JsonLog($pageBom, 6, false, 'flights');

        if (isset($pageBom->modelObject->messages[0]->code) && in_array($pageBom->modelObject->messages[0]->code, ["5003"])) {
            $this->SetWarning('There are no flights available for the dates you have selected.');

            return ['routes' => []];
        }

        if (!isset($pageBom->modelObject->availabilities->upsell->associations)
            || !isset($pageBom->modelObject->availabilities->upsell->recommendations)) {
            if (isset($pageBom->modelObject->availabilities)
                && isset($pageBom->modelObject->availabilities->calendar)
                && isset($pageBom->modelObject->availabilities->calendar->itineraryRecommendations)
                && !empty($pageBom->modelObject->availabilities->calendar->itineraryRecommendations)
                && !property_exists($pageBom->modelObject->availabilities->calendar->itineraryRecommendations, $depDate)
            ) {
                $this->SetWarning('There are no flights available for the dates you have selected.');

                return ['routes' => []];
            }

            if ($this->http->FindPreg("/We are having difficulty with the request as submitted.Please try again or contact us if the problem persists. \(3006\)/") && $this->attempt === 2) {
                throw new \CheckException($pageBom->modelObject->messages[0]->text, ACCOUNT_PROVIDER_ERROR);
            }
            // it's always no flights. but there's no message
            throw new \CheckRetryNeededException(5, 3);
        }

        if (!isset($pageBom->modelObject->availabilities->upsell->associations)
            || !isset($pageBom->modelObject->availabilities->upsell->recommendations)) {
            $this->sendNotification('check parse // ZM');
            $this->logger->warning($this->warning);

            throw new \CheckException('wrong answer', ACCOUNT_ENGINE_ERROR);
        }

        if ($this->http->FindPreg("/We are having difficulty with the request as submitted.Please try again or contact us if the problem persists. \(3006\)/")) {
            throw new \CheckRetryNeededException(5, 0);
        }
        $requestParams = $this->http->FindPreg("/requestParams = JSON.parse\(JSON\.stringify\('(\{.+?\})'\)\);/");
        $this->logger->info("[requestParams]");
        $requestParams = $this->http->JsonLog($requestParams, 2, true);

        if (isset($pageBom->modelObject->availabilities) && !isset($pageBom->modelObject->availabilities->upsell)) {
            $this->SetWarning('There are no flights available for the dates you have selected.');

            return ['routes' => []];
        }

        $url = $this->http->FindPreg('/src="([^"]+common\/js\/constants\/constant\.js)"/');

        if ($url && strpos($url, 'https://book') === false) {
            $url = "https://book.cathaypacific.com/" . $url;
//            $this->http->NormalizeURL($url);
        } else {
            $url = 'https://book.' . $domain . '.com/CathayPacificAwardV3/JULY_2023_NEW.7/common/js/constants/constant.js';
        }

        $this->http->GetURL($url);
        $config = $this->http->JsonLog($this->http->FindPreg("/'CX_GLOBAL_CONFIG',(\{.+?\})\);/s"), 0);

        if (empty($config)) {
            $config = \Cache::getInstance()->get('ra_asia_config');

            if (false === $config) {
                $this->sendNotification("constant.js");

                throw new \CheckException('can\'t find constant.js', ACCOUNT_ENGINE_ERROR);
            }
        } else {
            \Cache::getInstance()->set('ra_asia_config', $config, 60 * 60 * 2); // 2 hours
        }

        $this->http->RetryCount = 2;

        return [
            "routes" => $this->parseRewardFlights($pageBom, $requestParams, $config,
                $domain, $fields),
        ];
    }

    public function selenium($fields, $depDate)
    {
        $this->logger->notice(__METHOD__);
        $retry = false;

        $selenium = clone $this;
        $this->http->brotherBrowser($selenium->http);

        try {
            $this->logger->notice("Running Selenium...");
            $selenium->UseSelenium();

            if ($this->local_debug) {
                $selenium->useGoogleChrome(\SeleniumFinderRequest::CHROME_99);
            } else {
                $selenium->useChromePuppeteer(\SeleniumFinderRequest::CHROME_PUPPETEER_103);
            }

            $selenium->http->saveScreenshots = true;

            if (isset($this->fingerprint)) {
                $selenium->seleniumOptions->fingerprint = $this->fingerprint->getFingerprint();
            }
            $selenium->seleniumOptions->userAgent = $this->http->getDefaultHeader('User-Agent');
            $selenium->KeepState = false;

            if ($this->hot_enabled) {
                $selenium->seleniumRequest->setHotSessionPool(
                    self::class,
                    $this->AccountFields['ProviderCode'],
                    $this->AccountFields['AccountKey'] ?? null
                );
            }

            try {
                $selenium->http->start();
                $selenium->Start();
                $selenium->driver->manage()->window()->maximize();
            } catch (\Exception $e) {
                // retry helped with ... no need notify
                if (strpos($e->getMessage(), 'Error response from daemon: No such image') === false
                    && strpos($e->getMessage(), 'session not created: Post') === false
                    && strpos($e->getMessage(), 'all selenium servers are busy') === false
                    && strpos($e->getMessage(), 'New session attempts retry count exceeded') === false
                    && strpos($e->getMessage(), 'session not created: wait') === false
                ) {
                    $this->logger->error("Exception on selenium start: " . $e->getMessage(), ['HtmlEncode' => true]);
                    $this->logger->error($e->getTraceAsString(), ['HtmlEncode' => true]);
                    $this->sendNotification('exception on selenium start // ZM');
                }

                if (strpos($e->getMessage(), 'all selenium servers are busy') !== false) {
                    throw new \CheckRetryNeededException(5, 0);
                }

                try {
                    $selenium->http->start();
                    $selenium->Start();
                } catch (\Exception $e) {
                    throw new \CheckRetryNeededException(5, 0);
                }
            }

            // ============================ random pages ============================
            /*
            $curUrl = $selenium->http->currentUrl();

            if (!is_string($curUrl) || (strpos($curUrl, 'cathaypacific.com') === false)) {
                $this->http->GetURL('https://www.cathaypacific.com/cx/en_US.html');

                if ($this->isBadProxy()) {
                    throw new \CheckRetryNeededException(5, 0);
                }
                $this->saveResponse();
                $menu_links = $this->http->FindNodes("//a[contains(@href, 'cxsource')]/@href");

                foreach ($menu_links as $key => $one) {
                    if (strpos($one, '.mp4') !== false) {
                        unset($menu_links[$key]);
                    }
                }
                $tabs = rand(3, 4);

                $urls = array_rand($menu_links, $tabs);

                $this->logger->debug('tabs_count=' . $tabs);

                foreach ($urls as $url_id) {
                    $this->logger->debug('url_id=' . $url_id);
                    $url = $menu_links[$url_id];
                    $this->logger->debug('url_tab=' . $url);
                    $selenium->driver->executeScript("window.open('" . $url . "','_blank');", []);
                    sleep(2);
                    $this->someSleep();
                }

                sleep(2);
            }
            */
            // ============================

            $selenium->http->GetURL('https://www.cathaypacific.com/cx/en_US/membership/my-account.html');

            if ($selenium->waitForElement(WebDriverBy::xpath("
            //span[contains(text(), 'This site can’t be reached')]
            |//h1[contains(text(), 'Access Denied')]
            "), 5)
            ) {
                $this->markProxyAsInvalid();

                throw new \CheckRetryNeededException(5, 0);
            }

            if (filter_var($this->AccountFields['Login'], FILTER_VALIDATE_EMAIL) === false) {
                $selenium->driver->manage()->addCookie(['name' => 'lastLoginType', 'value' => '2', 'domain' => "www.cathaypacific.com"]);
            } else {
                $selenium->driver->manage()->addCookie(['name' => 'lastLoginType', 'value' => '1', 'domain' => "www.cathaypacific.com"]);
            }
//            $selenium->driver->manage()->addCookie(['name' => 'ak_bmsc', 'value' => '1', 'domain' => "www.cathaypacific.com"]);

            // Если авторизация уже прошла то всё-равно считать keepSession
            if (!$selenium->waitForElement(\WebDriverBy::xpath("//span[contains(@class,'welcomeLabel')][starts-with(normalize-space(),'Welcome,')]"), 10)) {
                $this->someSleep();

                if (!$this->LoginCathay($selenium)) {
                    throw new \CheckRetryNeededException(5, 0);
                }
            }

            $asiaCabinCode = $this->getCabin($fields['Cabin'], true);

            $this->seleniumSearch($selenium, $fields, $asiaCabinCode, $depDate);

            if ($this->hot_enabled) {
                $this->logger->notice('Save session'); // no exceptions - ok
                $selenium->keepSession(true);
            }
        } catch (\WebDriverCurlException | \WebDriverException $e) {
            $this->logger->error("WebDriverCurlException exception: " . $e->getMessage());
            // retries
            if ((strpos($e->getMessage(), '/session') !== false
                || strpos($e->getMessage(), 'remote response failed') !== false)
                && $this->ErrorCode !== ACCOUNT_WARNING // иногда трейс на savePage, при этом уже собрал, что нет маршрута
            ) {
                $retry = true;
            }
        } catch (\NoSuchDriverException |
        \UnknownServerException |
        \NoSuchWindowException |
        \TimeOutException |
        \SessionNotCreatedException $e) {
            $this->logger->error("exception: " . $e->getMessage());
            $this->DebugInfo = "exception";
            // retries
            $retry = true;
        } catch (\UnexpectedJavascriptException $e) {
            $this->logger->error("UnexpectedJavascriptException exception: " . $e->getMessage());
            $this->DebugInfo = "UnexpectedJavascriptException";
            // retries
            if (
                strpos($e->getMessage(), 'TypeError: document.documentElement is null') !== false
                || strpos($e->getMessage(), 'ReferenceError: $ is not defined') !== false
            ) {
                $retry = true;
            }
        } finally {
            $selenium->http->cleanup(); //todo

            // retries
            if ($retry && ConfigValue(CONFIG_SITE_STATE) != SITE_STATE_DEBUG) {
                $this->logger->debug("[attempt]: {$this->attempt}");

                throw new \CheckRetryNeededException(5, 0);
            }
        }
    }

    public function LoginCathay($selenium, $isRetry = false)
    {
        $this->logger->notice(__METHOD__);
        $mlc_prelogin = null;

        [$login, $pwd] = $this->getLoginFormFields($selenium, "https://www.cathaypacific.com/cx/en_US/sign-in.html");

        if (!$login && $selenium->waitForElement(WebDriverBy::xpath("//div[normalize-space()='Error loading chunks!']"))) {
            [$login, $pwd] = $this->getLoginFormFields($selenium, "https://www.cathaypacific.com/cx/en_US/sign-in.html?switch=Y");
        }

        if ($isRetry) {
            $login->clear();
            $pwd->clear();
        }

        $mouse = rand(0, 1);

        if ($mouse) {
            $this->logger->debug('mousemover:  true');
            $mover = new \MouseMover($selenium->driver);
            $mover->logger = $this->logger;
            $mover->duration = rand(300, 1000);
            $mover->steps = rand(10, 20);
            $this->checkCookieButton($selenium);

            $mover->moveToElement($login);
            $mover->click();
            $mover->sendKeys($login, $this->AccountFields['Login'], 7);
            $this->checkCookieButton($selenium);

            $mover->moveToElement($pwd);
            $mover->click();
            $mover->sendKeys($pwd, $this->AccountFields['Pass'], 7);

            $this->checkCookieButton($selenium);

            $btn = $selenium->waitForElement(WebDriverBy::xpath("//button[normalize-space()='Sign in']"), 0);
            $mover->moveToElement($btn);
            $mover->click();
            $this->checkCookieButton($selenium);
        } else {
            $this->logger->debug('mousemover:  false');
            $login->click();
            $pwd->click();
            $login->click();
            $login->sendKeys($this->AccountFields['Login']);
            $this->someSleep();
            $pwd->sendKeys($this->AccountFields['Pass']);
            $this->someSleep();
            $this->savePageToLogs($selenium);
            $btn = $selenium->waitForElement(WebDriverBy::xpath("//button[normalize-space()='Sign in']"), 0);

            if (!$btn) {
                throw new \CheckRetryNeededException(5, 0, 'page failed', ACCOUNT_ENGINE_ERROR);
            }
            $btn->click();
        }

        $res = $selenium->waitForElement(WebDriverBy::xpath("
                //span[contains(@class,'welcomeLabel')][starts-with(normalize-space(),'Welcome,')] 
                | //h2[contains(text(), 'Confirm your mobile phone number') or contains(text(), 'We need to verify your identity')] 
                | //label[contains(@class, 'textfield__errorMessage')] 
                | //span[@class='bookingPanel__radioGroup__radioLabel'][starts-with(normalize-space(),'Book flights')] 
                | //div[contains(@class, 'serverSideError__messages')] 
                | //h1[contains(text(), 'Access Denied')]"), 25);

        $this->savePageToLogs($selenium);

        if ($msg = $this->http->FindSingleNode("//div[contains(@class, 'serverSideError__messages')]")) {
            if (strpos($msg, 'Your sign-in details are incorrect. Please check your details and try again.') !== false) {
                throw new \CheckRetryNeededException(5, 5);
            }

            if (strpos($msg, 'Your member account is temporarily locked after too many unsuccessful login attempts. You can reset your password by confirming your personal information') !== false) {
                throw new \CheckException($msg, ACCOUNT_PREVENT_LOCKOUT);
            }

            if (strpos($msg, 'We\'re currently experiencing technical difficulties. Please try again later') !== false) {
                throw new \CheckException($msg, ACCOUNT_PROVIDER_ERROR);
            }

            if (strpos($msg, 'Inactive Membership number. Please contact our Global Contact Centres for assistance') !== false) {
                throw new \CheckException($msg, ACCOUNT_LOCKOUT);
            }
            $this->sendNotification('check error');

            throw new \CheckException($msg, ACCOUNT_ENGINE_ERROR);
        }

        if ($selenium->waitForElement(WebDriverBy::xpath("//label[contains(@class, 'textfield__errorMessage')]"), 0) && !$isRetry) {
            // it's help
            return $this->LoginCathay($selenium, true);
        }

        if (!isset($res)) {
            throw new \CheckException('unknown page format (not loaded)', ACCOUNT_ENGINE_ERROR);
        }

        if (strpos($res->getText(), 'Access Denied') !== false && !$isRetry) {
            // it's help
            return $this->LoginCathay($selenium, true);
        }

        if (strpos($res->getText(), 'Access Denied') !== false) {
            $this->markProxyAsInvalid();

            throw new \CheckRetryNeededException(5, 0);
        }

        if ($this->http->FindSingleNode("//h2[contains(text(), 'Confirm your mobile phone number') or contains(text(), 'We need to verify your identity')]")) {
            $later = $selenium->waitForElement(WebDriverBy::xpath("//a[contains(normalize-space(),'Remind me later')]"), 0);

            if (!$later) {
                throw new \CheckException('something wrong', ACCOUNT_ENGINE_ERROR);
            }
            $later->click();
            $selenium->waitForElement(WebDriverBy::xpath("
                    //span[contains(@class,'welcomeLabel')][starts-with(normalize-space(),'Welcome,')] 
                    | //label[contains(@class, 'textfield__errorMessage')] 
                    | //h1[contains(text(), 'Access Denied')]"), 15);
            $this->savePageToLogs($selenium);
        }

        if ($this->http->FindSingleNode("//h1[contains(text(), 'Access Denied')]")) {
            $this->markProxyAsInvalid();

            throw new \CheckRetryNeededException(5, 0);
        }

        $cookies = $selenium->driver->manage()->getCookies();

        foreach ($cookies as $cookie) {
            if ($cookie['name'] === 'mlc_prelogin') {
                $mlc_prelogin = $cookie['name'];
            }
        }

        return $mlc_prelogin;
    }

    protected function isBackgroundCheck()
    {
        return isset($this->AccountFields['Partner'])
            && (
                // for ra-aw doesn't matter
//                ($this->AccountFields['Partner'] == 'awardwallet' && $this->AccountFields['Priority'] < 7) ||
                ($this->AccountFields['Partner'] === 'juicymiles' && $this->AccountFields['Priority'] < 9)
            );
    }

    private function checkCookieButton($selenium)
    {
        if ($btnCookies = $selenium->waitForElement(WebDriverBy::xpath("//button[normalize-space()='Agree to all']"),
            0)) {
            $this->sendNotification('btn // ZM');
            $btnCookies->click();
        }
    }

    private function getLoginFormFields($selenium, $url)
    {
        $wait = 0;

        if (filter_var($this->AccountFields['Login'], FILTER_VALIDATE_EMAIL) === false) {
            $typeLogin = 'membernumber';
        } else {
            $typeLogin = 'email';
        }

        $curUrl = $selenium->http->currentUrl();

        if (!is_string($curUrl)) {
            $this->logger->debug(var_export($curUrl, true));

            if (is_array($curUrl) && isset($curUrl['error']) && $curUrl['error'] === 'invalid session id') {
                throw new \CheckRetryNeededException(5, 0);
            }
            $this->sendNotification("selenium->http->currentUrl() == array // ZM");
        }

        if (!is_string($curUrl) || (strpos($curUrl, 'sign-in.html') === false)) {
            $selenium->http->GetURL($url);
            $wait = 15;
        }

        $login = $selenium->waitForElement(WebDriverBy::xpath("//input[@name='{$typeLogin}']"), $wait);
        $pwd = $selenium->waitForElement(WebDriverBy::xpath("//input[@name='password']"), $wait);

        if (!$login || !$pwd) {
            $selenium->http->GetURL($url);
            $wait = 15;
            $login = $selenium->waitForElement(WebDriverBy::xpath("//input[@name='{$typeLogin}']"), $wait);
            $pwd = $selenium->waitForElement(WebDriverBy::xpath("//input[@name='password']"), $wait);

            if (!$login || !$pwd) {
                $this->logger->error("login field(s) not found");

                throw new \CheckRetryNeededException(5, 0);
            }
        }

        return [$login, $pwd];
    }

    private function getCabin(string $str, bool $asiaCabinCode)
    {
        $cabins = [
            'economy'        => 'Y', //ECONOMY
            'premiumEconomy' => 'W', //PREMIUM ECONOMY  ?? N
            'business'       => 'C', //BUSINESS
            'firstClass'     => 'F', //FIRST
        ];

        if (!$asiaCabinCode) {
            $cabins = array_flip($cabins);
        }

        if (isset($cabins[$str])) {
            return $cabins[$str];
        }
        $this->sendNotification("RA check cabin {$str} (" . var_export($asiaCabinCode, true) . ") // MI");

        throw new \CheckException("check cabin {$str} (" . var_export($asiaCabinCode, true) . ")", ACCOUNT_ENGINE_ERROR);
    }

    private function getCabin2(string $str, bool $asiaCabinCode)
    {
        $cabins = [
            'economy'        => 'ECO', //ECONOMY
            'premiumEconomy' => 'PEY', //PREMIUM ECONOMY
            'business'       => 'BUS', //BUSINESS
            'firstClass'     => 'FIR', //FIRST
        ];

        if (!$asiaCabinCode) {
            $cabins = array_flip($cabins);
        }

        if (isset($cabins[$str])) {
            return $cabins[$str];
        }
        $this->sendNotification("RA check cabin 2 {$str} (" . var_export($asiaCabinCode, true) . ") // MI");

        throw new \CheckException("check cabin 2 {$str} (" . var_export($asiaCabinCode, true) . ")", ACCOUNT_ENGINE_ERROR);
    }

    private function getService(string $str)
    {
        $cabins = [
            'ECO' => 'Economy',
            'PEY' => 'Premium Economy',
            'BUS' => 'Business',
            'FIR' => 'First',
        ];

        if (isset($cabins[$str])) {
            return $cabins[$str];
        }
        $this->sendNotification("RA check service {$str} // ZM");

        return null;
    }

    private function getCabinFromRBD($config, $carrier, $rbd)
    {
        $name = 'ONEWORLD.RBD.PARTNER.' . strtoupper($carrier);
        $this->logger->debug("{$name} -> {$rbd}");
        $partnerCabinObject = json_decode($config->{$name});
        $this->logger->debug(var_export($partnerCabinObject, true));
        $cabin = '';
        // 'FIR' => array ( 0 => 'A', 1 => 'E', ),
        foreach ($partnerCabinObject as $key => $value) {
            if (in_array($rbd, $value)) {
                $cabin = $key;
            }
        }

        return $cabin;
    }

    private function isDowngradable($shortAskedFF, $cabin)
    {
        $val = "";

        if ($shortAskedFF === "BUS" && $cabin === "FIR") {
            $val = "upgrade";
        } else {
            if ($shortAskedFF === "BUS" && $cabin !== $shortAskedFF) {
                $val = "downgrade";
            } else {
                if ($shortAskedFF === "FIR" && $cabin !== $shortAskedFF) {
                    $val = "downgrade";
                } else {
                    if ($shortAskedFF === "ECO" && $shortAskedFF != $cabin) {
                        $val = "upgrade";
                    } else {
                        if ($shortAskedFF === "PEY" && $cabin === "ECO") {
                            $val = "downgrade";
                        } else {
                            if ($shortAskedFF === "PEY" && $shortAskedFF != $cabin) {
                                $val = "upgrade";
                            }
                        }
                    }
                }
            }
        }

        return $val;
    }

    private function parseRewardFlights($pageBom, $requestParams, $config, $domain, $fields = []): array
    {
        $this->logger->notice(__METHOD__);
        $this->logger->info("ParseReward [" . $fields['DepDate'] . "-" . $fields['DepCode'] . "-" . $fields['ArrCode'] . "]",
            ['Header' => 2]);

        $routes = [];
        $milesInfoList = [];

        foreach ($pageBom->modelObject->availabilities->upsell->associations as $key => $associations) {
            $rbdList = $pageBom->modelObject->availabilities->upsell->recommendations->{$associations->recoId}->rbdsPerBound;

            $associationCabin = preg_replace('/STD_\d+/', '', $key);
            $this->logger->debug("{$key} -> {$associationCabin}");

            $cabinMain = $this->getCabin2($associationCabin, false);

            // recommendation
            if (!isset($pageBom->modelObject->availabilities->upsell->recommendations->{$associations->recoId})) {
                $this->sendNotification("RA check availabilities->upsell->recommendations // MI");

                throw new \CheckException("check availabilities->upsell->recommendations", ACCOUNT_ENGINE_ERROR);
            }
            $rec = $pageBom->modelObject->availabilities->upsell->recommendations->{$associations->recoId};

            if (count($associations->boundAssociations) != 1) {
                $this->sendNotification("RA check associations->boundAssociation // MI");

                throw new \CheckException("check associations->boundAssociation", ACCOUNT_ENGINE_ERROR);
            }

            foreach ($associations->boundAssociations as $boundAssociations) {
                foreach ($pageBom->modelObject->availabilities->upsell->bounds as $bounds) {
                    // Flights
                    foreach ($bounds->flights as $flight) {
                        if ($boundAssociations->flightId != $flight->id) {
                            continue;
                        }

                        $route = [
                            'num_stops'   => null,
                            'distance'    => null,
                            'times'       => ['flight' => null, 'layover' => null],
                            'redemptions' => [
                                'miles'   => null,
                                'program' => $this->AccountFields['ProviderCode'],
                            ],
                            'payments'    => [
                                'currency' => $rec->recommendationPrice->pricePerTravellerTypes->ADT->priceForOneTravellerOfThisType->totalTaxes->cashAmount->currency,
                                'taxes'    => $rec->recommendationPrice->pricePerTravellerTypes->ADT->priceForOneTravellerOfThisType->totalTaxes->cashAmount->amount,
                                'fees'     => null,
                            ],
                            'connections' => [],
                            'tickets'     => null,
                            'award_type'  => null,
                        ];
                        $segmentOfStops = 0;
                        $cabinSegmentList = [];

                        foreach ($flight->segments as $segmentKey => $segment) {
                            $code = $rbdList[0]->segmentRBDs->{$segmentKey}->code;
                            $cabinRDB = $this->getCabinFromRBD($config, $segment->flightIdentifier->marketingAirline, $code);
                            $this->logger->debug("cabinRDB: $cabinRDB, segmentRBD: " . $code);

                            if (empty($cabinRDB)) {
                                $warningMsg = 'Flight is fully booked.';
                                $this->logger->notice('Skip: Flight is fully booked.');
                                $this->logger->debug('');

                                continue 2;
                            }
                            $segmentOfStops += $segment->numberOfStops;
                            $status = $this->isDowngradable($associationCabin, $cabinRDB);
                            $this->logger->warning("status: $status");

                            if ($status === 'upgrade' || $status === "downgrade") {
                                $cabin = $this->getCabin2($cabinRDB, false);
                            } else {
                                $cabin = $cabinMain;
                            }
                            $cabinSegmentList[] = $cabinRDB;

                            foreach ($pageBom->dictionaries->values as $dicKey => $value) {
                                if (isset($pageBom->dictionaries->values->{$dicKey}->{$segment->equipment}->name)) {
                                    $segment->equipment = $pageBom->dictionaries->values->{$dicKey}->{$segment->equipment}->name;

                                    break;
                                }
                            }
                            $origin = explode('_', $segment->originLocation);
                            $destination = explode('_', $segment->destinationLocation);
                            $route['connections'][] = [
                                'num_stops'  => $segment->numberOfStops,
                                'departure'  => [
                                    'date'     => date('Y-m-d H:i', $segment->flightIdentifier->originDate / 1000),
                                    'dateTime' => $segment->flightIdentifier->originDate / 1000,
                                    'airport'  => $origin[1],
                                    'terminal' => preg_replace('/^T/', '', $origin[0]),
                                ],
                                'arrival'    => [
                                    'date'     => date('Y-m-d H:i', $segment->destinationDate / 1000),
                                    'dateTime' => $segment->destinationDate / 1000,
                                    'airport'  => $destination[1],
                                    'terminal' => preg_replace('/^T/', '', $destination[0]),
                                ],
                                'meal'       => null,
                                'cabin'      => $cabin,
                                'fare_class' => null,
                                'flight'     => ["{$segment->flightIdentifier->marketingAirline}{$segment->flightIdentifier->flightNumber}"],
                                'airline'    => $segment->flightIdentifier->marketingAirline,
                                'operator'   => $segment->flightIdentifier->marketingAirline,
                                'distance'   => null,
                                'aircraft'   => $segment->equipment,
                                'times'      => ['flight' => null, 'layover' => null],
                            ];
                        }
                        $route['flightIdString'] = $flight->flightIdString . implode(':', $cabinSegmentList);
                        $cabinRDBs = array_unique($cabinSegmentList);

                        $route['classOfService'] = null;

                        if (count($cabinRDBs) > 1) {
                            $minCabinRDBs = null;

                            foreach (['ECO', 'PEY', 'BUS', 'FIR'] as $cabinRDB) {
                                if (in_array($cabinRDB, $cabinRDBs)) {
                                    $minCabinRDBs = $cabinRDB;

                                    break;
                                }
                            }

                            if (isset($minCabinRDBs)) {
                                $route['classOfService'] = $this->getService($minCabinRDBs);
                                // TODO need to check
                                // maybe post https://book.cathaypacific.com/CathayPacificAwardV3/dyn/air/booking/backTrackMilesChecksum?TAB_ID={$requestParams['TAB_ID']}
                                // externalID из $requestParams итд
                                // checksum(один на всех) => post 	https://api.cathaypacific.com/redibe/backTracking
                                $route['redemptions']['miles'] = $requestParams['MILES_' . $minCabinRDBs] ?? null;
                            } else {
                                $this->sendNotification('check classOfService');
                            }
                        } else {
                            $minCabinRDBs = array_shift($cabinRDBs);
                            $route['classOfService'] = $this->getService($minCabinRDBs);
                            $route['redemptions']['miles'] = $requestParams['MILES_' . $minCabinRDBs] ?? null;
                        }

                        if (!in_array($route['flightIdString'], $milesInfoList)) {
                            $milesInfoList[] = $route['flightIdString'];
                        }
                        $route['num_stops'] = $segmentOfStops;
                        $this->logger->debug('Parsed data:');
                        $this->logger->debug(var_export($route, true), ['pre' => true]);
                        $routes[] = $route;
                    }
                }
            }
        }

        $result = [];

        $check = false;

        foreach ($routes as $route) {
            $route['num_stops'] += count($route['connections']) - 1;
            unset($route['flightIdString']);
            $result[] = $route;
        }

        if (empty($result)) {
            if ($check) {
                $this->SetWarning('Your selected itinerary is invalid. Please reselect.');
            } elseif (isset($warningMsg)) {
                $this->SetWarning('Flights is fully booked.');
            } elseif (isset($this->warning)) {
                $this->SetWarning($this->warning);
            }
        }

        return $result;
    }

    private function validRouteV2($fields)
    {
        $this->logger->notice(__METHOD__);
        $headers = [
            'Accept'  => '*/*',
            'Origin'  => 'https://www.cathaypacific.com',
            'Referer' => 'https://www.cathaypacific.com/',
        ];
        // check origin
        $dataFrom = \Cache::getInstance()->get('ra_asia_origins_v2');

        if (!is_array($dataFrom)) {
            $this->http->GetURL("https://api.cathaypacific.com/common/api/v2/airports/en/origin-list/redeem?type=standard",
                $headers, 30);

            if ($this->isBadProxy()
                || strpos($this->http->Error, 'Network error 28 - Operation timed out after') !== false
                || strpos($this->http->Error, 'empty body') !== false
                || $this->http->Response['code'] != 200
            ) {
                throw new \CheckRetryNeededException(5, 0);
            }
            $dataFrom = $this->http->JsonLog(null, 0, true);

            if (!empty($dataFrom)) {
                \Cache::getInstance()->set('ra_asia_origins_v2', $dataFrom, 60 * 60 * 24);
            }
        }

        foreach ($dataFrom['airports'] as $destination) {
            if ($destination['airportCode'] === $fields['DepCode']) {
                $inOrigins = true;

                break;
            }
        }

        if (isset($dataFrom['airports']) && !isset($inOrigins)) {
            $this->SetWarning('No flights from ' . $fields['DepCode']);

            return false;
        }

        $dataTo = \Cache::getInstance()->get('ra_asia_destination_v2_' . $fields['DepCode']);

        if (!is_array($dataTo)) {
            // check destination
            $this->http->GetURL("https://api.cathaypacific.com/common/api/v2/airports/en/destination-list/redeem?origin={$fields['DepCode']}&type=standard",
                $headers, 30);

            if ($this->isBadProxy()
                || strpos($this->http->Error, 'Network error 28 - Operation timed out after') !== false
                || strpos($this->http->Error, 'empty body') !== false
                || $this->http->Response['code'] != 200
            ) {
                throw new \CheckRetryNeededException(5, 0);
            }
            $dataTo = $this->http->JsonLog(null, 0, true);

            if (!empty($dataTo)) {
                \Cache::getInstance()->set('ra_asia_destination_v2_' . $fields['DepCode'], $dataTo, 60 * 60 * 24);
            }
        }

        foreach ($dataTo['airports'] as $destination) {
            if ($destination['airportCode'] === $fields['ArrCode']) {
                $inDestinations = true;

                break;
            }
        }

        if (isset($dataTo['airports']) && !isset($inDestinations)) {
            $this->SetWarning('No flights from ' . $fields['DepCode'] . ' to ' . $fields['ArrCode']);

            return false;
        }

        // check airlines
        $data = \Cache::getInstance()->get('ra_asia_airlines_v2_' . $fields['DepCode'] . '_' . $fields['ArrCode']);

        if (!is_array($data) || !isset($data['airlines'])) {
            $url = "https://api.cathaypacific.com/common/api/v2/airline/en/airline-by-od?destination={$fields['ArrCode']}&origin={$fields['DepCode']}&type=standard";
            $this->http->GetURL($url, $headers, 30);

            if (in_array($this->http->Response['code'], [504, 400, 500, 503, 408])) {
                sleep(2);
                $this->http->GetURL($url, $headers, 30);
            }

            if ($this->isBadProxy()
                || strpos($this->http->Error, 'Network error 28 - Operation timed out after') !== false
                || strpos($this->http->Error, 'empty body') !== false
            ) {
                throw new \CheckRetryNeededException(5, 0);
            }
            $data = $this->http->JsonLog(null, 0, true);

            if (!empty($data)) {
                \Cache::getInstance()
                    ->set('ra_asia_airlines_v2_' . $fields['DepCode'] . '_' . $fields['ArrCode'], $data, 60 * 60 * 24);
            }
        }

        if (!isset($data['airlines'])) {
            if (isset($data['errors']) && isset($this->http->Response['body'])
                && strpos($this->http->Response['body'], 'No carrier found') !== false) {
                $this->SetWarning('No carrier found on route from ' . $fields['DepCode'] . ' to ' . $fields['ArrCode']);

                return false;
            }

            if (isset($this->http->Response['code'])
                && !in_array($this->http->Response['code'], [504, 400, 500, 503])
                && strpos($this->http->Error, 'Network error ') === false
            ) {
                $this->sendNotification('check valid airlines // ZM');
            }

            return true;
        }

        if (isset($data['airlines']) && !empty($data['airlines'])) {
            $airlines = [];

//            $this->http->JsonLog(json_encode($data['airlines']), 1, true);

            foreach ($data['airlines'] as $airline) {
                $url = "https://api.cathaypacific.com/afr/searchpanel/searchoptions/en.{$fields['DepCode']}.{$fields['ArrCode']}.ow.std.{$airline['airlineDesignator']}.json";
                $this->http->GetURL($url, $headers, 30);

                if ($this->http->Response['code'] == 504) {
                    $this->http->GetURL($url, $headers, 30);
                }

                if ($this->isBadProxy()
                    || strpos($this->http->Error, 'Network error 28 - Operation timed out after') !== false
                    || strpos($this->http->Error, 'Network error 56') !== false
                    || strpos($this->http->Error, 'empty body') !== false
                ) {
                    throw new \CheckRetryNeededException(5, 0);
                }

                $dataRoute = $this->http->JsonLog(null, 0, true);

                if (!isset($dataRoute['searchStartDate'])) {
                    if (isset($data['errors']) && strpos($this->http->Response['body'], 'Get lvooFindFlightAward occurs Exception') !== false) {
                        $warning = 'No flights on search criteria';
                    }

                    continue;
                }

                $searchStartDate = strtotime(preg_replace("/^(\d{4})(\d{2})(\d{2})$/", "$1-$2-$3",
                    $dataRoute['searchStartDate']));
                $searchEndDate = strtotime(preg_replace("/^(\d{4})(\d{2})(\d{2})$/", "$1-$2-$3",
                    $dataRoute['searchEndDate']));

                if ($fields['DepDate'] < $searchStartDate || $fields['DepDate'] > $searchEndDate) {
                    $this->warning = 'No flights on search criteria';
                }

                switch ($fields['Cabin']) {
                    case 'economy':
                        $cabin = 'eco';

                        break;

                    case 'premiumEconomy':
                        $cabin = 'pey';

                        break;

                    case 'business':
                        $cabin = 'bus';

                        break;

                    case 'firstClass':
                        $cabin = 'fir';

                        break;
                }

                if (isset($dataRoute['milesRequired'][$cabin])) {
                    $airlines[] = $airline['airlineDesignator'];
                }
            }

            if (empty($airlines)) {
                if (isset($warning)) {
                    $this->SetWarning('We are unable to proceed with this booking as it includes more than 6 flights or more than 4 different cities.');

                    return false;
                }
                $this->SetWarning("No flights. Try another criteria search");

                return false;
            }
        }

        return true;
    }

    private function isBadProxy(): bool
    {
        return
            $this->http->Response['code'] == 403
            || strpos($this->http->Error, 'Network error 0 -') !== false
            || strpos($this->http->Error, 'Network error 52 - Empty reply from server') !== false
            || strpos($this->http->Error, 'Network error 56 - OpenSSL SSL_read: error') !== false
            || strpos($this->http->Error, 'Network error 28 - Connection timed out after') !== false
            || strpos($this->http->Error, 'Network error 56 - Proxy CONNECT aborted') !== false
            || strpos($this->http->Error, 'Network error 56 - Received HTTP code 400 from proxy after CONNECT') !== false
            || strpos($this->http->Error, 'Network error 56 - Received HTTP code 403 from proxy after CONNECT') !== false
            || strpos($this->http->Error, 'Network error 56 - Received HTTP code 490 from proxy after CONNECT') !== false
            || strpos($this->http->Error, 'Network error 56 - Received HTTP code 503 from proxy after CONNECT') !== false
            || strpos($this->http->Error, 'Network error 56 - Received HTTP code 502 from proxy after CONNECT') !== false
            || strpos($this->http->Error, 'Network error 7 - Failed to connect to') !== false
            || strpos($this->http->Error, 'Network error 56 - Recv failure: Connection reset by peer') !== false;
    }

    private function seleniumSearch($selenium, $fields, $asiaCabinCode, $depDate)
    {
        $this->logger->notice(__METHOD__);

        $this->logger->warning('[current url]: ' . $selenium->http->currentUrl());

        $header = [
            'Accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Referer'                   => 'https://www.cathaypacific.com/',
            'DNT'                       => 1,
            'Sec-Fetch-Dest'            => 'document',
            'Sec-Fetch-Mode'            => 'navigate',
            'Sec-Fetch-Site'            => 'same-site',
            'Sec-Fetch-User'            => '?1',
            'TE'                        => 'trailers',
            'Upgrade-Insecure-Requests' => 1,
        ];

        $data = [
            'ACTION'           => 'RED_AWARD_SEARCH',
            'ENTRYPOINT'       => 'https://www.cathaypacific.com/cx/en_GB/book-a-trip/redeem-flights/redeem-flight-awards.html',
            'ENTRYLANGUAGE'    => 'en',
            'ENTRYCOUNTRY'     => 'GB',
            'RETURNURL'        => 'https://www.cathaypacific.com/cx/en_GB/book-a-trip/redeem-flights/redeem-flight-awards.html?recent_search=ow',
            'ERRORURL'         => 'https://www.cathaypacific.com/cx/en_GB/book-a-trip/redeem-flights/redeem-flight-awards.html?recent_search=ow',
            'CABINCLASS'       => $asiaCabinCode,
            'BRAND'            => 'CX',
            'ADULT'            => $fields['Adults'],
            'CHILD'            => 0,
            'FLEXIBLEDATE'     => 'true',
            'ORIGIN[1]'        => $fields['DepCode'],
            'DESTINATION[1]'   => $fields['ArrCode'],
            'DEPARTUREDATE[1]' => $depDate,
            'LOGINURL'         =>
                'https://www.cathaypacific.com/cx/en_GB/sign-in/campaigns/miles-flight.html',
        ];

        $data = http_build_query($data);
        $selenium->http->GetURL("https://api.cathaypacific.com/redibe/IBEFacade?" . $data);

        try {
            $this->processingResponse($selenium);
        } catch (\UnexpectedJavascriptException $e) {
            $this->logger->debug('this->http->response[body] is null, try my-account.html');

            if ($this->ErrorCode === ACCOUNT_WARNING) {
                $this->hot_enabled = false; // reset saving session

                return;
            }

            $selenium->http->GetURL('https://www.cathaypacific.com/cx/en_US/membership/my-account.html');

            if (!$selenium->waitForElement(\WebDriverBy::xpath("//span[contains(@class,'welcomeLabel')][starts-with(normalize-space(),'Welcome,')]"), 10)) {
                $this->savePageToLogs($selenium);

                if ($this->http->FindSingleNode("//span[contains(.,'There are no flights available for the dates you have selected')]")) {
                    $this->SetWarning('There are no flights available for the dates you have selected');

                    return;
                }

                if ($this->http->FindSingleNode("//h1[contains(.,'There are no available flights for that date')]")) {
                    $this->SetWarning('There are no available flights for that date');

                    return;
                }
                // check ones again - debug (ones not load)
                if (!$selenium->waitForElement(\WebDriverBy::xpath("//span[contains(@class,'welcomeLabel')][starts-with(normalize-space(),'Welcome,')]"), 0)) {
                    $this->sendNotification("check session // ZM");
                    $this->logger->debug('session expired or blocked');

                    throw new \CheckRetryNeededException(5, 0);
                }
            }
            $this->logger->debug('session alive');

            $selenium->http->GetURL("https://api.cathaypacific.com/redibe/IBEFacade?" . $data);

            try {
                $this->processingResponse($selenium);
            } catch (\UnexpectedJavascriptException $e) {
                $this->logger->debug('session expired or blocked');

                throw new \CheckRetryNeededException(5, 0);
            }
        }
    }

    private function processingResponse($selenium)
    {
        $this->logger->notice(__METHOD__);
        //loading page 30 sec
        $wait = 0;

        if ($selenium->waitForElement(WebDriverBy::xpath('//iframe[@data-duration="30"]'), 10)) {
            $wait = 35;
        }

        $this->savePageToLogs($selenium);

        $XPATH_BAD_CONNECT = "
            //h1[
                contains(text(), 'Internal Server Error - Read')
                or contains(text(), 'Service Unavailable - Zero size object')
            ]
            | //bwc-error-page-subtitle[contains(text(), 'The server was acting as a gateway or proxy and received an invalid response from the upstream server.')]
            | //*[contains(text(), 'The server was acting as a gateway or proxy and received an invalid response from the upstream server.')]
            |//h1[contains(text(),'Pardon our interruption...')]
            |//span[contains(text(), 'This site can’t be reached')]
            |//h1[contains(text(), 'Access Denied')]
            ";
        $XPATH_LOADED = '
        //text()[contains(.,"There are no redemption seats available for this flight")]/ancestor::div[1]
        |//span[contains(.,"There are no flights available for the dates you have selected.")]
        |//span[contains(.,"Your selected itinerary is not eligible for redemption.")]
        |//div[contains(.,"is not available for your chosen itinerary")]
        |//h1[contains(.,"There are no available flights for that date")]
        |//h2[contains(text(),"Depart")]
        ';

        $res = $selenium->waitForElement(WebDriverBy::xpath($XPATH_BAD_CONNECT . "|" . $XPATH_LOADED), 10 + $wait);

        if ($selenium->waitForElement(WebDriverBy::xpath($XPATH_BAD_CONNECT), 0)) {
            throw new \CheckRetryNeededException(5, 0);
        }

        if (!isset($res)) {
            try {
                $this->savePageToLogs($selenium);
            } catch (\UnexpectedJavascriptException | \ErrorException $e) {
                $this->logger->error("Exception: " . $e->getMessage(), ['HtmlEncode' => true]);
                $this->logger->error($e->getTraceAsString(), ['HtmlEncode' => true]);

                throw new \CheckException('something went wrong', ACCOUNT_ENGINE_ERROR);
            }

            if ($msg = $this->http->FindSingleNode("//p[contains(text(),'There are no flights available for the date and/or award')]")) {
                $this->SetWarning($msg);

                return;
            }

            if ($this->http->FindPreg("#/_sec/cp_challenge/#")) {
                $this->logger->error("still being processed: cp_challenge");

                throw new \CheckRetryNeededException(5, 0);
            }

            throw new \CheckException('unknown page format (not loaded)', ACCOUNT_ENGINE_ERROR);
        }

        // for debug
        $text = $res->getText();
        $this->logger->error($text);

        if (strpos($text, 'Depart') === false) {
            $this->SetWarning('No redemption flights available for this date and route');
        }
        /*
                $this->savePageToLogs($selenium);

                $wait = 0;

                if ($selenium->waitForElement(WebDriverBy::xpath('//iframe[@data-duration="30"]'), 5)) {
                    $wait = 30;
                }

                        if ($res = $selenium->waitForElement(WebDriverBy::xpath('
                        //text()[contains(.,"There are no redemption seats available for this flight")]/ancestor::div[1]
                        |//span[contains(.,"There are no flights available for the dates you have selected.")]
                        |//span[contains(.,"Your selected itinerary is not eligible for redemption.")]
                        |//div[contains(.,"is not available for your chosen itinerary")]
                        |//h1[contains(.,"There are no available flights for that date")]
                        |//h1[contains(text(),"Pardon our interruption...")]
                        |//span[contains(text(), "This site can’t be reached")]
                        |//h2[contains(text(),"Depart")]
                        '), 5 + $wait)) {
                            if (!isset($res)) {
                                try {
                                    $this->savePageToLogs($selenium);
                                } catch (\UnexpectedJavascriptException $e) {
                                    throw new \CheckException($e->getMessage(), ACCOUNT_ENGINE_ERROR);
                                }

                                throw new \CheckException('unknown page format (not loaded)', ACCOUNT_ENGINE_ERROR);
                            }

                            if ((strpos($res->getText(), 'Pardon our interruption...') !== false)) {
                                $this->logger->debug('Bot detection');

                                throw new \CheckRetryNeededException(5, 0);
                            }

                            if ((strpos($res->getText(), 'This site can’t be reached') !== false)) {
                                $this->logger->debug('Connection error');

                                throw new \CheckRetryNeededException(5, 0);
                            }

                            if (!(strpos($res->getText(), 'Depart') !== false)) {
                                $this->SetWarning('No redemption flights available for this date and route');
                            }
                        }
                */
        $this->savePageToLogs($selenium);
    }

    private function savePageToLogs($selenium)
    {
        if (isset($selenium->http) && isset($selenium->driver)) {
            $this->logger->notice(__METHOD__);
            // save page to logs
            try {
                $selenium->http->SaveResponse();
            } catch (\ErrorException $e) {
                $this->logger->error("Exception: " . $e->getMessage());
            }
            // save page to logs
            $this->http->SetBody($selenium->driver->executeScript('return document.documentElement.innerHTML'));
            $this->http->SaveResponse();
        }
    }

    private function someSleep()
    {
        usleep(random_int(70, 250) * 10000);
    }
}
