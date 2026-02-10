<?php

namespace AwardWallet\Engine\iberia\RewardAvailability;

use AwardWallet\Common\Selenium\FingerprintFactory;
use AwardWallet\Common\Selenium\FingerprintRequest;
use AwardWallet\Engine\ProxyList;
use CheckException;
use CheckRetryNeededException;
use UnexpectedJavascriptException;
use WebDriverBy;

class Parser extends \TAccountChecker
{
    use \SeleniumCheckerHelper;
    use \PriceTools;
    use ProxyList;
    private const XPATH_LOGOUT = '//span[@class = "nav-account-number"]';
    private const XPATH_ERRORS = '//p[
            contains(normalize-space(),"Sorry, an error has occurred. Please try again later.")
            or contains(normalize-space(),"Lo sentimos, se ha producido un error. ")
            or contains(normalize-space(),"Lamentamos, occorreu um erro. ")
            or contains(normalize-space(),"An error has occurred in the Login.")
        ]
        | //div[@id = "Error_Subtitulo" and contains(text(), "The connection was interrupted due to an error,")]
    ';
    private const ATTEMPT_REQUEST_LIMIT = 94;
    private const PARSED_ROUTES_LIMIT = 40;
    private const REQUEST_TIMEOUT = 10;
    private const MAX_LOGIN_FAILS = 45;

    private const CONFIGS = [
        'chrome-95' => [
            'agent'           => "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/95.0.4638.74 Safari/537.36",
            'browser-family'  => \SeleniumFinderRequest::BROWSER_CHROME,
            'browser-version' => \SeleniumFinderRequest::CHROME_95,
        ],
        'chrome-pup-100' => [
            'browser-family'  => \SeleniumFinderRequest::BROWSER_CHROME_PUPPETEER,
            'browser-version' => \SeleniumFinderRequest::CHROME_PUPPETEER_103,
        ],
    ];
    public $isRewardAvailability = true;

    private $headers = [];
    private $systemErrorFare;
    private $config;
    private $newSession;
    private $fingerprint;

    public static function getRASearchLinks(): array
    {
        return ['https://www.iberia.com/us/search-engine-flights-with-avios/' => 'search page'];
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->config = (random_int(0, 1)) ? 'chrome-95' : 'chrome-pup-100';
        $this->logger->info("selected config $this->config");

        $this->http->setHttp2(true);
        $this->KeepState = false;

        switch (random_int(0, 2)) {
            case 0:
                $this->setProxyBrightData(null, 'static', 'fi');

                break;

            case 1:
                $this->setProxyNetNut(null, 'in');

                break;

            case 2:
                $this->setProxyNetNut(null, 'ca');

                break;
        }

        if (self::CONFIGS[$this->config]['browser-family'] !== 'firefox') {
            $request = FingerprintRequest::chrome();
        } else {
            $request = FingerprintRequest::firefox();
        }
        $request->browserVersionMin = 100;
        $request->platform = (random_int(0, 1)) ? 'MacIntel' : 'Win32';
        $this->fingerprint = $this->services->get(FingerprintFactory::class)->getOne([$request]);

        if (isset($this->fingerprint)) {
            $this->http->setUserAgent($this->fingerprint->getUseragent());
        } else {
            if (self::CONFIGS[$this->config]['browser-family'] !== 'firefox') {
                $this->http->setRandomUserAgent(null, false, true, false, true, false);
            } else {
                $this->http->setRandomUserAgent(null, true, false, false, true, false);
            }
        }
    }

    public function IsLoggedIn()
    {
        return false;
    }

    public function LoadLoginForm()
    {
        return true;
    }

    public function Login()
    {
        return true;
    }

    public function getRewardAvailabilitySettings()
    {
        return [
            'supportedCurrencies'      => ['USD'],
            'supportedDateFlexibility' => 0,
            'defaultCurrency'          => 'USD',
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

        if ($fields['Adults'] > 9) {
            $this->SetWarning("It's too much travellers");

            return ["routes" => []];
        }

        if (!isset($this->AccountFields['Login'])) {
            throw new CheckException('no account for login', ACCOUNT_ENGINE_ERROR);
        }
        $this->AccountFields['Login'] = trim(preg_replace("/(?:^IB\s*|[^[:print:]\r\n])/ims", "",
            $this->AccountFields['Login']));
        $this->AccountFields['Pass'] = preg_replace("/^[^[:print:]]*/ims", "",
            $this->AccountFields['Pass']); // AccountID: 4185733

        try {
            $data = $this->selenium($fields);
        } catch (\WebDriverException $e) {
            $this->logger->error('WebDriverException: ' . $e->getMessage());

            throw new CheckRetryNeededException(5, 0);
        } catch (\ErrorException $e) {
            if (strpos($e->getMessage(), 'Array to string conversion') !== false
                || strpos($e->getMessage(), 'strlen() expects parameter 1 to be string, array given') !== false
            ) {
                // TODO бага селениума
                throw new \CheckRetryNeededException(5, 0);
            }

            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Exception: ' . $e->getMessage());

            if (strpos($e->getMessage(), 'session not created') !== false) {
                throw new CheckRetryNeededException(5, 0);
            }

            throw $e;
        }

        if (!$data && $this->ErrorCode == ACCOUNT_WARNING) {
            return [];
        }

        if (is_array($data) && isset($data['routes']) && empty($data['routes'])) {
            return $data;
        }

        if (empty($data) || (isset($data->error) && $data->error == 'unauthorized')) {
            throw new CheckRetryNeededException(5, 0);
        }

        if (isset($data->originDestinations, $data->originDestinations->slices)
            && is_array($data->originDestinations->slices)
            && empty($data->originDestinations->slices)
        ) {
            $this->sendNotification('check warning(error) //ZM');

            throw new \CheckException('check warning(error)', ACCOUNT_ENGINE_ERROR);
//            return ["routes" => []];
        }

        if (isset($data->originDestinations) && is_array($data->originDestinations)
            && isset($data->originDestinations[0]) && isset($data->originDestinations[0]->slices)
            && is_array($data->originDestinations[0]->slices)
            && empty($data->originDestinations[0]->slices)
        ) {
            $this->SetWarning("Sorry, we can't show you the flights. For reasons beyond the control of Iberia, we can't show you the flights available at this time");

            return ["routes" => []];
        }

        if (!isset($data->originDestinations)) {
            if (isset($data->errors[0]) && isset($data->errors[0]->reason)
                && strpos($data->errors[0]->reason,
                    'No availability has been found for the selected search.') !== false) {
                $this->SetWarning($data->errors[0]->reason);

                return ["routes" => []];
            }

            throw new \CheckException('response error', ACCOUNT_ENGINE_ERROR);
        }

        $this->logger->notice("IBERIACOM_SSO_ACCESS: {$this->http->getCookieByName('IBERIACOM_SSO_ACCESS', 'www.iberia.com')}");
        $token = $this->http->getCookieByName('IBERIACOM_SSO_ACCESS', 'www.iberia.com');

        return ["routes" => $this->parseRewardFlights($data, $token, $fields)];
    }

    public function isBadProxy($browser = null): bool
    {
        if (!isset($browser)) {
            $browser = $this->http;
        }

        return
            strpos($browser->Error, 'Network error 28 - Connection timed out after') !== false
            || strpos($browser->Error, 'Network error 56 - Proxy CONNECT aborted') !== false
            || strpos($browser->Error, 'Network error 56 - Unexpected EOF') !== false
            || strpos($browser->Error, 'Network error 56 - Received HTTP code 400 from proxy after CONNECT') !== false
            || strpos($browser->Error, 'Network error 56 - Received HTTP code 403 from proxy after CONNECT') !== false
            || strpos($browser->Error, 'Network error 56 - Received HTTP code 407 from proxy after CONNECT') !== false
            || strpos($browser->Error, 'Network error 56 - Received HTTP code 503 from proxy after CONNECT') !== false
            || strpos($browser->Error, 'Network error 56 - Received HTTP code 502 from proxy after CONNECT') !== false
            || strpos($browser->Error, 'Network error 56 - Recv failure') !== false
            || strpos($browser->Error, 'Network error 0 -') !== false
            || $browser->Response['code'] == 403
            ;
    }

    private function getCabinForSegment(string $cabin)
    {
        $cabins = [
            'TOURIST'        => 'economy', // Economy Class
            'ECONOMY'        => 'economy', // Blue Class
            'PREMIUMTOURIST' => 'premiumEconomy', // Premium Economy
            'BUSINESS'       => 'business', // Business
            'FIRST'          => 'firstClass', // First Class
        ];

        if (isset($cabins[$cabin])) {
            return $cabins[$cabin];
        }
        $this->sendNotification("RA check cabin for segment {$cabin} // MI");

        throw new \CheckException("check cabin for segment {$cabin}", ACCOUNT_ENGINE_ERROR);
    }

    private function getAwardTypeForSegment(string $cabin)
    {
        $cabins = [
            'TOURIST'        => 'Economy Class',
            'ECONOMY'        => 'Blue Class',
            'PREMIUMTOURIST' => 'Premium Economy',
            'BUSINESS'       => 'Business',
            'FIRST'          => 'First Class',
        ];

        if (isset($cabins[$cabin])) {
            return $cabins[$cabin];
        }
        $this->sendNotification("RA check cabin for segment {$cabin} // MI");

        throw new \CheckException("check cabin for segment {$cabin}", ACCOUNT_ENGINE_ERROR);
    }

    private function parseRewardFlights($data, $token, $fields = []): array
    {
        $routes = [];
        $attemptRequest = 0;
        $searchParams = [];

        foreach ($data->originDestinations as $key => $originDestination) {
            foreach ($originDestination->slices as $slice) {
                $this->logger->notice("-- {$slice->sliceId} --");

                foreach ($slice->segments as $i => $segment) {
                    foreach ($segment->offers as $offer) {
                        if ($attemptRequest > self::ATTEMPT_REQUEST_LIMIT && !empty($routes)) {
                            $this->logger->error('Exceeding the limit, stopping');

                            break 3;
                        }

                        if ((time() - $this->requestDateTime) > 105 && count($routes) > 0) {
                            $this->logger->error('Exceeding the limit parsing time, stopping');

                            break 3;
                        }

                        if (count($slice->segments) == 1) {
                            $attemptRequest++;
                            $this->logger->notice("{$offer->bookingClass}");
                            $tmpOffers = [$offer];
                            $selectedOffers = [
                                [
                                    'segmentId' => preg_replace('/[A-Z]$/', '', $offer->offerId),
                                    'offerId'   => $offer->offerId,
                                ],
                            ];
                            $this->logger->notice(var_export($selectedOffers, true));

                            // TODO хотят скипать не кэбины, а партнеров...
                            if ($this->getCabinForSegment($offer->bookingClass) !== $fields['Cabin']) {
                                $this->logger->notice('skip other cabin');
                            }

                            $searchParams[] = [
                                'slice'          => $slice,
                                'selectedOffers' => $selectedOffers,
                                'tmpOffers'      => $tmpOffers,
                            ];
                        } else {
                            for ($j = $i + 1; $j <= count($slice->segments); $j++) {
                                if (isset($slice->segments[$j]->offers)) {
                                    foreach ($slice->segments[$j]->offers as $offer2) {
                                        if (count($slice->segments) == 3) {
                                            for ($k = $j + 1; $k <= count($slice->segments); $k++) {
                                                if (isset($slice->segments[$k]->offers)) {
                                                    foreach ($slice->segments[$k]->offers as $offer3) {
                                                        $attemptRequest++;
                                                        $this->logger->notice("{$offer->bookingClass} -> {$offer2->bookingClass} -> {$offer3->bookingClass}");
                                                        $tmpOffers = [$offer, $offer2, $offer3];
                                                        $selectedOffers = [
                                                            [
                                                                'segmentId' => preg_replace('/[A-Z]$/', '',
                                                                    $offer->offerId),
                                                                'offerId' => $offer->offerId,
                                                            ],
                                                            [
                                                                'segmentId' => preg_replace('/[A-Z]$/', '',
                                                                    $offer2->offerId),
                                                                'offerId' => $offer2->offerId,
                                                            ],
                                                            [
                                                                'segmentId' => preg_replace('/[A-Z]$/', '',
                                                                    $offer3->offerId),
                                                                'offerId' => $offer3->offerId,
                                                            ],
                                                        ];
                                                        $this->logger->notice(var_export($selectedOffers, true));

                                                        if (!in_array($fields['Cabin'],
                                                            [
                                                                $this->getCabinForSegment($offer->bookingClass),
                                                                $this->getCabinForSegment($offer2->bookingClass),
                                                                $this->getCabinForSegment($offer3->bookingClass),
                                                            ])) {
                                                            $this->logger->notice('skip other cabins');
                                                        }

                                                        $searchParams[] = [
                                                            'slice'          => $slice,
                                                            'selectedOffers' => $selectedOffers,
                                                            'tmpOffers'      => $tmpOffers,
                                                        ];
                                                    }
                                                }
                                            }
                                        } else {
                                            $attemptRequest++;
                                            $this->logger->notice("{$offer->bookingClass} -> {$offer2->bookingClass}");
                                            $tmpOffers = [$offer, $offer2];
                                            $selectedOffers = [
                                                [
                                                    'segmentId' => preg_replace('/[A-Z]$/', '', $offer->offerId),
                                                    'offerId'   => $offer->offerId,
                                                ],
                                                [
                                                    'segmentId' => preg_replace('/[A-Z]$/', '', $offer2->offerId),
                                                    'offerId'   => $offer2->offerId,
                                                ],
                                            ];
                                            $this->logger->notice(var_export($selectedOffers, true));

                                            if (!in_array($fields['Cabin'],
                                                [
                                                    $this->getCabinForSegment($offer->bookingClass),
                                                    $this->getCabinForSegment($offer2->bookingClass),
                                                ])) {
                                                $this->logger->notice('skip other cabins');
                                            }

                                            $searchParams[] = [
                                                'slice'          => $slice,
                                                'selectedOffers' => $selectedOffers,
                                                'tmpOffers'      => $tmpOffers,
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $this->logger->notice("");
            }
        }

        $routes = $this->parseRewardFlight($fields, $token, $data, $searchParams);

        if ($this->systemErrorFare && empty($routes)) {
            throw new \CheckException('There has been a system error. Please try again and, if the issue persists, please contact us.', ACCOUNT_PROVIDER_ERROR);
        }

        if ($this->newSession) {
            $this->logger->info("marking config {$this->config} as successful");
            \Cache::getInstance()->set('iberia_config_' . $this->config, 1, 900);
        }

        return $routes;
    }

    private function parseRewardFlight($fields, $token, $data, $searchParams)
    {
        $this->logger->notice(__METHOD__);

        $remainingSeats = [];
        $options = [];
        $routes = [];

        $headers = [
            'Authorization'  => "Bearer {$token}",
            'Accept'         => 'application/json, text/javascript, */*; q=0.01',
            'Content-Type'   => 'application/json; charset=UTF-8',
            'Origin'         => 'https://www.iberia.com',
            'Referer'        => 'https://www.iberia.com/',
        ];

        foreach ($searchParams as $param) {
            foreach ($param['tmpOffers'] as $tmpOffer) {
                $remainingSeats[$tmpOffer->offerId] = $tmpOffer->remainingSeats;
            }
            $postData = [
                'responseId'         => $data->responseId,
                'originDestinations' => [
                    [
                        'originDestinationId' => 'OD0',
                        'selectedSliceId'     => $param['slice']->sliceId,
                        'selectedOffers'      => $param['selectedOffers'],
                    ],
                ],
                'specialNeedQualifierCods' => ["DISA"],
            ];

            $options[] = [
                'method'   => 'POST',
                'sURL'     => 'https://ibisservices.iberia.com/api/sse-rpa/rs/v1/fare',
                'postData' => json_encode($postData),
                'headers'  => $headers,
                'timeout'  => 10,
            ];
        }

        $this->http->sendAsyncRequests($options);

        foreach ($this->http->asyncResponses as $response) {
            $this->http->Response = $response;
            $fare = $this->http->JsonLog(null, 1, false);

            if (is_null($fare)) {
                continue;
            }

            if ($this->http->Response['code'] != 200) {
                if (isset($fare->errors)
                    && isset($fare->errors[0]) && isset($fare->errors[0]->reason)
                ) {
                    $this->logger->error($fare->errors[0]->reason);

                    if (strpos($fare->errors[0]->reason,
                            'There has been a system error. Please try again and, if the issue persists, please contact us.') !== false
                        || strpos($fare->errors[0]->reason,
                            'There has been a communication error with the scoring system. Please try again and, if the problem persists, please contact us') !== false
                        || strpos($fare->errors[0]->reason,
                            'We are having problems with your request. Please try again.') !== false) {
                        $this->systemErrorFare = true;
                    }
                }

                continue;
            }
            $offer = $fare->offers[0];

            // 1 or 2 flights
            if (isset($offer->redemptionOptions)) {
                usort($offer->redemptionOptions, function ($item1, $item2) {
                    return $item1->optionId <=> $item2->optionId;
                });
                $option = $offer->redemptionOptions[0];
                $price = $offer->price;
            } // 3 flights
            elseif (isset($offer->promotion->discountedRedemptionOptions[0])) {
                usort($offer->promotion->discountedRedemptionOptions, function ($item1, $item2) {
                    return $item1->optionId <=> $item2->optionId;
                });
                $option = $offer->promotion->discountedRedemptionOptions[0]->discountedPrice;
                $price = $offer->price;
            } else {
                $this->logger->error('Empty offers->redemptionOptions');

                return false;
            }

            $route = [
                'times'       => ['flight' => null, 'layover' => null],
                'redemptions' => [
                    'miles'   => round($option->totalPoints->fare / $fields['Adults']),
                    'program' => $this->AccountFields['ProviderCode'],
                ],
                'payments' => [
                    'currency' => $option->totalCash->currency,
                    'taxes'    => round(($option->totalCash->fare + $price->total) / $fields['Adults'], 2),
                    'fees'     => null,
                ],
            ];
            $classOfServiceArray = [];
            $slice = $offer->slices[0];

            foreach ($slice->segments as $segment) {
                $seatsKey = $segment->id . $segment->cabin->rbd;
                $classOfService = $this->getAwardTypeForSegment($segment->cabin->bookingClass);

                if (preg_match('/^(.+\w+) class$/i', $classOfService, $m)) {
                    $classOfService = $m[1];
                }
                $classOfServiceArray[] = $classOfService;
                $flNum = $segment->flight->marketingFlightNumber ?? $segment->flight->operationalFlightNumber;
                $route['connections'][] = [
                    'departure' => [
                        'date'     => date('Y-m-d H:i', strtotime($segment->departureDateTime)),
                        'dateTime' => strtotime($segment->departureDateTime),
                        'airport'  => $segment->departure->airport->code,
                        'terminal' => $segment->departure->terminal ?? null,
                    ],
                    'arrival' => [
                        'date'     => date('Y-m-d H:i', strtotime($segment->arrivalDateTime)),
                        'dateTime' => strtotime($segment->arrivalDateTime),
                        'airport'  => $segment->arrival->airport->code,
                        'terminal' => $segment->arrival->terminal ?? null,
                    ],
                    'meal'           => null,
                    'tickets'        => $remainingSeats[$seatsKey],
                    'cabin'          => $this->getCabinForSegment($segment->cabin->bookingClass),
                    'classOfService' => $classOfService,
                    'fare_class'     => $segment->cabin->bookingCode,
                    'award_type'     => $this->getAwardTypeForSegment($segment->cabin->bookingClass),
                    'flight'         => ["{$segment->flight->marketingCarrier->code}{$flNum}"],
                    'airline'        => $segment->flight->marketingCarrier->code,
                    'operator'       => $segment->flight->operationalCarrier->code,
                    'distance'       => null,
                    'aircraft'       => $segment->flight->aircraft->description,
                    'times'          => [
                        'flight'  => null,
                        'layover' => null,
                    ],
                ];
            }
            $classOfServiceArray = array_values(array_unique($classOfServiceArray));

            if (count($classOfServiceArray) === 1) {
                $route['classOfService'] = $classOfServiceArray[0];
            }
            $route['num_stops'] = count($route['connections']) - 1;

            $routes[] = $route;
        }

        return $routes;
    }

    private function selenium(array $fields)
    {
        $this->logger->notice(__METHOD__);
        $selenium = clone $this;
        $this->http->brotherBrowser($selenium->http);
        $responseData = null;

        try {
            $this->logger->notice("Running Selenium...");
            $selenium->UseSelenium();

            $selenium->seleniumRequest->request(self::CONFIGS[$this->config]['browser-family'], self::CONFIGS[$this->config]['browser-version']);

            if ($this->fingerprint) {
                $selenium->seleniumOptions->userAgent = $this->fingerprint->getUseragent();
                $selenium->http->setUserAgent($this->fingerprint->getUseragent());
            }

            $this->seleniumOptions->addHideSeleniumExtension = false;
            $selenium->usePacFile(false);
            $selenium->keepCookies(false);

            $selenium->seleniumRequest->setHotSessionPool(self::class, $this->AccountFields['ProviderCode']);
            $selenium->http->saveScreenshots = true;
            $selenium->disableImages();

            $selenium->http->start();
            $selenium->Start();
            /** @var \SeleniumDriver $seleniumDriver */
            $seleniumDriver = $selenium->http->driver;
            $this->newSession = $seleniumDriver->isNewSession();
            $oldToken = null;
            $selenium->driver->manage()->window()->maximize();

            $currentUrl = $this->http->currentUrl();
            $this->logger->error('Current url - ' . $currentUrl);

            if (!strstr($currentUrl, '/search-engine-flights-with-avios/')) {
                try {
                    $selenium->http->GetURL('https://www.iberia.com/us/');
                } catch (UnexpectedJavascriptException $e) {
                    $this->logger->error("UnexpectedJavascriptException exception on saveResponse: " . $e->getMessage());
                } catch (\ScriptTimeoutException | \TimeOutException $e) {
                    $this->logger->error("TimeOutException exception: " . $e->getMessage());
                }

                if ($cookie = $selenium->waitForElement(WebDriverBy::xpath('//button[@id=\'onetrust-accept-btn-handler\']'), 5)) {
                    sleep(1);
                    $cookie->click();
                }

                $this->clickSearchForm($selenium);
            }

            try {
                $selenium->http->GetURL('https://www.iberia.com/us/search-engine-flights-with-avios/');
            } catch (UnexpectedJavascriptException $e) {
                $this->logger->error("UnexpectedJavascriptException exception on saveResponse: " . $e->getMessage());
            } catch (\ScriptTimeoutException | \TimeOutException $e) {
                $this->logger->error("TimeOutException exception: " . $e->getMessage());
            }

            if ($selenium->waitForElement(\WebDriverBy::xpath("
                    //span[contains(text(), 'This site can’t be reached')]
                    | //h1[normalize-space()='Access Denied']
                    | //div[@id = 'Error_Subtitulo' and contains(text(), 'The connection was interrupted due to an error,')]
                "), 0)
            ) {
                $this->markProxyAsInvalid();

                throw new \CheckRetryNeededException(5, 0);
            }

            $this->savePageToLogs($selenium);

            $cookies = $selenium->driver->manage()->getCookies();

            foreach ($cookies as $cookie) {
                if ($cookie['name'] === 'IBERIACOM_SSO_ACCESS') {
                    $oldToken = $cookie['value'];
                    $this->logger->debug(var_export($cookie, true), ['pre' => true]);
                }
            }

            if (!isset($oldToken)) {
                $selenium->driver->manage()->deleteAllCookies();

                $this->interceptSubmitCredentials($selenium);
            }

            if ($elem = $selenium->waitForElement(\WebDriverBy::xpath('//h2[contains(normalize-space(),"Access to the Iberia Plus personal area is currently blocked for this user.")]'), 0)) {
                $this->logger->error($elem->getText());
                $this->sendNotification('ACCOUNT_LOCKOUT // MI');

                throw new \CheckException($elem->getText(), ACCOUNT_LOCKOUT);
            }

            if ($elem = $selenium->waitForElement(\WebDriverBy::xpath('//p[contains(normalize-space(),"Contraseña, debe tener una longitud de 6 caracteres")]'), 0)) {
                $this->logger->error($elem->getText());

                throw new \CheckException($elem->getText(), ACCOUNT_INVALID_PASSWORD);
            }

            if ($elem = $selenium->waitForElement(\WebDriverBy::xpath(self::XPATH_ERRORS), 0)) {
                $this->logger->error($elem->getText());

                if (strstr($elem->getText(), 'The connection was interrupted due to an error')) {
                    $this->markProxyAsInvalid();
                    $selenium->keepSession(false);
                    $this->markConfigAsBad();

                    throw new \CheckRetryNeededException(5, 0);
                }

                if ($this->isBlockedMessage($elem->getText())) {
                    $this->markConfigAsBad();
                }

                throw new \CheckRetryNeededException(5, 0);
            }

            $this->savePageToLogs($selenium);

            $selenium->waitForElement(\WebDriverBy::xpath("//h1[contains(text(),'Fly with Avios')]"), 20);

            $this->savePageToLogs($selenium);

            //-->  check valid route
            $this->logger->debug('brotherBrowser for check valid route');
            $browser = clone $this;
            $browser->http->setHttp2(true);
            $this->http->brotherBrowser($browser->http);
//            $browser->http->setUserAgent("Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_3) AppleWebKit/604.5.6 (KHTML, like Gecko) Version/11.0.3 Safari/604.5.6");

            $cookies = $selenium->driver->manage()->getCookies();

            foreach ($cookies as $cookie) {
                if ($cookie['name'] === 'IBERIACOM_SSO_ACCESS') {
                    $token = $cookie['value'];
                }
                $browser->http->setCookie($cookie['name'], $cookie['value'], $cookie['domain'], $cookie['path'], $cookie['expiry'] ?? null);
            }

            if (isset($token)) {
                $this->logger->debug('Has an authorization token');

                try {
                    if (is_array($checkRoute = $this->checkValidRouteAjax($fields, $selenium, $token))) {
                        return $checkRoute;
                    }
                } catch (\WebDriverException | \WebDriverCurlException $e) {
                    // рестартить не надо. отрабатывает далее без проблем. даже на горячем при сохранении сессии дальше ок
                    $this->logger->error("WebDriverException: " . $e->getMessage());
                }
                $this->logger->notice('Logged in, saving session');
                $selenium->keepSession(true);

                $headers = [
                    'Authorization' => "Bearer {$token}",
                    'Accept'        => 'application/json, text/plain, */*',
                    'Content-Type'  => 'application/json;charset=UTF-8',
                    'Origin'        => 'https://www.iberia.com',
                    'Referer'       => 'https://www.iberia.com/',
                    'Connection'    => 'keep-alive',
                ];

                $dateDep = date('Y-m-d', $fields['DepDate']);
                $data = '{"slices":[{"origin":"' . $fields['DepCode'] . '","destination":"' . $fields['ArrCode'] . '","date":"' . $dateDep . '"}],"passengers":[{"passengerType":"ADULT","count":' . $fields['Adults'] . '}],"marketCode":"US"}';
                $browser->http->RetryCount = 0;
                $browser->http->PostURL('https://ibisservices.iberia.com/api/sse-rpa/rs/v1/availability', $data, $headers, 10);

                if ($this->isBadProxy($browser->http)
                    || strpos($browser->http->Error, 'Network error 28 - Operation timed out after') !== false
                ) {
                    $browser->http->PostURL('https://ibisservices.iberia.com/api/sse-rpa/rs/v1/availability', $data,
                        $headers, 10);
                }
                $browser->http->RetryCount = 2;

                if ($this->isBadProxy($browser->http)
                    || strpos($browser->http->Error, 'Network error 28 - Operation timed out after') !== false
                    || $browser->http->Response['code'] == 500
                ) {
                    //  TODO: пока без ресета
                    $this->markProxyAsInvalid();

                    throw new CheckRetryNeededException(5, 0);
                }
                $responseData = $browser->http->JsonLog(null, 3, false, 'slices');
            } else {
                $this->logger->debug('No authorization token');

                if ($message = $selenium->waitForElement(WebDriverBy::xpath('//p[contains(@class, "paragraph__regular--modal-claim")]'), 3)) {
                    $this->savePageToLogs($selenium);
                    $this->logger->error($message->getText());

                    if ($this->isBlockedMessage($message->getText())) {
                        $this->markConfigAsBad();

                        throw new CheckRetryNeededException(2, 0, $message->getText(), ACCOUNT_PROVIDER_ERROR);
                    }
                }
                $this->savePageToLogs($selenium);

                if ($this->http->FindSingleNode("//div[contains(@class,'overlay')][contains(.,'Loading...')]")) {
                    $this->logger->notice('still Loading...');

                    throw new CheckRetryNeededException(5, 0);
                }

                return false;
            }

            if (is_null($responseData) && $browser->http->Response['code'] == '204') {
                $this->SetWarning("We can't find any exclusive Iberia Plus seats for the selected date and destination.");
            }
        } catch (\ScriptTimeoutException $e) {
            $this->logger->error("ScriptTimeoutException exception: " . $e->getMessage());
            $this->DebugInfo = "ScriptTimeoutException";
        } catch (\WebDriverCurlException | \Facebook\WebDriver\Exception\WebDriverCurlException | \Facebook\WebDriver\Exception\WebDriverException $e) {
            $this->logger->error($e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'New session attempts retry count exceeded') === false) {
                throw $e;
            }
            $this->logger->error("exception: " . $e->getMessage());
            $this->DebugInfo = "New session attempts retry count exceeded";

            throw new \CheckRetryNeededException(5, 0);
        } finally {
            // close Selenium browser
            $selenium->http->cleanup();
        }

        return $responseData;
    }

    private function interceptSubmitCredentials(Parser $selenium)
    {
        $this->logger->notice(__METHOD__);
        $this->logger->debug('current url: ' . $selenium->http->currentUrl());

        if (!strpos($selenium->http->currentUrl(), 'login.iberia.com/IDY_LoginPage')) {
            try {
                $selenium->http->GetURL('https://www.iberia.com/us/search-engine-flights-with-avios/');
            } catch (UnexpectedJavascriptException $e) {
                $this->logger->error("UnexpectedJavascriptException exception on saveResponse: " . $e->getMessage());
                $this->savePageToLogs($selenium);
            } catch (\ScriptTimeoutException | \TimeOutException $e) {
                $this->logger->error("TimeOutException exception: " . $e->getMessage());

                try {
                    $selenium->http->GetURL('https://www.iberia.com/us/search-engine-flights-with-avios/');
                } catch (UnexpectedJavascriptException $e) {
                    $this->logger->error("UnexpectedJavascriptException exception on saveResponse: " . $e->getMessage());
                    $this->savePageToLogs($selenium);
                } catch (\TimeOutException $e) {
                    $this->logger->error("TimeOutException exception: " . $e->getMessage());
                    $selenium->http->GetURL('https://www.iberia.com/us/search-engine-flights-with-avios/');
                }
            } catch (\Facebook\WebDriver\Exception\UnknownErrorException | \Facebook\WebDriver\Exception\WebDriverException $e) {
                $this->logger->error("UnknownErrorException exception: " . $e->getMessage());

                throw new \CheckRetryNeededException(5, 0);
            }
        }

        if ($selenium->waitForElement(\WebDriverBy::xpath("
                //span[contains(text(), 'This site can’t be reached')]
                | //h1[normalize-space()='Access Denied']
                | //div[@id = 'Error_Subtitulo' and contains(text(), 'The connection was interrupted due to an error,')]
            "), 0)) {
            $this->markProxyAsInvalid();

            throw new \CheckRetryNeededException(5, 0);
        }
        $this->waitFor(function () use ($selenium) {
            return $selenium->waitForElement(WebDriverBy::xpath('//input[@id = "iberia-plus" or @id = "loginPage:theForm:loginEmailInput"]'), 0);
        }, 10);

        if (strpos($selenium->http->currentUrl(), 'login.iberia.com/IDY_LoginPage') !== false) {
            return $this->interceptSubmitCredentials2($selenium);
        }

        throw new CheckException('Change login page, check it!', ACCOUNT_ENGINE_ERROR);
    }

    private function interceptSubmitCredentials2(Parser $selenium): bool
    {
        $this->logger->notice(__METHOD__);

        $loginInput = $selenium->waitForElement(WebDriverBy::xpath('//input[@id=\'loginPage:theForm:loginEmailInput\']'), 0);
        $passwordInput = $selenium->waitForElement(WebDriverBy::xpath('//input[@id=\'loginPage:theForm:loginPasswordInput\']'), 0);
        $button = $selenium->waitForElement(WebDriverBy::xpath('//input[@id=\'loginPage:theForm:loginSubmit\']'), 0);
        $this->savePageToLogs($selenium);

        if (!$loginInput || !$passwordInput || !$button) {
            $this->logger->error('something went wrong');
            $this->savePageToLogs($selenium);

            throw new CheckRetryNeededException(5, 0);
        }

        $loginInput->sendKeys($this->AccountFields['Login']);
        $this->someSleep();
        $passwordInput->sendKeys($this->AccountFields['Pass']);
        $this->someSleep();
        $button->click();

        // save page to logs
        $this->savePageToLogs($selenium);

        $this->logger->debug('need delay');
        $block = $selenium->waitForElement(WebDriverBy::xpath('//div[@id=\'userErrorController\']/label'), 5);

        if ($block) {
            $this->savePageToLogs($selenium);

            if (strstr($block->getText(), 'email might not be registered')) {
                throw new \CheckException($block->getText(), ACCOUNT_PROVIDER_ERROR);
            }

            if (strstr($block->getText(), 'Plus number does not correspond')) {
                \Cache::getInstance()->set('iberia_login_' . $this->AccountFields['Login'], null, 1);

                throw new \CheckException($block->getText(), ACCOUNT_LOCKOUT);
            }

            $this->sendNotification($block->getText() . ' //ND');

            throw new \CheckException($block->getText(), ACCOUNT_PREVENT_LOCKOUT);
        }

        $result = $selenium->waitForElement(WebDriverBy::xpath('//h1[contains(text(), "Fly with Avios")] | ' . self::XPATH_ERRORS), 20);

        if (!$result) {
            $selenium->http->GetURL('https://www.iberia.com/us/search-engine-flights-with-avios/');
        }

        $this->savePageToLogs($selenium);

        return !($selenium->http->currentUrl() !== 'https://www.iberia.com/us/search-engine-flights-with-avios/');
    }

    private function hasCode($data, $code)
    {
        foreach ($data as $row) {
            foreach ($row->cities as $city) {
                if ($city->code === $code) {
                    return true;
                }
            }
        }

        return false;
    }

    private function checkValidRouteAjax($fields, $selenium, $token): ?array
    {
        try {
            $script = '
                var xhttp = new XMLHttpRequest();
                xhttp.open("GET", "https://ibisservices.iberia.com/api/rdu-loc/rs/loc/v1/location/areas/origin/", false);
                xhttp.setRequestHeader("Accept", "application/json, text/plain, */*");
                xhttp.setRequestHeader("Origin", "https://www.iberia.com");
                xhttp.setRequestHeader("Referer", "https://www.iberia.com/");
                xhttp.setRequestHeader("Connection", "keep-alive");
                xhttp.setRequestHeader("Authorization", "Bearer ' . $token . '");
    
                var responseText = null;
                xhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                        responseText = this.responseText;
                    }
                };
                xhttp.send();
                return responseText;
            ';

            $returnData = $selenium->driver->executeScript($tt = $script);

            $this->logger->debug($tt, ['pre' => true]);

            $origin = $selenium->http->JsonLog($returnData, 1);

            if (is_array($origin) && !$this->hasCode($origin, $fields['DepCode'])) {
                $this->SetWarning('no flights from ' . $fields['DepCode']);

                return ['routes' => []];
            }

            $returnData = $selenium->driver->executeScript($tt =
                '
                        var xhttp = new XMLHttpRequest();
                        xhttp.open("GET", "https://ibisservices.iberia.com/api/rdu-loc/rs/loc/v1/location/areas/destination/", false);
                        xhttp.setRequestHeader("Accept", "application/json, text/plain, */*");
                        xhttp.setRequestHeader("Origin", "https://www.iberia.com");
                        xhttp.setRequestHeader("Referer", "https://www.iberia.com/");
                        xhttp.setRequestHeader("Connection", "keep-alive");
                        xhttp.setRequestHeader("Authorization", "Bearer ' . $token . '");
            
                        var responseText = null;
                        xhttp.onreadystatechange = function() {
                            if (this.readyState == 4 && this.status == 200) {
                                responseText = this.responseText;
                            }
                        };
                        xhttp.send();
                        return responseText;
            '
            );

            $this->logger->debug($tt, ['pre' => true]);

            $destination = $selenium->http->JsonLog($returnData, 1);

            if (is_array($destination) && !$this->hasCode($destination, $fields['ArrCode'])) {
                $this->SetWarning('no flights to ' . $fields['ArrCode']);

                return ['routes' => []];
            }

            return null;
        } catch (\Facebook\WebDriver\Exception\InvalidSelectorException | \Facebook\WebDriver\Exception\ScriptTimeoutException | \Facebook\WebDriver\Exception\WebDriverCurlException $e) {
            throw new \CheckRetryNeededException(5, 0);
        }
    }

    private function savePageToLogs($selenium)
    {
        $this->logger->notice(__METHOD__);
        // save page to logs
        $selenium->http->SaveResponse();
        // save page to logs
        $this->http->SetBody($selenium->driver->executeScript('return document.documentElement.innerHTML'));
        $this->http->SaveResponse();
    }

    private function markConfigAsBad(): void
    {
        $this->logger->info("marking config {$this->config} as bad");
        \Cache::getInstance()->set('iberia_config_' . $this->config, 0, 900);
    }

    private function isBlockedMessage(string $message): bool
    {
        return strpos($message, 'Sorry, an error has occurred. Please try again later.') !== false
            || strpos($message, 'Lo sentimos, se ha producido un error. ') !== false
            || strpos($message, 'Lamentamos, occorreu um erro. ') !== false;
    }

    private function someSleep()
    {
        usleep(random_int(70, 250) * 10000);
    }

    private function clickSearchForm(self $selenium)
    {
        $this->logger->notice(__METHOD__);

        $miles = $selenium->waitForElement(\WebDriverBy::xpath("//label[@for=\"paywithAvios\"]"), 5);

        if (!$miles) {
            $selenium->saveResponse();

            throw new \CheckRetryNeededException(5, 0);
        }

        $inputs = [
            'from'       => $selenium->waitForElement(\WebDriverBy::xpath("//input[@id=\"flight_origin1\"]/.."), 5),
            'to'         => $selenium->waitForElement(\WebDriverBy::xpath("//input[@id=\"flight_destiny1\"]/.."), 0),
            'date1'      => $selenium->waitForElement(\WebDriverBy::xpath("//label[@for=\"flight_round_date1\"]"), 0),
            'date2'      => $selenium->waitForElement(\WebDriverBy::xpath("//label[@for=\"flight_return_date1\"]"), 0),
            'miles'      => $miles,
            'passengers' => $selenium->waitForElement(\WebDriverBy::xpath("//button[@id=\"flight_passengers1\"]"), 0),
        ];

        $cntClicks = random_int(3, count($inputs));
        $this->logger->info('Random cnt clicks: ' . $cntClicks);

        while ($cntClicks) {
            $key = array_keys($inputs)[random_int(0, count($inputs) - 1)];
            $this->logger->info('Try to click on: ' . $key);
            $this->clickTextField($inputs[$key]);
            unset($inputs[$key]);
            $cntClicks--;
        }
    }

    private function clickTextField($input)
    {
        if ($input) {
            $input->click();
            $this->someSleep();
        }
    }
}
