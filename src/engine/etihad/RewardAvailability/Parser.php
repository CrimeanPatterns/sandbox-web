<?php

namespace AwardWallet\Engine\etihad\RewardAvailability;

use AwardWallet\Common\Selenium\FingerprintFactory;
use AwardWallet\Common\Selenium\FingerprintRequest;
use AwardWallet\Engine\ProxyList;
use Facebook\WebDriver\Exception\Internal\UnexpectedResponseException;

class Parser extends \TAccountChecker
{
    use \SeleniumCheckerHelper;
    use \PriceTools;
    use ProxyList;

    public $isRewardAvailability = true;
    private $debugMode = false;
    private $inCabin;
    private $brandDir;
    private $brands;
    private $isFound = false;
    private $responseData;

    public static function getRASearchLinks(): array
    {
        return ['https://www.etihad.com/en-us/'=>'search page'];
    }

    public function InitBrowser()
    {
        \TAccountChecker::InitBrowser();
        $this->UseSelenium();
//        $this->useGoogleChrome(\SeleniumFinderRequest::CHROME_95);
        $this->useChromePuppeteer(\SeleniumFinderRequest::CHROME_PUPPETEER_103);

        $this->http->saveScreenshots = true;
//        $this->seleniumRequest->setHotPool(self::class); // old
        $this->seleniumRequest->setHotSessionPool(self::class . 'pup', $this->AccountFields['ProviderCode']);

        switch (random_int(0, 1)) {
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
        }

        $request = FingerprintRequest::chrome();
        $request->browserVersionMin = 100;
        $fingerprint = $this->services->get(FingerprintFactory::class)->getOne([$request]);

        $this->seleniumOptions->fingerprint = $fingerprint->getFingerprint();
        $this->http->setUserAgent($fingerprint->getUseragent());
        $this->seleniumOptions->userAgent = $fingerprint->getUseragent();
    }

    public function LoadLoginForm()
    {
        return true;
    }

    public function IsLoggedIn()
    {
        return false;
    }

    public function Login()
    {
        return true;
    }

    public function getRewardAvailabilitySettings()
    {
        return [
            'supportedCurrencies'      => ['USD'],
            'supportedDateFlexibility' => 0, // 1
            'defaultCurrency'          => 'USD',
        ];
    }

    public function ParseRewardAvailability(array $fields)
    {
        $this->logger->info("Parse Reward Availability", ['Header' => 2]);
        $this->logger->debug("params: " . var_export($fields, true));

        $this->inCabin = $fields['Cabin'];
        $fields['Cabin'] = $this->getCabinFields(false)[$fields['Cabin']];
        $supportedCurrencies = $this->getRewardAvailabilitySettings()['supportedCurrencies'];

        if (!in_array($fields['Currencies'][0], $supportedCurrencies)) {
            $fields['Currencies'][0] = $this->getRewardAvailabilitySettings()['defaultCurrency'];
            $this->logger->notice("parse with defaultCurrency: " . $fields['Currencies'][0]);
        }

        $url = $this->createHttpQuery($fields);

        try {
            $response = $this->getData($url);
        } catch (\WebDriverCurlException | \WebDriverException $e) {
            $this->logger->error($e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        } catch (\ErrorException $e) {
            if (strpos($e->getMessage(), 'Array to string conversion') !== false
                || strpos($e->getMessage(), 'strlen() expects parameter 1 to be string, array given') !== false
            ) {
                // TODO бага селениума
                throw new \CheckRetryNeededException(5, 0);
            }

            throw $e;
        }

        if (is_null($response)) {
            if ($this->ErrorCode != ACCOUNT_WARNING) {
                throw new \CheckRetryNeededException(5, 0);
            }
            $this->keepSession(true);

            return ['routes' => []];
        }

        return ['routes' => $this->parseData($response)];
    }

    protected function incapsula($incapsula, $src)
    {
        $this->logger->notice(__METHOD__);
        $referer = $this->http->currentUrl();

        $this->driver->switchTo()->frame($incapsula);

        if (isset($incapsula)) {
            sleep(2);
            $this->logger->debug("parse captcha form");
            $this->saveResponse();

            $action = $this->http->FindPreg("/xhr2.open\(\"POST\", \"([^\"]+)/");
            $dataUrl = $this->http->FindPreg('#"(/_Incapsula_Resource\?SWCNGEEC=.+?)"#');
            $this->driver->switchTo()->defaultContent();
            $this->saveResponse();

            if (!$dataUrl || !$action) {
                return false;
            }
            $this->http->NormalizeURL($dataUrl);
            $this->http->GetURL($dataUrl);
            $json = $this->waitForElement(\WebDriverBy::xpath('//pre[not(@id)]'), 20);

            if (!$json) {
                throw new \CheckRetryNeededException(5, 0);
            }
            $json = str_replace(['<pre>', '</pre>'], '', $json->getText());
            $data = $this->http->JsonLog($json);

            if (!isset($data->gt, $data->challenge)) {
                return false;
            }

            $postData = [
                "type"       => "GeeTestTaskProxyless",
                "websiteURL" => $referer,
                "gt"         => $data->gt,
                "challenge"  => $data->challenge,
            ];
            $recognizer = $this->getCaptchaRecognizer(self::CAPTCHA_RECOGNIZER_ANTIGATE_API_V2);
            $recognizer->RecognizeTimeout = 120;

            $request = $this->recognizeAntiCaptcha($recognizer, $postData);

            if (is_string($request)) {
                $request = $this->http->JsonLog($request, 1);
            } elseif (is_bool($request) && $request === false) {
                throw new \CheckException('bad captcha', ACCOUNT_ENGINE_ERROR);
            }

            $this->driver->executeScript("
                fetch(\"{$action}\", {
                  \"headers\": {
                    \"accept\": \"*/*\",
                    \"accept-language\": \"en-US,en;q=0.9\",
                    \"content-type\": \"application/x-www-form-urlencoded\",
                  },
                  \"referrer\": \"https://digital.etihad.com{$src}\",
                  \"referrerPolicy\": \"strict-origin-when-cross-origin\",
                  \"body\": \"geetest_challenge={$request->challenge}&geetest_validate={$request->validate}&geetest_seccode={$request->seccode}\",
                  \"method\": \"POST\",
                  \"mode\": \"cors\",
                  \"credentials\": \"include\"
                }).then( result => {
                    let script = document.createElement(\"script\");
                    let id = \"challenge\";
                    script.id = id;
                    document.querySelector(\"body\").append(script);
                });
            ");

            $this->waitForElement(\WebDriverBy::xpath('//script[@id="challenge"]'), 10, false);

            $this->http->GetURL($referer);
        }

        return true;
    }

    private function getCabinFields(bool $onlyKeys = true): array
    {
        $cabins = [
            'economy'        => ['class' => 'Economy', 'execution' => 'e2s1', 'query' => 'E'],
            'premiumEconomy' => ['class' => 'Economy', 'execution' => 'e3s1', 'query' => 'E'], // has no
            'firstClass'     => ['class' => 'First', 'execution' => 'e2s1', 'query' => 'F'],
            // it has the residence ['class' => 'First', 'execution' => 'e1s1']
            'business' => ['class' => 'Business', 'execution' => 'e3s1', 'query' => 'B'],
        ];

        if ($onlyKeys) {
            return array_keys($cabins);
        }

        return $cabins;
    }

    private function createHttpQuery(array $fields): string
    {
        $params = [
            'LANGUAGE'               => 'EN',
            'CHANNEL'                => 'DESKTOP',
            'B_LOCATION'             => $fields['DepCode'],
            'E_LOCATION'             => $fields['ArrCode'],
            'TRIP_TYPE'              => 'O',
            'CABIN'                  => $fields['Cabin']['query'],
            'TRIP_FLOW_TYPE'         => 'AVAILABILITY',
            'DATE_1'                 => date("Ymd", $fields['DepDate']) . '0000',
            'WDS_ENABLE_MILES_TOGGLE'=> 'TRUE',
            'FLOW'                   => 'AWARD',
        ];

        $travelers = '';

        for ($i = 0; $i < $fields['Adults']; $i++) {
            $travelers .= 'ADT,';
        }
        $params['TRAVELERS'] = substr($travelers, 0, -1);

        $query = http_build_query($params);

        return "https://digital.etihad.com/book/search?{$query}";
    }

    private function getData(string $url): ?array
    {
        try {
            if (strpos($this->http->currentUrl(), 'etihad.com') === false) {
                $this->http->GetURL('https://www.etihad.com/en-us/');
            }
            $this->http->GetURL($url);
            $badProxy = $this->waitForElement(\WebDriverBy::xpath("
                //h1[contains(., 'This site can’t be reached')]
                | //span[contains(text(), 'This page isn’t working')]
                | //p[contains(text(), 'There is something wrong with the proxy server, or the address is incorrect.')]
            "), 3);
            $this->http->SaveResponse();
            $this->runScriptFetch();

            if ($badProxy) {
                throw new \CheckRetryNeededException(5, 0);
            }

            if ($this->waitForElement(\WebDriverBy::xpath("//h1[contains(., '502 Bad Gateway')]"), 1)) {
                sleep(1);
                $this->http->GetURL($url);

                $badProxy = $this->waitForElement(\WebDriverBy::xpath("
                    //h1[contains(., 'This site can’t be reached')]
                    | //span[contains(text(), 'This page isn’t working')]
                    | //p[contains(text(), 'There is something wrong with the proxy server, or the address is incorrect.')]
                "), 1);
                $this->runScriptFetch();

                if ($badProxy) {
                    throw new \CheckRetryNeededException(5, 0);
                }
            }
        } catch (\ScriptTimeoutException | \TimeOutException $e) {
            $this->logger->error("ScriptTimeoutException: " . $e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        }

        if ($this->waitForElement(\WebDriverBy::id('onetrust-accept-btn-handler'), 5)) {
            $this->driver->executeScript('document.querySelector("#onetrust-accept-btn-handler").click()');
        }

        $this->waitFor(function () {
            return $this->waitForElement(\WebDriverBy::xpath("
                    //text()[contains(.,\"As you were browsing something about your browser made us think you were a bot\")]/ancestor::*[1]
                    | //h1[contains(.,\"Service Unavailable\")]
                    | //h1[contains(.,'Pardon Our Interruption') or contains(., 'This site can’t be reached')]
                    | //h1[contains(.,'Choose your flight')]
                    | //span[contains(.,'No flight available')]
                    | //ey-bounds-new//div[contains(text(),'Outbound flight')]
                "), 0);
        }, 10);

        $iframe = $this->waitForElement(\WebDriverBy::xpath("//iframe[contains(@src, '/_Incapsula_Resource?')]"), 10, true);

        if ($iframe) {
            $this->incapsula($iframe, $iframe->getAttribute('src'));
        }

        $this->runScriptFetch();

        $iframe = $this->waitForElement(\WebDriverBy::xpath("//iframe[contains(@src, '/_Incapsula_Resource?')]"), 10, true);

        if ($iframe) {
            throw new \CheckRetryNeededException(5, 0);
            /*            $this->incapsula($iframe, $iframe->getAttribute('src'));
                        $this->runScriptFetch();
                        $iframe = $this->waitForElement(\WebDriverBy::xpath("//iframe[contains(@src, '/_Incapsula_Resource?')]"), 10, true);

                        if ($iframe) {
                            $this->sendNotification('captcha2 - not helped // ZM');

                            throw new \CheckRetryNeededException(5, 0);
                        }
                        $this->sendNotification('captcha2 - helped // ZM');*/
        }

        if ($this->waitForElement(\WebDriverBy::xpath('(//div[@class="miles-checkbox-panel-information"]//mat-checkbox//input[@aria-checked="true"])[1]'),
            5, false)
        ) {
            $this->driver->executeScript('document.querySelector("div.miles-checkbox-panel-information mat-checkbox label").click()');
            sleep(2);
            $this->http->SaveResponse();
        }

        if ($this->waitForElement(\WebDriverBy::xpath('(//div[@class="miles-checkbox-panel-information"]//mat-checkbox//input[@aria-checked="false"])[1]'), 5, false)
        ) {
            $this->driver->executeScript('document.querySelector("div.miles-checkbox-panel-information mat-checkbox label").click()');
        } else {
            $noClick = true;
        }
        $xpathLoaded = "
                //div[@id='upsell-data']//span[contains(.,'We found')]
                | // div[@id='upsell-data']//div[normalize-space()='Outbound flight']
                ";

        $this->waitForElement(\WebDriverBy::xpath($xpathLoaded), 10);

        try {
            $this->http->SaveResponse();
        } catch (\ErrorException $e) {
            $curUrl = $this->http->currentUrl();

            if (!is_string($curUrl)) {
                $this->logger->debug(var_export($curUrl, true));

                if (is_array($curUrl) && isset($curUrl['error'])
                    && ($curUrl['error'] === 'invalid session id' || $curUrl['error'] === 'unknown command')
                ) {
                    if (strpos($e->getMessage(), 'Array to string conversion') === false) {
                        $this->sendNotification("not invalid session id or unknown command // ZM");
                    }

                    throw new \CheckRetryNeededException(5, 0);
                }
                $this->sendNotification("http->currentUrl() == array // ZM");
            } else {
                throw $e;
            }
        }

        if (isset($noClick) && $this->waitForElement(\WebDriverBy::id('mat-slide-toggle-1'), 0)) {
            if ($this->waitForElement(\WebDriverBy::xpath('//*[@id="mat-slide-toggle-1" and contains(@class,"checked")]'),
                5)
            ) {
                $this->driver->executeScript('document.querySelector("#mat-slide-toggle-1-input").click()');
                sleep(2);
                $this->http->SaveResponse();
            }
            $this->waitForElement(\WebDriverBy::xpath($xpathLoaded), 10);
            $this->http->SaveResponse();
        }

        $noFlights = $this->waitForElement(\WebDriverBy::xpath("//span[contains(.,'No flight available')]/../span[contains(@class,'description')]"), 5);

        if ($noFlights) {
            $this->SetWarning($noFlights->getText());

            return null;
        }
        $response = $this->http->JsonLog(
            $this->driver->executeScript('return localStorage.getItem("air-bounds")'),
            1,
            true
        );

        if (is_null($response)) {
            $response = $this->getDataV2();
        }

        return $response;
    }

    private function getDataV2(): ?array
    {
        $this->logger->notice(__METHOD__);
        $checkedMiles = $this->waitForElement(\WebDriverBy::xpath("
                //ey-miles-toggle-new-cont[contains(., 'Pay with miles')]//mat-checkbox[@id='mat-checkbox-1']
            "), 0);

        if (!$checkedMiles) {
            return null;
        }
        $checkedMiles->click();

        $this->waitFor(function () {
            return $this->waitForElement(\WebDriverBy::xpath("
                    //text()[contains(.,\"As you were browsing something about your browser made us think you were a bot\")]/ancestor::*[1]
                    | //h1[contains(.,\"Service Unavailable\")]
                    | //h1[contains(.,'Pardon Our Interruption') or contains(., 'This site can’t be reached')]
                    | //h1[contains(.,'Choose your flight')]
                    | //span[contains(.,'No flight available')]
                    | //*[contains(normalize-space(text()),'Unable to proceed , please try again')]
                    | //ey-bounds-new//div[contains(text(),'Outbound flight')]
                "), 0);
        }, 10);
        $this->http->SetBody($this->driver->executeScript('return document.documentElement.innerHTML'));
        $this->http->SaveResponse();

        if ($msg = $this->http->FindSingleNode("//*[contains(normalize-space(text()),'Unable to proceed , please try again')]")) {
            $this->sendNotification('unable proceed // ZM');

            if ($this->attempt === 0) {
                throw new \CheckRetryNeededException(5, 0);
            }

            throw new \CheckException($msg, ACCOUNT_PROVIDER_ERROR);
        }

        $this->runScriptFetch();
        $checkedMiles = $this->waitForElement(\WebDriverBy::xpath("
                //ey-miles-toggle-new-cont[contains(., 'Pay with miles')]//mat-checkbox[@id='mat-checkbox-1']
            "), 0);

        if (!$checkedMiles) {
            return null;
        }
        $checkedMiles->click();
        $checkedMilesChecked = $this->waitForElement(\WebDriverBy::xpath("
             //ey-miles-toggle-new-cont[contains(., 'Pay with miles')]//mat-checkbox[@id='mat-checkbox-1' and contains(@class,'mat-checkbox-checked')]
        "), 5);

        if (!$checkedMilesChecked) {
            $checkedMiles = $this->waitForElement(\WebDriverBy::xpath("
                //ey-miles-toggle-new-cont[contains(., 'Pay with miles')]//mat-checkbox[@id='mat-checkbox-1']
            "), 0);

            if ($checkedMiles) {
                $checkedMiles->click();
            }
        }

        $this->waitFor(function () {
            return $this->waitForElement(\WebDriverBy::xpath("
                    //text()[contains(.,\"As you were browsing something about your browser made us think you were a bot\")]/ancestor::*[1]
                    | //h1[contains(.,\"Service Unavailable\")]
                    | //h1[contains(.,'Pardon Our Interruption') or contains(., 'This site can’t be reached')]
                    | //h1[contains(.,'Choose your flight')]
                    | //span[contains(.,'No flight available')]
                    | //ey-bounds-new//div[contains(text(),'Outbound flight')]
                "), 0);
        }, 10);

        $this->http->SaveResponse();

        $noFlights = $this->waitForElement(\WebDriverBy::xpath("//span[contains(.,'No flight available')]/../span[contains(@class,'description')]"), 0);

        if ($noFlights && $checkedMilesChecked) {
            $this->SetWarning($noFlights->getText());

            return null;
        }

        return $this->http->JsonLog(
            $this->driver->executeScript('return localStorage.getItem("air-bounds")'),
            1,
            true
        );
    }

    private function runScriptFetch(): void
    {
        $this->logger->debug(__METHOD__);
        $this->driver->executeScript(/** @lang JavaScript */ "
            localStorage.setItem('air-bounds', '');
            var constantMock = window.fetch;
            window.fetch = function() {
                return new Promise((resolve, reject) => {
                     constantMock.apply(this, arguments)
                         .then( (response) => { 
                                if (response.url.indexOf('air-bounds') > -1) {
                                    response.clone().json().then(
                                        (body) => {
                                            //console.log(JSON.stringify(body));
                                            localStorage.setItem('air-bounds', JSON.stringify(body));
                                        }
                                    );
                                }
                                resolve(response);
                         }).catch((error) => {
                            reject(error);
                         })
                });
            }
        ");
    }

    private function parseData(array $response): array
    {
        $this->logger->notice(__METHOD__);

        if (isset($response['errors']) && !isset($response['data'])) {
            $errors = $response['errors'][0];

            if ($errors['title'] == "NO FARES") {
                $this->SetWarning($errors['title']);

                return [];
            }

            $this->logger->error($errors['title']);

            throw new \CheckRetryNeededException(5, 0);
        }

        $airBoundGroups = $response['data']['airBoundGroups'];
        $flight = $response['dictionaries']['flight'];
        $airline = $response['dictionaries']['airline'];
        $fareFamilyWithServices = $response['dictionaries']['fareFamilyWithServices'];
        $currency = $response['dictionaries']['currency'];

        try {
            $dynamicContent = $this->getDynamicContent();
        } catch (\WebDriverCurlException | UnexpectedResponseException $e) {
            $this->logger->error($e->getMessage());
            $this->logger->error($e->getTraceAsString());

            throw new \CheckRetryNeededException(5, 0);
        }

        $tmpResults = [];

        foreach ($airBoundGroups as $boundGroup) {
            $tmp = [
                'distance'    => null,
                'num_stops'   => count($boundGroup['boundDetails']['segments']) - 1,
            ];

            foreach ($boundGroup['boundDetails']['segments'] as $segment) {
                $segmentInfo = $flight[$segment['flightId']];
                $depDateTime = str_replace('T', ' ', substr($segmentInfo['departure']['dateTime'], 0, 16));
                $arrDateTime = str_replace('T', ' ', substr($segmentInfo['arrival']['dateTime'], 0, 16));

                $tmp['connections'][] = [
                    'departure' => [
                        'date'     => $depDateTime,
                        'dateTime' => strtotime($depDateTime),
                        'airport'  => $segmentInfo['departure']['locationCode'],
                        'terminal' => $segmentInfo['departure']['terminal'] ?? null,
                    ],
                    'arrival' => [
                        'date'     => $arrDateTime,
                        'dateTime' => strtotime($arrDateTime),
                        'airport'  => $segmentInfo['arrival']['locationCode'],
                        'terminal' => $segmentInfo['arrival']['terminal'] ?? null,
                    ],
                    'meal'        => null,
                    'flight'      => [$segmentInfo['marketingAirlineCode'] . $segmentInfo['marketingFlightNumber']],
                    'airline'     => $segmentInfo['marketingAirlineCode'],
                    'operator'    => (isset($segmentInfo['operatingAirlineCode']))
                        ? $airline[$segmentInfo['operatingAirlineCode']]
                        : 'Another airline',
                    'aircraft'    => $segmentInfo['aircraftCode'],
                    'flightId'    => $segment['flightId'],
                ];
            }

            foreach ($boundGroup['airBounds'] as $numAirBound => $airBound) {
                if (isset($airBound['status']) && $airBound['status']['value'] === 'soldOut') {
                    $this->logger->info("skip airBound $numAirBound: soldOut");

                    continue;
                }
                $prices = $airBound['prices'];

                if (!isset($prices['milesConversion']['convertedMiles'])) {
                    throw new \CheckRetryNeededException(5, 0);
                }

                if ($airBound['prices']['milesConversion']['convertedMiles']['base'] === 0) {
                    $this->logger->info("skip airBound $numAirBound: no miles");

                    continue;
                }
                $fareFamilyCode = $airBound['fareFamilyCode'];
                $commercialFareFamily = $fareFamilyWithServices[$fareFamilyCode]['commercialFareFamily'];

                $currencyProvider = $prices['totalPrices'][0]['currencyCode'];
                $decimalPlaces = $currency[$currencyProvider]['decimalPlaces'] ?? 1;
                $taxes = $prices['totalPrices'][0]['totalTaxes'] / (10 ** $decimalPlaces);

                $bookingClass = [];

                foreach ($airBound['availabilityDetails'] as $value) {
                    $bookingClass[$value['flightId']] = $value['bookingClass'];
                }

                foreach ($tmp['connections'] as $index => $connection) {
                    $tmp['connections'][$index] = $connection + [
                        'cabin'       => ($commercialFareFamily !== 'FIRST') ? strtolower($commercialFareFamily) : 'firstClass',
                        'fare_class'  => $bookingClass[$connection['flightId']],
                    ];
                }

                $tmp = [
                    'classOfService' => $commercialFareFamily,
                    'award_type'     => $dynamicContent[$fareFamilyCode] ?? null,
                    'redemptions'    => [
                        'miles'   => $prices['milesConversion']['convertedMiles']['base'],
                        'program' => $this->AccountFields['ProviderCode'],
                    ],
                    'payments' => [
                        'currency' => $currencyProvider,
                        'taxes'    => $taxes,
                        'fees'     => null,
                    ],
                ] + $tmp;
                $this->logger->debug(var_export($tmp, true));

                $tmpResults[] = $tmp;
            }
        }

        foreach ($tmpResults as $i => $result) {
            foreach ($result['connections'] as $j => $connection) {
                if (isset($connection['flightId'])) {
                    unset($tmpResults[$i]['connections'][$j]['flightId']);
                }
            }
        }

        $this->keepSession(true);

        return $tmpResults;
    }

    private function getDynamicContent()
    {
        $this->logger->notice(__METHOD__);
        $dynamicContent = \Cache::getInstance()->get('dynamic_content');

        if (!$dynamicContent) {
            try {
                $json = $this->driver->executeScript('
                    var xhr = new XMLHttpRequest();
                    xhr.open("GET", "https://digital.etihad.com/statics/applications/booking/dynamicContent/1.0.172/en.json", false);
                    xhr.onreadystatechange = function() {
                        if (this.readyState == 4 && this.status == 200) {
                            responseText = this.responseText;
                        }
                    };
                    xhr.send();
                    return xhr.responseText;'
                );
            } catch (\WebDriverException $e) {
                $this->logger->debug($e->getMessage(), ['pre' => true]);

                throw new \CheckRetryNeededException(5, 0);
            }

            $dynamicContent = $this->http->JsonLog($json, 0, true);

            if (!$dynamicContent) {
                if (\Cache::getInstance()->add('dynamic_content_error', 1, 60 * 60 * 24)) {
                    $this->sendNotification('check dynamicContent // ZM');
                }

                return [];
            }

            foreach ($dynamicContent as $key => $value) {
                if (strpos($key, 'ALLP.text.Common.FareFamily.') === false || strpos($key, 'Description') !== false) {
                    unset($dynamicContent[$key]);

                    continue;
                }

                $newKey = str_replace('ALLP.text.Common.FareFamily.', '', $key);
                $dynamicContent[$newKey] = $value;
                unset($dynamicContent[$key]);
            }

            $this->logger->debug(var_export($dynamicContent, true), ['pre' => true]);
            \Cache::getInstance()->set('dynamic_content', $dynamicContent, 60 * 60 * 24);
        }

        return $dynamicContent;
    }
}
