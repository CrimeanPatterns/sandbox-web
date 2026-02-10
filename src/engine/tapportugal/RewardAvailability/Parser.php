<?php

namespace AwardWallet\Engine\tapportugal\RewardAvailability;

use AwardWallet\Common\Selenium\FingerprintFactory;
use AwardWallet\Common\Selenium\FingerprintRequest;
use AwardWallet\Engine\ProxyList;
use CheckException;
use CheckRetryNeededException;
use ScriptTimeoutException;
use StaleElementReferenceException;
use WebDriverBy;

class Parser extends \TAccountChecker
{
    use \SeleniumCheckerHelper;
    use \PriceTools;
    use ProxyList;
    private const XPATH_LOGOUT = '//span[@class = "nav-account-number"]';
    public $isRewardAvailability = true;
    private $loyaltyMemberId;
    private $token;
    private $sessionToken;
    private $isLoggedInOnStart = false;
    private $requestId = '';
    private $dataResponseOnlyTap;
    private $dataResponseAlliance;
    private $bodyResponseOnlyTap;
    private $bodyResponseAlliance;
    private $hasTapOnly;
    private $noRoute;

    private $tapRoute;
    private $starAllianceRoute;
    private $changedCabin;

    public static function getRASearchLinks(): array
    {
        return ['https://booking.flytap.com/booking' => 'search page'];
    }

    public function InitBrowser()
    {
        \TAccountChecker::InitBrowser();

        if ($this->AccountFields['ParseMode'] === 'awardwallet') {
            $this->setProxyGoProxies(null, 'pt');
        } else {
            $this->setProxyNetNut(null, 'pt');
        }
        $this->http->setDefaultHeader('Accept-Encoding', 'gzip, deflate, br');
        $this->http->setUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 12_2_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/95.0.4638.74 Safari/537.36');
        $this->KeepState = true;
    }

    public function IsLoggedIn()
    {
        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();

        return true;
    }

    public function Login()
    {
        return true;
    }

    public function getRewardAvailabilitySettings()
    {
        return [
            'supportedCurrencies'      => ['EUR'],
            'supportedDateFlexibility' => 0,
            'defaultCurrency'          => 'EUR',
        ];
    }

    public function ParseRewardAvailability(array $fields)
    {
        $this->logger->info("Parse Reward Availability", ['Header' => 2]);
        $this->logger->debug("params: " . var_export($fields, true));

        if ($fields['DepDate'] > strtotime('+360 day')) {
            $this->SetWarning('You checked too late date');

            return ['routes' => []];
        }
        $warningMsg = null;

        $supportedCurrencies = $this->getRewardAvailabilitySettings()['supportedCurrencies'];

        if (!in_array($fields['Currencies'][0], $supportedCurrencies)) {
            $fields['Currencies'][0] = $this->getRewardAvailabilitySettings()['defaultCurrency'];
            $this->logger->notice("parse with defaultCurrency: " . $fields['Currencies'][0]);
        }

        $origins = \Cache::getInstance()->get('ra_tapportugal_origins');

        if (is_array($origins) && !in_array($fields['DepCode'], $origins)) {
            $this->SetWarning('No flights from ' . $fields['DepCode']);

            return ['routes' => []];
        }

//        if (!$this->validRoute($fields)) {
//            return ['routes' => []];
//        }
        if ($fields['Adults'] > 9) {
            $this->SetWarning("It's too much travellers");

            return ['routes' => []];
        }
        $counter = \Cache::getInstance()->get('ra_tapportugal_failed_auth');

        if ($counter && $counter > 30) {
            $this->logger->error('15 min downtime is on');

            throw new \CheckException('Login temporariamente indisponível.', ACCOUNT_PROVIDER_ERROR);
        }
//        $this->logger->error('temporary off parsing');
//
//        throw new \CheckException("Something went wrong", ACCOUNT_ENGINE_ERROR);
//        if (!$this->isLoggedInOnStart && !$this->selenium()) {
        try {
            if (!$this->isLoggedInOnStart && !$this->selenium2($fields)) {
                throw new \CheckException("Something went wrong", ACCOUNT_ENGINE_ERROR);
            }
        } catch (\NoSuchDriverException $e) {
            $this->logger->error('NoSuchDriverException: ' . $e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        } catch (\Facebook\WebDriver\Exception\WebDriverCurlException $e) {
            $this->logger->error('Facebook\WebDriver\Exception\WebDriverCurlException: ' . $e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        }

        if ($this->noRoute) {
            return ['routes' => []];
        }

        // New search
        $this->logger->info('TAP + ALLIANCE');

        $data = null;

        if (!empty($this->dataResponseAlliance)) {
            $data = $this->dataResponseAlliance;
            $this->http->SetBody($this->bodyResponseAlliance);
        } elseif (!isset($this->tapRoute, $this->starAllianceRoute) || $this->starAllianceRoute) {
            $data = $this->otherTypeSearch($fields, true);
        }
        $routes = [];

        if (isset($data)) {
            if (empty($data->data)) {
                if (isset($data->errors[0])) {
                    $warningMsg = $this->http->FindPreg('/"desc":"(NO ITINERARY FOUND FOR REQUESTED SEGMENT.+?)"/') ??
                        $this->http->FindPreg('/"desc":"(No available flight found for the requested segment.+?)"/') ??
                        $this->http->FindPreg('#"desc":"(Unknown City/Airport)"#') ??
                        $this->http->FindPreg('#"desc":"(Bad value \(coded\) - timeDetails)"#') ??
                        $this->http->FindPreg('/"desc":"(NO\s+FARE\s+FOUND\s+FOR\s+REQUESTED\s+ITINERARY)"/m');

                    if (!$warningMsg) {
                        if ($this->http->FindPreg('/"desc":"(Transaction unable to process)/')
                            || $this->http->FindPreg('/"code":"404","type":"ERROR"/')
                            || $this->http->FindPreg('/"code":"Read timed out","type":"ERROR","desc":"404"/')
                            || $this->http->FindPreg('/"code":"404","type":"ERROR","desc":"Read timed out"/')
                        ) {
                            throw new CheckRetryNeededException(5, 0);
                        }
                        $this->sendNotification('check error // ZM');

                        throw new \CheckException("Something went wrong", ACCOUNT_ENGINE_ERROR);
                    }
                } else {
                    if ($this->http->Response['code'] == 403) {
                        throw new CheckRetryNeededException(5, 0);
                    }

                    throw new \CheckException("Something went wrong", ACCOUNT_ENGINE_ERROR);
                }
            } elseif (empty($data->data->offers)) {
                $warningMsg = 'Select another date with available flights';
            } else {
                $routes = $this->parseRewardFlights($data, $fields, true);
            }
        }

        $this->logger->info('TAP ONLY');
        $data = null;

        if (!empty($this->dataResponseOnlyTap)) {
            $data = $this->dataResponseOnlyTap;
            $this->http->SetBody($this->bodyResponseOnlyTap);
        } elseif ($this->hasTapOnly || !isset($this->tapRoute, $this->starAllianceRoute)) {
            $data = $this->otherTypeSearch($fields, false);
        }

        if (isset($data)) {
            if (empty($data->data)) {
                if (isset($data->errors[0])) {
                    $warningMsg1 = $this->http->FindPreg('/"desc":"(NO ITINERARY FOUND FOR REQUESTED SEGMENT.+?)"/') ??
                        $this->http->FindPreg('/"desc":"(No available flight found for the requested segment.+?)"/') ??
                        $this->http->FindPreg('#"desc":"(Unknown City/Airport)"#') ??
                        $this->http->FindPreg('#"desc":"(Bad value \(coded\) - timeDetails)"#') ??
                        $this->http->FindPreg('/"desc":"(NO\s+FARE\s+FOUND\s+FOR\s+REQUESTED\s+ITINERARY)"/m');

                    if (!$warningMsg1 && (empty($routes) || empty($warningMsg))) {
                        if (empty($routes)) {
                            if ($this->http->FindPreg('/"desc":"(Transaction unable to process)/')) {
                                throw new CheckRetryNeededException(5, 0);
                            }
                            $this->sendNotification('check error // ZM');

                            throw new \CheckException("Something went wrong", ACCOUNT_ENGINE_ERROR);
                        }
                        $this->sendNotification('check error // ZM');
                    } elseif ($warningMsg1 && empty($warningMsg)) {
                        $warningMsg = $warningMsg1;
                    }
                } elseif (empty($routes)) {
                    if ($this->http->Response['code'] == 403) {
                        throw new CheckRetryNeededException(5, 0);
                    }

                    throw new \CheckException("Something went wrong", ACCOUNT_ENGINE_ERROR);
                }
            } elseif (empty($data->data->offers)) {
                $warningMsg = 'Select another date with available flights';
            } else {
                $routesOnlyTap = $this->parseRewardFlights($data, $fields, false);

                if (!empty($routesOnlyTap) && $routesOnlyTap != $routes) {
                    $allRoutes = array_merge($routesOnlyTap, $routes);
                    $routes = array_map('unserialize', array_unique(array_map('serialize', $allRoutes)));
                }
            }
        }

        if (isset($this->changedCabin) && count($this->changedCabin) === 2 && isset($this->tapRoute, $this->starAllianceRoute) && $this->tapRoute && $this->starAllianceRoute && empty($warningMsg)) {
            $this->logger->notice('possible duplicates with different cabins'); // так-то руками не находились, но на всякий влог отметка
        }

        if (empty($routes) && !empty($warningMsg)) {
            if ($warningMsg === "Bad value (coded) - timeDetails") {
                $warningMsg = 'Select another date with available flights';
            }
            $this->SetWarning($warningMsg);
        }

        return ['routes' => $routes];

        $routes = [];
        // TAP flights only
        $emptyResult = false;
        $this->queryRewardFlights($fields, 'False');
        $data = $this->http->JsonLog(null, 0);

        if (!isset($data->d)) {
            if ($this->http->FindPreg("/The selected route is not available. Please select a valid route using fields/")) {
                $warningMsg = "The selected route is not available. Please select a valid route using fields";
                $emptyResult = true;
            } else {
                throw new \CheckException("Something went wrong", ACCOUNT_ENGINE_ERROR);
            }
        } else {
            $data = $this->http->JsonLog($data->d);
        }

        if (isset($data, $data->StackTrace) && $data->StackTrace === '' && isset($data, $data->Message)
            && $this->http->FindPreg("/We are unable to find recommendations for your search. Please change your search criteria and resubmit the search/",
                false, $data->Message)
        ) {
            $warningMsg = 'We are unable to find recommendations for your search. Please change your search criteria and resubmit the search';
            $emptyResult = true;
        }

        if (isset($data, $data->StackTrace) && $data->StackTrace === '' && isset($data, $data->Message)
            && $this->http->FindPreg("/Your cities of departure and arrival are the same. Please modify your request and try again/",
                false, $data->Message)
        ) {
            $this->SetWarning('Your cities of departure and arrival are the same. Please modify your request and try again');

            return ["routes" => $routes];
        }

        if (!isset($data, $data->PriceOptionCollection)) {
            if ($this->http->FindPreg('/\[InternalEmpty\]PriceOptionCollection is empty or null\./')
                || $this->http->FindPreg('/StarAwardsUrl.+?starAwards=True/')
                || (isset($data, $data->Message) && $data->Message === 'Pricing option collection is not defined')
            ) {
                $emptyResult = true;
            }

            if (isset($this->requestId)) {
                $depDateString = date('d.m.Y', $fields['DepDate']);
                $this->http->GetURL("https://book.flytap.com/air/TAPMilesAndGo/Calendar.aspx?errorCode=2&pageTrace=21&_l=en&requestID={$this->requestId}&flightType=Single&origin={$fields['DepCode']}&destination={$fields['ArrCode']}&negotiatedFaresOnly=False&milesAndCash=0&maxConn=-1&depDate={$depDateString}&adt={$fields['Adults']}&");
            }

            if ($msg = $this->http->FindSingleNode("//p[contains(.,'Attention Please')]/following-sibling::p")) {
                $this->logger->error($msg);
                $warningMsg = $msg;
                $emptyResult = true;
            }
        }

        if (!$emptyResult) {
            $routes = $this->parseRewardFlights($data, $fields);
        }

        // TAP + Star Alliance flights
        $emptyResult = false;
        $this->queryRewardFlights($fields, 'True');
        $data = $this->http->JsonLog(null, 0);

        if (!isset($data->d)) {
            if ($this->http->FindPreg("/The selected route is not available. Please select a valid route using fields/")) {
                $warningMsg = 'The selected route is not available. Please select a valid route using fields';
                $emptyResult = true;
            } else {
                throw new \CheckException("Something went wrong", ACCOUNT_ENGINE_ERROR);
            }
        } else {
            $data = $this->http->JsonLog($data->d);
        }

        if (!isset($data, $data->PriceOptionCollection)) {
            if ($this->http->FindPreg('/\[InternalEmpty\]PriceOptionCollection is empty or null\./')
                || (isset($data, $data->Message) && $data->Message === 'Pricing option collection is not defined')
            ) {
                $emptyResult = true;
            }

            if (isset($this->requestId)) {
                $depDateString = date('d.m.Y', $fields['DepDate']);
                $this->http->GetURL("https://book.flytap.com/air/TAPMilesAndGo/Calendar.aspx?errorCode=2&pageTrace=21&_l=en&requestID={$this->requestId}&flightType=Single&origin={$fields['DepCode']}&destination={$fields['ArrCode']}&negotiatedFaresOnly=False&milesAndCash=0&maxConn=-1&depDate={$depDateString}&adt={$fields['Adults']}&");
            }

            if ($msg = $this->http->FindSingleNode("//p[contains(.,'Attention Please')]/following-sibling::p")) {
                $this->logger->error($msg);
                $warningMsg = $msg;
                $emptyResult = true;
            }
        }

        if (isset($data, $data->StackTrace) && $data->StackTrace === '' && isset($data, $data->Message)
            && $this->http->FindPreg("/We are unable to find recommendations for your search. Please change your search criteria and resubmit the search/",
                false, $data->Message)
        ) {
            $this->SetWarning('We are unable to find recommendations for your search. Please change your search criteria and resubmit the search');

            return ["routes" => $routes];
        }

        if (!$emptyResult) {
            $allianceRoutes = $this->parseRewardFlights($data, $fields);

            if ($allianceRoutes != $routes) {
                $allRoutes = array_merge($allianceRoutes, $routes);
                $routes = array_map('unserialize', array_unique(array_map('serialize', $allRoutes)));
            }
        }

        if (empty($routes) && !empty($warningMsg)) {
            $this->SetWarning($warningMsg);
        }

        return ["routes" => $routes];
    }

    private function getCabin(string $cabin, bool $isFlip = true)
    {
        $cabins = [
            'economy' => 'Economy', // basic
            //'premiumEconomy' => '', //
            'business'   => 'Business', //  executive
            //'firstClass' => 'First',
        ];

        if ($isFlip) {
            $cabins = array_flip($cabins);
        }

        if (isset($cabins[$cabin])) {
            return $cabins[$cabin];
        }
        $this->sendNotification("RA check cabin {$cabin} (" . var_export($isFlip, true) . ") // MI");

        throw new \CheckException("check cabin {$cabin} (" . var_export($isFlip, true) . ")", ACCOUNT_ENGINE_ERROR);
    }

    private function getCabinNew(string $cabin): string
    {
        $cabins = [
            'economy'   => 'economy',
            'executive' => 'business',
        ];

        if (isset($cabins[$cabin])) {
            return $cabins[$cabin];
        }
        $this->sendNotification("RA check cabin {$cabin} // MI");

        throw new \CheckException("check cabin {$cabin}", ACCOUNT_ENGINE_ERROR);
    }

    private function getCabinAlliance(string $cabin): string
    {
        $cabins = [
            'economy'   => 'X',
            'executive' => 'I',
        ];

        if (isset($cabins[$cabin])) {
            return $cabins[$cabin];
        }
        $this->sendNotification("RA check cabin alliance {$cabin} // MI");

        throw new \CheckException("check cabin alliance {$cabin}", ACCOUNT_ENGINE_ERROR);
    }

    /*private function getCabinRbd(string $cabin): string
    {
        $cabins = [
            'X' => 'economy',
            'I' => 'business',
        ];

        if (isset($cabins[$cabin])) {
            return $cabins[$cabin];
        }
        $this->sendNotification("RA check cabin rbd {$cabin} // MI");

        throw new \CheckException("check cabin rbd {$cabin}", ACCOUNT_ENGINE_ERROR);
    }*/

    private function queryRewardFlights($fields, $starAwards = 'True')
    {
        $this->logger->notice(__METHOD__);
        $depDateString = date('d.m.Y', $fields['DepDate']);
        $depDate = date('Y-m-d\TH:i:s', $fields['DepDate']);
        $query = [
            'pageTrace'           => '21',
            'market'              => '',
            '_l'                  => 'en',
            'requestID'           => '5464608726960792937',
            'flightType'          => 'Single',
            'origin'              => $fields['DepCode'],
            'destination'         => $fields['ArrCode'],
            'negotiatedFaresOnly' => 'False',
            'milesAndCash'        => '0',
            'maxConn'             => '-1',
            'depDate'             => $depDateString,
            'adt'                 => $fields['Adults'],
            'resident'            => '',
            'giftCode'            => '&',
            'starAwards'          => $starAwards,
        ];

        $this->http->setOriginHeader = false;
        $this->http->GetURL('https://book.flytap.com/air/TAPMilesAndGo/SelectRedemption.aspx?' . http_build_query($query));

        parse_str(html_entity_decode($this->http->FindSingleNode("//form[@id='aspnetForm']/@action")), $output);
        $this->logger->debug(var_export($output, true));

        if (empty($output) || !isset($output['depDate'])) {
            if ($msg = $this->http->FindPreg("/(The selected route is not available. Please select a valid route using fields)/")) {
                $this->SetWarning($msg);

                return;
            }

            if ($this->http->FindPreg("/(We are experiencing technical problems at the moment and are unable to proceed with your request. Please try again later)/")) {
                throw new \CheckException('technical problems at the moment', ACCOUNT_PROVIDER_ERROR);
            }

            if (isset($output['_/Search_aspx?errorCode']) && $output['_/Search_aspx?errorCode'] === '3'
                && $this->http->FindSingleNode("//p[normalize-space()='Attention Please']/following-sibling::p[normalize-space()='URL malformed']")
            ) {
                if (strtotime("-1 day", $fields['DepDate']) < time()) {
                    $this->SetWarning('It is not possible to book online within less than 24 hours before flight departure. Please review your selection.');
                } else {
                    $this->SetWarning('The selected route is not available. Please review your selection.');
                    $this->sendNotification('check msg 2 // ZM');
                }

                return;
            }

            throw new \CheckException('something went wrong', ACCOUNT_ENGINE_ERROR);
        }
        $headers = [
            'X-Requested-With' => "XMLHttpRequest",
            'Accept'           => 'application/json, text/javascript, */*; q=0.01',
            'Content-Type'     => 'application/json; charset=UTF-8',
        ];
        $this->http->RetryCount = 0;
        $data = '{
          "availabilityRequest": {
            "segments": [
              {
                "Origin": "' . $fields['DepCode'] . '",
                "Destination": "' . $fields['ArrCode'] . '",
                "DepTime": null,
                "DepDate": "' . $depDate . '",
                "DepDateString": "' . $depDateString . '"
              }
            ],
            "loyaltyProgramMemberId": "' . $this->loyaltyMemberId . '",
            "tierLevel": "Miles",
            "starAwards": ' . strtolower($starAwards) . ',
            "_a": "",
            "origin": "' . $fields['DepCode'] . '",
            "destination": "' . $fields['ArrCode'] . '",
            "depDate": "' . $output['depDate'] . '",
            "retDate": "",
            "depTime": "",
            "retTime": "",
            "flightType": "Single",
            "adt": "' . $output['adt'] . '",
            "chd": "",
            "inf": "",
            "src": "",
            "stu": "",
            "yth": "",
            "yad": "0",
            "cabinClass": "",
            "_l": "en",
            "promoCode": "",
            "congressCode": "",
            "agentCode": "",
            "resident": "",
            "market": "",
            "contract": "TAPVictoria",
            "requestID": "' . $output['requestID'] . '",
            "sessionId": "",
            "pageTicket": "",
            "selectedBP": "",
            "_debug": false,
            "isB2B": false,
            "maxConn": "",
            "isPreviuosFpp": false,
            "negotiatedFaresOnly": false,
            "stopover": "",
            "nights": "0",
            "stayMulti": 0,
            "milesAndCash": 0,
            "uh": "' . $output['uh'] . '"
          }
        }';
        $this->http->PostURL('https://book.flytap.com/air/WebServices/Availability/TAPAvailability.asmx/GetMulticityFlights?_l=en',
            json_decode(json_encode($data)),
            $headers
        );
        $this->http->RetryCount = 2;
        $this->requestId = $output['requestID'];
    }

    private function otherTypeSearch($fields, $starAlliance = false)
    {
        $this->logger->notice(__METHOD__);
        // 401
        //$this->http->GetURL("https://booking.flytap.com/bfm/rest/search/pax/types?market=US&journeyList={$fields['DepCode']},{$fields['ArrCode']}&tripType=O");

        $dateStr = date('dmY', $fields['DepDate']);
        $headers = [
            'Accept'        => 'application/json, text/plain, */*',
            'Authorization' => 'Bearer ' . $this->sessionToken,
            'Content-Type'  => 'application/json',
            'Origin'        => 'https://booking.flytap.com',
            'Referer'       => 'https://booking.flytap.com/booking/flights',
        ];
        $payload = '{"adt":' .
            $fields['Adults'] . ',"airlineId":"TP","c14":0,"cabinClass":"E","chd":0,"departureDate":["' . $dateStr . '"],"destination":["' . $fields['ArrCode'] . '"],"inf":0,"language":"en-us","market":"US","origin":["' . $fields['DepCode'] . '"],"passengers":{"ADT":' . $fields['Adults'] . ',"YTH":0,"CHD":0,"INF":0},"returnDate":"' . $dateStr . '","tripType":"O","validTripType":true,"payWithMiles":true,"starAlliance":' . var_export($starAlliance, true) . ',"yth":0}';
        $this->http->RetryCount = 0;
        $this->http->PostURL("https://booking.flytap.com/bfm/rest/booking/availability/search?payWithMiles=true&starAlliance=" . var_export($starAlliance, true), $payload, $headers, 30);
        $this->http->RetryCount = 2;

        if ($this->http->Response['code'] == 503
            || ($flag500 = $this->http->FindPreg('/"status":"(500)"/'))
            || strpos($this->http->Error, 'Network error 28 - Operation timed out after ') !== false
            || strpos($this->http->Error, 'Network error 28 - Connection timed out after ') !== false
        ) {
            sleep(5);
            $this->http->RetryCount = 0;
            $this->http->PostURL("https://booking.flytap.com/bfm/rest/booking/availability/search?payWithMiles=true&starAlliance=" . var_export($starAlliance, true), $payload, $headers, 30);
            $this->http->RetryCount = 2;

            if ($flag500 && $this->http->FindPreg('/"status":"(500)"/')) {
                $this->sendNotification('RA success retry // ZM');
            }
        }

        if (strpos($this->http->Error, 'Network error 56 - Received HTTP code 407 from proxy after CONNECT') !== false
            || strpos($this->http->Error, 'Network error 56 - Received HTTP code 400 from proxy after CONNECT') !== false
            || strpos($this->http->Error, 'Network error 56 - Received HTTP code 503 from proxy after CONNECT') !== false
            || strpos($this->http->Error, 'Network error 28 - Operation timed out after ') !== false
            || strpos($this->http->Error, 'Network error 28 - Connection timed out after ') !== false
            || $this->http->Response['code'] == 403
            || $this->http->FindPreg('/"desc":"Server is busy, please try again in a few minutes/')
            || $this->http->FindPreg('/"desc":"Read timed out/')
        ) {
            throw new CheckRetryNeededException(5, 0);
        }

        if ($this->http->Response['code'] != 200) {
            throw new \CheckRetryNeededException(5, 0);
        }

        return $this->http->JsonLog();
    }

    private function parseRewardFlights($data, $fields = [], $starAlliance = true): array
    {
        $this->logger->notice(__METHOD__);
        $routes = [];

        foreach ($data->data->listOutbound as $outbound) {
            foreach ($outbound->relateOffer as $keyRoute => $rateOffer) {
                $offer = null;

                foreach ($data->data->offers->listOffers as $itemOffer) {
                    if ($itemOffer->idOffer == $rateOffer) {
                        $offer = $itemOffer;

                        break;
                    }
                }

                if (empty($offer)) {
                    $this->logger->info('skip offer ' . $itemOffer->idOffer . ' no data');

                    continue;
                }
                $fareFamily = $offer->outFareFamily;
                //$cabin = $this->getCabinNew(strtolower($offer->outbound->cabin[0]));
                $route = [
                    'distance'  => null,
                    'num_stops' => $outbound->numberOfStops,
                    'times'     => [
                        'flight'  => $this->convertMinDuration($outbound->duration),
                        'layover' => null,
                    ],
                    'redemptions' => [
                        'miles'   => round($offer->outbound->totalPoints->price / $fields['Adults']),
                        'program' => $this->AccountFields['ProviderCode'],
                    ],
                    'payments' => [
                        'currency' => $data->data->offers->currency,
                        'taxes'    => round(($offer->outbound->totalPrice->tax + $offer->outbound->totalPrice->obFee) / $fields['Adults'], 2),
                        'fees'     => null,
                    ],
                    'connections'     => [],
                    'tickets'         => null,
                    'award_type'      => null,
                    'classOfService'  => $this->convertClassOfService($offer->outFareFamily),
                ];
                // Connections
                foreach ($outbound->listSegment as $keyConn => $segment) {
                    $cabin = $this->getCabinNew(strtolower($offer->outbound->cabin[$keyConn]));
                    //$bounds = [$offer->outbound, $offer->inbound];
                    $rbd = $offer->outbound->rbd[$keyConn];

                    if (!$starAlliance && $fareFamily == 'AWEXECU' && !in_array($rbd, ['I', 'Z'])) {
                    } elseif ($outbound->numberOfStops > 0 && /*$starAlliance &&*/ in_array($fareFamily, ['AWEXECU', 'AWFIRST']) && $rbd != 'I') {
                        $this->logger->notice("Change $cabin for economy");

                        if ($cabin != 'economy') {
                            if (isset($this->changedCabin)) {
                                $this->changedCabin[$starAlliance] = true;
                            } else {
                                $this->changedCabin = [$starAlliance => true];
                            }
                        }
                        $cabin = 'economy';
                    } elseif ($fareFamily == 'AWEXECU' && !in_array($rbd, ['C', 'Z', 'I', 'J'])) {
                    }
                    //
                    // Sd = ["C", "Z", "I", "J"], Md = ["I", "Z"], kd = ["I"],
                    /*e.d(t, "wl", (function() {
                        return Sd
                    }
                    )),
                    e.d(t, "vl", (function() {
                        return Md
                    }
                    )),
                    e.d(t, "nl", (function() {
                        return kd
                    }
                    )),*/

                    $route['connections'][] = [
                        'num_stops' => count($segment->technicalStops ?? []),
                        'departure' => [
                            'date'     => date('Y-m-d H:i', strtotime($segment->departureDate)),
                            'dateTime' => strtotime($segment->departureDate),
                            'airport'  => $segment->departureAirport,
                            'terminal' => $segment->departureTerminal,
                        ],
                        'arrival' => [
                            'date'     => date('Y-m-d H:i', strtotime($segment->arrivalDate)),
                            'dateTime' => strtotime($segment->arrivalDate),
                            'airport'  => $segment->arrivalAirport,
                            'terminal' => $segment->arrivalTerminal,
                        ],
                        'meal'       => null,
                        'cabin'      => $cabin,
                        //'fare_class' => $starAlliance ? $this->getCabinAlliance($cabin) : null,
                        'flight'     => ["{$segment->carrier}{$segment->flightNumber}"],
                        'airline'    => $segment->carrier,
                        'operator'   => $segment->operationCarrier,
                        'distance'   => null,
                        'aircraft'   => $segment->equipment,
                        'times'      => [
                            'flight'  => $this->convertMinDuration($segment->duration),
                            'layover' => $this->convertMinDuration($segment->stopTime),
                        ],
                    ];
                }
                $route['num_stops'] = count($route['connections']) - 1 + array_sum(array_column($route['connections'], 'num_stops'));
                $this->logger->debug(var_export($route, true), ['pre' => true]);
                $routes[] = $route;
            }
        }
        /*foreach ($data->data->offers->listOffers as $offers) {
            foreach ($offers->groupFlights as $flight) {
                foreach ($data->data->listOutbound as $outbound) {
                    if (in_array($flight->idOutBound, $outbound->relateOffer)) {
                        $this->logger->debug( "idOutBound: $flight->idOutBound  - relateOffer: ".var_export($outbound->relateOffer, true));
                        $route = [
                            'distance'  => null,
                            'num_stops' => $outbound->numberOfStops,
                            'times'     => [
                                'flight'  => $this->convertMinDuration($outbound->duration),
                                'layover' => null,
                            ],
                            'redemptions' => [
                                'miles'   => $offers->outbound->totalPoints->price / $fields['Adults'],
                                'program' => $this->AccountFields['ProviderCode'],
                            ],
                            'payments' => [
                                'currency' => $data->data->offers->currency,
                                'taxes'    => ($offers->outbound->totalPrice->tax + $offers->outbound->totalPrice->obFee) / $fields['Adults'],
                                'fees'     => null,
                            ],
                            'connections' => [],
                            'tickets'     => null,
                            'award_type'  => null,
                        ];

                        // Connections
                        foreach ($outbound->listSegment as $key => $segment) {
                            $this->logger->debug("cabin: ".var_export($offers->outbound->cabin, true) . " - $key");
                            $this->logger->debug('Parsed data:');
                            $this->logger->debug(var_export($route, true), ['pre' => true]);
                            $cabin = $this->getCabinNew(strtolower($offers->outbound->cabin[0]));
                             if (strtolower($cabin) != $fields['Cabin']) {
                                $this->logger->notice("Skip {$cabin}");
                                continue 2
;                           }
                            $route['connections'][] = [
                                'departure' => [
                                    'date'     => date('Y-m-d H:i', strtotime($segment->departureDate)),
                                    'dateTime' => strtotime($segment->departureDate),
                                    'airport'  => $segment->departureAirport,
                                    'terminal' => $segment->departureTerminal,
                                ],
                                'arrival' => [
                                    'date'     => date('Y-m-d H:i', strtotime($segment->arrivalDate)),
                                    'dateTime' => strtotime($segment->arrivalDate),
                                    'airport'  => $segment->arrivalAirport,
                                    'terminal' => $segment->arrivalTerminal,
                                ],
                                'meal'       => null,
                                'cabin'      => $cabin,
                                //'fare_class' => $offers->outbound->rbd[$key],
                                'flight'     => ["{$segment->carrier}{$segment->flightNumber}"],
                                'airline'    => $segment->carrier,
                                'operator'   => $segment->operationCarrier,
                                'distance'   => null,
                                'aircraft'   => $segment->equipment,
                                'times'      => [
                                    'flight'  => $this->convertMinDuration($segment->duration),
                                    'layover' => $this->convertMinDuration($segment->stopTime),
                                ],
                            ];
                        }
                        $route['times']['layover'] = $this->sumLayovers($route['connections']);

                        $routes[] = $route;
                    }
                }
            }
        }*/

        return $routes;
    }

    private function convertClassOfService(string $str): ?string
    {
        switch ($str) {
            case "AWBASIC":
                return 'Economy';

            case "AWEXECU":
                return 'Business';
        }
        $this->sendNotification('check outFareFamily: ' . $str);

        return null;
    }

    private function parseRewardFlightsOld($data, $fields = []): array
    {
        $routes = [];

        foreach ($data->PriceOptionCollection as $price) {
            $route = [
                'distance'  => null,
                'num_stops' => null,
                'times'     => [
                    'flight'  => null,
                    'layover' => null,
                ],
                'redemptions' => [
                    'miles'   => $price->DisplayPriceWithDiscount / $fields['Adults'],
                    'program' => $this->AccountFields['ProviderCode'],
                ],
                'payments' => [
                    'currency' => $price->DisplayCashCurrency,
                    'taxes'    => $price->DisplayCashPrice / $fields['Adults'],
                    'fees'     => null,
                ],
                'connections' => [],
                'tickets'     => null,
                'award_type'  => null,
            ];

            if (count($price->LegCollection) > 1) {
                $this->sendNotification('RA check LegCollection // MI');

                throw new \CheckException('RA check LegCollection', ACCOUNT_ENGINE_ERROR);
            }

            foreach ($price->LegCollection as $leg) {
                foreach ($leg->Segments as $segment) {
                    $route['connections'][] = [
                        'departure' => [
                            'date'     => date('Y-m-d H:i', strtotime($segment->DepDate)),
                            'dateTime' => strtotime($segment->DepDate),
                            'airport'  => $segment->OriginTLC,
                            'terminal' => $segment->OriginTerminal,
                        ],
                        'arrival' => [
                            'date'     => date('Y-m-d H:i', strtotime($segment->ArrDate)),
                            'dateTime' => strtotime($segment->ArrDate),
                            'airport'  => $segment->DestinationTLC,
                            'terminal' => $segment->DestinationTerminal,
                        ],
                        'meal'       => null,
                        'cabin'      => $this->getCabin($segment->CabinClass),
                        'fare_class' => $segment->BookingClassCode,
                        'flight'     => ["{$segment->FlightString}"],
                        'airline'    => $segment->CarrierCode,
                        'operator'   => $segment->CarrierCode,
                        'distance'   => null,
                        'aircraft'   => $segment->Aircraft,
                        'times'      => [
                            'flight'  => $this->convertDuration($segment->Duration),
                            'layover' => $this->convertDuration($segment->CalculatedLayover),
                        ],
                    ];
                }
                $route['num_stops'] = $leg->LayoverCount;
                $route['award_type'] = $leg->BrandedProduct;
                $route['times'] = [
                    'flight' => $this->sumLayovers($route['connections'], 'flight'),
                    //                    'flight' => $this->convertDuration($leg->LegDuration),
                    'layover' => $this->sumLayovers($route['connections']),
                ];
            }

            $this->logger->debug('Parsed data:');
            $this->logger->debug(var_export($route, true), ['pre' => true]);
            $routes[] = $route;
        }

        return $routes;
    }

    private function convertMinDuration($minutes)
    {
        $format = gmdate('H:i', $minutes * 60);

        if ($format == '00:00') {
            return null;
        }

        return $format;
    }

    private function convertDuration($duration)
    {
        if (preg_match("/^(\d+)\s*[hrs]+\s*(\d+)\s*[min]+$/", $duration, $m)) {
            return sprintf('%02d:%02d', $m[1], $m[2]);
        } elseif (preg_match("/^(\d+)\s*[min]+$/", $duration, $m)) {
            return sprintf('%02d:%02d', 0, $m[1]);
        }

        return null;
    }

    private function sumLayovers($connections, $fieldName = 'layover')
    {
        $minutesLayover = 0;

        foreach ($connections as $value) {
            if (isset($value['times'][$fieldName])) {
                [$hour, $minute] = explode(':', $value['times'][$fieldName]);
                $minutesLayover += $hour * 60;
                $minutesLayover += $minute;
            }
        }
        $hoursLayover = floor($minutesLayover / 60);
        $minutesLayover -= floor($minutesLayover / 60) * 60;

        return ($hoursLayover + $minutesLayover > 0) ?
            sprintf('%02d:%02d', $hoursLayover, $minutesLayover) : null;
    }

    private function selenium()
    {
        $this->logger->notice(__METHOD__);
        $this->logger->debug('[Parse start date: ' . date('Y/m/d H:i:s') . ']');
        $startTimer = $this->getTime();
        $login = false;
        $retry = false;
        $selenium = clone $this;
        $this->selenium = true;
        $this->http->brotherBrowser($selenium->http);
        $error = null;

        try {
            $this->logger->notice("Running Selenium...");
            $selenium->UseSelenium();
//            $selenium->useGoogleChrome();
            $selenium->useFirefox(\SeleniumFinderRequest::FIREFOX_59);

            $request = FingerprintRequest::firefox();
            $request->browserVersionMin = \HttpBrowser::BROWSER_VERSION_MIN;
            $fingerprint = $this->services->get(FingerprintFactory::class)->getOne([$request]);

            if ($fingerprint !== null) {
                $selenium->seleniumOptions->fingerprint = $fingerprint->getFingerprint();
            }

            $selenium->disableImages();
            $selenium->useCache();
            $selenium->http->saveScreenshots = true;
            $selenium->http->start();
            $selenium->Start();

            try {
                $selenium->http->GetURL('https://www.flytap.com/en-us/login?redirectUrl=/en-us/client-area');
            } catch (\TimeOutException $e) {
                $this->logger->error("Exception: " . $e->getMessage());

                throw new CheckRetryNeededException(5, 0);
            }

            $selenium->waitForElement(WebDriverBy::xpath('//input[@id = "login-user-account"] | //h2[contains(text(), "Pardon Our Interruption ...")]
| //span[contains(.,"This page isn’t working")]'),
                10);

            $this->savePageToLogs($selenium);

            if ($this->http->FindSingleNode('//span[contains(.,"This page isn’t working")]')) {
                throw new CheckRetryNeededException(5, 0);
            }

            $loginInput = $selenium->waitForElement(WebDriverBy::id('login-user-account'), 5);
            $passwordInput = $selenium->waitForElement(WebDriverBy::id('login-pass-account'), 0);

            if (!$loginInput || !$passwordInput) {
                $this->savePageToLogs($selenium);

                return false;
            }
            $loginInput->sendKeys($this->AccountFields['Login']);
            $passwordInput->sendKeys($this->AccountFields['Pass']);

            $button = $selenium->waitForElement(WebDriverBy::xpath("//button[@id = 'login-save-account-submit']"), 3);

            if (!$button) {
                $this->savePageToLogs($selenium);

                return false;
            }
            $button->click();
            $sleep = 30;
            $startTime = time();

            while (((time() - $startTime) < $sleep) && !$login) {
                $this->logger->debug("(time() - \$startTime) = " . (time() - $startTime) . " < {$sleep}");

                if (
                    $selenium->waitForElement(WebDriverBy::xpath('//div[@class = "user-name"]'), 0, false)
                    || $selenium->waitForElement(WebDriverBy::xpath('
                            //div[@class = "user-name"]
                            | //button[contains(text(), "Logout")]
                            | //a[@class = "js-profile-name"]
                        '), 0)
                ) {
                    $login = true;
                    $this->savePageToLogs($selenium);

                    break;
                }

                if ($message = $selenium->waitForElement(WebDriverBy::xpath("
                        //div[@class = 'half-area']//li[@class = 'error-item']
                        | //h1[contains(text(), 'Consent Reconfirmation')]
                    "), 0)
                ) {
                    $error = $this->http->FindPreg("/^[\w.]*\:\:?\s*([^<]+)/ims", false, $message->getText());

                    if (!$error) {
                        $error = $message->getText();
                    }
                    $this->logger->error($error);

                    if (strpos($error,
                            "Sorry, but we are unable to validate the information provided at this time. Please try again later") !== false) {
                        throw new CheckRetryNeededException(5, 0);
                    }

                    break;
                }
                $this->savePageToLogs($selenium);
            }

            $this->savePageToLogs($selenium);

            if ($login) {
                if ($elem = $this->http->FindPreg('#class="js-profile-tp">(.+?)</div#')) {
                    $this->loyaltyMemberId = str_replace(' ', '', $elem);
                }
                $cookies = $selenium->driver->manage()->getCookies();

                foreach ($cookies as $cookie) {
                    $this->http->setCookie($cookie['name'], $cookie['value'], $cookie['domain'], $cookie['path'],
                        $cookie['expiry'] ?? null);
                }
            }

            if ($selenium->http->currentUrl() === 'https://www.flytap.com/en-us/login?redirectUrl=/en-us/client-area') {
                throw new CheckRetryNeededException(5, 0);
            }
            $this->logger->debug("[Current Selenium URL]: {$selenium->http->currentUrl()}");
        } catch (ScriptTimeoutException $e) {
            $this->logger->error("ScriptTimeoutException exception: " . $e->getMessage());
            // retries
            if (strpos($e->getMessage(), 'timeout: Timed out receiving message from renderer') !== false) {
                $retry = true;
            }
        } catch (StaleElementReferenceException $e) {
            $this->logger->error("StaleElementReferenceException exception: " . $e->getMessage());
            // retries
            if (stripos($e->getMessage(), 'Element not found in the cache') !== false) {
                $retry = true;
            }
        } finally {
            // close Selenium browser
            $selenium->http->cleanup();

            if ($retry && ConfigValue(CONFIG_SITE_STATE) != SITE_STATE_DEBUG) {
                $this->logger->debug("[attempt]: {$this->attempt}");

                throw new CheckRetryNeededException(5, 0);
            }
        }
        $this->getTime($startTimer);

        if (!is_null($error)) {
            $this->logger->error($error);

            throw new CheckException($error, ACCOUNT_PROVIDER_ERROR);
        }

        return $login && isset($this->loyaltyMemberId);
    }

    private function selenium2($fields): bool
    {
        $this->logger->notice(__METHOD__);
        $this->logger->debug('[Parse start date: ' . date('Y/m/d H:i:s') . ']');
        $startTimer = $this->getTime();
        $login = false;
        $retry = false;
        $selenium = clone $this;
        $this->selenium = true;
        $this->http->brotherBrowser($selenium->http);
        $error = null;

        try {
            $this->logger->notice("Running Selenium...");
            $selenium->UseSelenium();

            if (!isset($this->State["Resolution"])) {
                $resolutions = [
                    //                    [1152, 864],
                    //                    [1280, 720],
                    [1280, 768],
                    [1280, 800],
                    [1360, 768],
                    //[1920, 1080],
                ];
                $this->State["Resolution"] = $resolutions[array_rand($resolutions)];
            }

            $selenium->setScreenResolution($this->State["Resolution"]);
            /*            $selenium->useGoogleChrome(\SeleniumFinderRequest::CHROME_95);
                        $selenium->seleniumOptions->addHideSeleniumExtension = false;*/
//            $selenium->useGoogleChrome(\SeleniumFinderRequest::CHROME_99);
            $selenium->useChromium();

            $selenium->seleniumOptions->addHideSeleniumExtension = false;
            $request = FingerprintRequest::chrome();
            $request->browserVersionMin = \HttpBrowser::BROWSER_VERSION_MIN;
            $fingerprint = $this->services->get(FingerprintFactory::class)->getOne([$request]);

            if ($fingerprint !== null) {
                $selenium->seleniumOptions->fingerprint = $fingerprint->getFingerprint();
                $selenium->http->setUserAgent($fingerprint->getUseragent());
                $selenium->seleniumOptions->setResolution([$fingerprint->getScreenWidth(), $fingerprint->getScreenHeight()]);
            }

            //$selenium->disableImages();
            //$selenium->useCache();
            $selenium->usePacFile(false);

            $selenium->http->saveScreenshots = true;
//            $selenium->seleniumRequest->setHotPool(self::class . $this->AccountFields['Login']); // old
            $selenium->seleniumRequest->setHotSessionPool(self::class, $this->AccountFields['ProviderCode']);

            try {
                $selenium->http->start();
                $selenium->Start();
            } catch (\UnknownServerException | \TimeOutException | \ErrorException $e) {
                $this->markProxyAsInvalid();
                $this->logger->error("exception: " . $e->getMessage());

                throw new \CheckRetryNeededException(5, 0);
            }

            try {
                $selenium->http->GetURL("https://booking.flytap.com/booking");
            } catch (\TimeOutException | \UnknownServerException | \NoSuchDriverException $e) {
                $this->logger->error("Exception: " . $e->getMessage());

                throw new CheckRetryNeededException(5, 0);
            } catch (\UnexpectedAlertOpenException | \Facebook\WebDriver\Exception\UnknownErrorException $e) {
                $this->logger->error("Exception: " . $e->getMessage());

                try {
                    $selenium->http->GetURL("https://booking.flytap.com/booking");
                } catch (\UnexpectedAlertOpenException | \Facebook\WebDriver\Exception\UnknownErrorException  $e) {
                    throw new CheckRetryNeededException(5, 0);
                }
            }

            try {
                $this->savePageToLogs($selenium);
            } catch (\UnexpectedJavascriptException $e) {
                $this->logger->error("Exception: " . $e->getMessage());
            }

            if ($this->isBadProxy()) {
                $this->DebugInfo = "bad proxy";
                $this->markProxyAsInvalid();

                throw new \CheckRetryNeededException(5, 0);
            }
            $logo = $selenium->waitForElement(WebDriverBy::xpath("//a//*[@alt='TAP Air Portugal logo']|//*[@class='flight-actions__item flight-search']"), 30);

            if ($selenium->waitForElement(\WebDriverBy::xpath("//p[contains(normalize-space(), 'Estamos fazendo melhorias em nosso mecanismo de reservas. Pedimos desculpas pela inconveniência.')]"), 0)) {
                throw new \CheckException('Estamos fazendo melhorias em nosso mecanismo de reservas. Pedimos desculpas pela inconveniência.', ACCOUNT_PROVIDER_ERROR);
            }

            if ($selenium->waitForElement(\WebDriverBy::xpath("//h1[contains(text(),'Voltaremos em breve')]"), 0)) {
                throw new \CheckException('Technical works.', ACCOUNT_PROVIDER_ERROR);
            }

            try {
                $this->savePageToLogs($selenium);
            } catch (\NoSuchDriverException $e) {
                $this->logger->error("exception: " . $e->getMessage());

                if (!$logo) {
                    throw new CheckRetryNeededException(5, 0);
                }
            }

            if (!$logo) {
                throw new \CheckRetryNeededException(5, 0);
            }

            try {
                $script = "return sessionStorage.getItem('userData');";
                $this->savePageToLogs($selenium);
                $this->logger->debug("[run script]");
                $this->logger->debug($script, ['pre' => true]);
                $userData = $selenium->driver->executeScript($script);
            } catch (\UnknownServerException $e) {
                $this->logger->error('UnknownServerException: ' . $e->getMessage());

                throw new CheckRetryNeededException(5, 0);
            }

//            if ($this->http->FindSingleNode('(//div[contains(@class,"header-fallback__user")][normalize-space()!="Login"])[1]')) {
            if (!empty($userData)) {
                $this->logger->debug("logged in");
                $login = true;
                $this->savePageToLogs($selenium);
            } else {
                $this->savePageToLogs($selenium);

                try {
                    $login = $this->auth($selenium);
                } catch (\NoSuchDriverException $e) {
                    $this->logger->error('NoSuchDriverException: ' . $e->getMessage());

                    throw new CheckRetryNeededException(5, 0);
                }

                if (!isset($login)) {
                    return false;
                }
            }

            if ($login) {
                $script = "return sessionStorage.getItem('userData');";
                $this->logger->debug("[run script]");
                $this->logger->debug($script, ['pre' => true]);
                $userData = $selenium->driver->executeScript($script);

                if (!empty($userData)) {
                    $data = $this->http->JsonLog($userData, 1);

                    if ($data) {
                        if (isset($data->ffCarrier, $data->ffNumber)) {
                            $this->loyaltyMemberId = $data->ffCarrier . $data->ffNumber;
                        }

                        if (isset($data->flyTapLogin)) {
                            $this->token = $data->flyTapLogin;
                        }
                    }
                }
                $script = "return sessionStorage.getItem('token');";
                $this->logger->debug("[run script]");
                $this->logger->debug($script, ['pre' => true]);
                $this->sessionToken = $selenium->driver->executeScript($script);
                $this->sessionToken = trim($this->sessionToken, '"');
                $this->logger->debug('token ' . $this->sessionToken);

                $cookies = $selenium->driver->manage()->getCookies();

                foreach ($cookies as $cookie) {
                    $this->http->setCookie($cookie['name'], $cookie['value'], $cookie['domain'], $cookie['path'],
                        $cookie['expiry'] ?? null);
                }

                $this->savePageToLogs($selenium);

                try {
                    $this->hasTapOnly = $this->checkRouteData($selenium, $fields);

                    if ($this->noRoute) {
                        $this->logger->notice('Data ok, saving session');
                        $selenium->keepSession(true);

                        return true;
                    }

//                    $originData = $selenium->driver->executeScript($tt =
//                        '
//                    var xhttp = new XMLHttpRequest();
//                    xhttp.open("GET", "https://booking.flytap.com/bfm/rest/search/pax/types?market=US&journeyList=' . $fields['DepCode'] . '&journeyList=' . $fields['ArrCode'] . '&tripType=O", false);
//                    xhttp.setRequestHeader("Accept", "application/json, text/plain, */*");
//                    xhttp.setRequestHeader("Authorization","Bearer ' . $this->sessionToken . '");
//                    xhttp.setRequestHeader("Origin","https://booking.flytap.com");
//                    xhttp.setRequestHeader("Referer","https://booking.flytap.com/booking/flights");
//                    xhttp.setRequestHeader("User-Agent","' . $selenium->http->getDefaultHeader("User-Agent") . '");
//
//                    var data = JSON.stringify({"tripType":"O","market":"US","language":"en-us","airlineIds":["TP"],"payWithMiles":true});
//                    xhttp.onreadystatechange = function() {
//                        localStorage.setItem("retDataCode",this.status);
//                        if (this.readyState == 4 && this.status == 200) {
//                            localStorage.setItem("retData",this.responseText);
//                        }
//                    };
//                    xhttp.send(data);
//                    v = JSON.stringify({retData: localStorage.getItem("retData"), retDataCode: localStorage.getItem("retDataCode")});
//                    return v;
//        '
//                    );
//                    $this->logger->debug($tt);
//                    $data = $this->http->JsonLog($originData, 1, true);

                    if (isset($this->tapRoute, $this->starAllianceRoute)) {
                        if ($this->tapRoute) {
                            $this->dataResponseOnlyTap = $this->tryAjax($selenium, $fields, false);
                        }

                        if ($this->starAllianceRoute) {
                            $this->dataResponseAlliance = $this->tryAjax($selenium, $fields);
                        }
                    } else {
                        $this->dataResponseAlliance = $this->tryAjax($selenium, $fields);

                        if ($this->hasTapOnly) {
                            $this->dataResponseOnlyTap = $this->tryAjax($selenium, $fields, false);
                        }
                    }
                } catch (\UnexpectedJavascriptException $e) {
                    $this->logger->error($e->getMessage());

                    if (!isset($this->bodyResponseOnlyTap)) {
                        $this->dataResponseOnlyTap = null;
                        $this->bodyResponseOnlyTap = null;
                    }

                    if (!isset($this->bodyResponseAlliance)) {
                        $this->dataResponseAlliance = null;
                        $this->bodyResponseAlliance = null;
                    }
                } catch (\NoSuchWindowException | \UnknownServerException $e) {
                    $this->logger->error($e->getMessage());

                    throw new \CheckRetryNeededException(5, 0);
                }

                if (isset($this->dataResponseAlliance) && (!$this->hasTapOnly || isset($this->dataResponseOnlyTap))) {
                    $this->logger->notice('Data ok, saving session');
                    $selenium->keepSession(true);
                    $dataOk = true;
                }
            }

//            if (!$login && $selenium->waitForElement(\WebDriverBy::xpath("//h2[@id='modal__title'][normalize-space()='Login']"), 30)) {
//                throw new CheckException('No login form', ACCOUNT_PROVIDER_ERROR);
//            }

            try {
                $this->logger->debug("[Current Selenium URL]: {$selenium->http->currentUrl()}");
            } catch (\NoSuchDriverException $e) {
                $this->logger->error($e->getMessage());

                if (!isset($dataOk)) {
                    throw new CheckRetryNeededException(5, 0);
                }
            }
        } catch (ScriptTimeoutException $e) {
            $this->logger->error("ScriptTimeoutException exception: " . $e->getMessage());
            // retries
            if (strpos($e->getMessage(), 'timeout: Timed out receiving message from renderer') !== false) {
                $retry = true;
            }
        } catch (StaleElementReferenceException $e) {
            $this->logger->error("StaleElementReferenceException exception: " . $e->getMessage());
            // retries
            if (stripos($e->getMessage(), 'Element not found in the cache') !== false) {
                $retry = true;
            }
        } catch (\UnknownServerException $e) {
            $this->logger->error("UnknownServerException: " . $e->getMessage());
            $this->logger->debug("Need to change ff version");
            $retry = true;
        } catch (\WebDriverCurlException | \WebDriverException $e) {
            $this->logger->error($e->getMessage());
            $retry = true;
        } finally {
            // close Selenium browser
            $selenium->http->cleanup();

            if ($retry && ConfigValue(CONFIG_SITE_STATE) != SITE_STATE_DEBUG) {
                $this->logger->debug("[attempt]: {$this->attempt}");

                throw new CheckRetryNeededException(5, 0);
            }
        }
        $this->getTime($startTimer);

        if (!is_null($error)) {
            $this->logger->error($error);

            throw new CheckException($error, ACCOUNT_PROVIDER_ERROR);
        }

        return $login;
        // if old version with  queryRewardFlights
//        return $login && isset($this->loyaltyMemberId);
    }

    private function auth($selenium): ?bool
    {
        $this->logger->notice(__METHOD__);
        $login = false;

        if ($btn = $selenium->waitForElement(\WebDriverBy::xpath("//a[normalize-space()='Login' or normalize-space()='header.text.logIn']"),
            25)) {
            if ($selenium->waitForElement(\WebDriverBy::xpath("//a[normalize-space()='header.text.logIn']"), 0)) {
                try {
                    $selenium->http->GetURL("https://booking.flytap.com/booking");
                    $this->savePageToLogs($selenium);

                    // 502 Bad Gateway
                    if ($this->http->FindSingleNode('//h1[contains(text(), "502 Bad Gateway")]')) {
                        if ($this->attempt == 0) {
                            throw new CheckRetryNeededException(5, 0);
                        }

                        throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
                    }

                    if ($this->isBadProxy()) {
                        $this->markProxyAsInvalid();

                        throw new CheckRetryNeededException(5, 0);
                    }
                } catch (\TimeOutException $e) {
                    $this->logger->error("Exception: " . $e->getMessage());

                    throw new CheckRetryNeededException(5, 0);
                }
            }

            if ($selenium->waitForElement(\WebDriverBy::xpath("//a[normalize-space()='header.text.logIn']"), 10)) {
                $this->logger->error('header.text.logIn');

                throw new CheckRetryNeededException(5, 0);
            }
        }
        $accept = $selenium->waitForElement(\WebDriverBy::id('onetrust-accept-btn-handler'), 0);

        if ($accept) {
            $this->logger->debug("click accept");
            $accept->click();
        }

        $this->waitFor(function () use ($selenium) {
            return !$selenium->waitForElement(\WebDriverBy::id('onetrust-accept-btn-handler'), 0);
        }, 20);

        if (!$btn = $selenium->waitForElement(\WebDriverBy::xpath("//a[normalize-space()='Login' or normalize-space()='header.text.logIn']"), 0)) {
            try {
                $this->logger->debug("[run js]: document.querySelector('#pay-miles').click();");
                $selenium->driver->executeScript("document.querySelector('#pay-miles').click();");
            } catch (\UnexpectedJavascriptException $e) {
                $this->logger->error('UnexpectedJavascriptException: ' . $e->getMessage());

                throw new \CheckRetryNeededException(5, 0);
            }
        }

        if ($btn = $selenium->waitForElement(\WebDriverBy::xpath("//a[normalize-space()='Login']"), 5)) {
            $selenium->waitForElement(\WebDriverBy::xpath("//a[normalize-space()='Login']"), 0);
            $this->logger->debug("login click");

            try {
                $btn->click();
            } catch (\UnrecognizedExceptionException $e) {
                $this->logger->error('UnrecognizedExceptionException: ' . $e->getMessage());
                $this->savePageToLogs($selenium);

                throw new \CheckRetryNeededException(5, 0);
            }
        }

        $this->savePageToLogs($selenium);
        $loginInput = $selenium->waitForElement(WebDriverBy::id('login'), 10);
        $passwordInput = $selenium->waitForElement(WebDriverBy::id('login-password'), 0);
        $button = $selenium->waitForElement(WebDriverBy::xpath("//button[@type='submit'][normalize-space()='Login' or normalize-space()='header.text.logIn']"), 0);

        if (!$loginInput || !$passwordInput || !$button) {
            $this->savePageToLogs($selenium);
            $this->logger->error('login form not load');

            return null;
        }

        if (!isset($this->AccountFields['Login']) || !isset($this->AccountFields['Pass'])) {
            throw new CheckRetryNeededException(5, 0);
        }

        $loginInput->sendKeys($this->AccountFields['Login']);
        $passwordInput->sendKeys($this->AccountFields['Pass']);

        if (!$button) {
            $this->savePageToLogs($selenium);

            return null;
        }
        $button->click();

        if ($selenium->waitForElement(\WebDriverBy::xpath("//span[contains(normalize-space(), 'Login temporariamente indisponível. Reservas online e check-in a funcionar corretamente.')]"), 40)) {
            $counter = \Cache::getInstance()->get('ra_tapportugal_failed_auth');

            if (!$counter) {
                $counter = 0;
            }
            $counter++;
            \Cache::getInstance()->set('ra_tapportugal_failed_auth', $counter, 15 * 60); // 15min

            throw new \CheckException('Login temporariamente indisponível.', ACCOUNT_PROVIDER_ERROR);
        }
        $this->savePageToLogs($selenium);
        $sleep = 25;
        $startTime = time();

        $scriptUserData = "return sessionStorage.getItem('userData');";
        $this->logger->debug("[script scriptUserData]");
        $this->logger->debug($scriptUserData, ['pre' => true]);

        while (((time() - $startTime) < $sleep) && !$login) {
            $this->logger->debug("(time() - \$startTime) = " . (time() - $startTime) . " < {$sleep}");

            if ($selenium->waitForElement(WebDriverBy::xpath('(//div[contains(@class,"header-fallback__user")][normalize-space()!="Login"])[1]'), 0, false)) {
                $login = true;
                $this->savePageToLogs($selenium);

                break;
            }
            $this->logger->debug("[run script scriptUserData]");
            $userData = $selenium->driver->executeScript($scriptUserData);

            if (!empty($userData)) {
                $this->logger->debug("logged in");
                $login = true;
                $this->savePageToLogs($selenium);

                break;
            }

            if ($message = $selenium->waitForElement(WebDriverBy::xpath("//h5[contains(.,'Login Error')]/following-sibling::div[1][contains(.,'Algo correu mal. Tente mais tarde.')]"), 0)) {
                if ($this->attempt >= 3 || (time() - $this->requestDateTime) > 90) {
                    throw new \CheckException($message->getText(), ACCOUNT_PROVIDER_ERROR);
                }

                throw new CheckRetryNeededException(5, 0);
            }

            if ($message = $selenium->waitForElement(WebDriverBy::xpath("//div[contains(@class,'form-errors')]"), 0)
            ) {
                $error = $this->http->FindPreg("/^[\w.]*\:\:?\s*([^<]+)/ims", false, $message->getText());

                if (!$error) {
                    $error = $message->getText();
                }
                $this->logger->error($error);

                if (strpos($error,
                        "Sorry, it is currently not possible to validate the information provided. Please try again later.") !== false
                    || strpos($error,
                        "E-mail ou número de cliente (TP): Campo obrigatório") !== false
                    || strpos($error,
                        "Lamentamos, mas de momento não é possível validar as informações fornecidas. Por favor, tente novamente mais tarde.") !== false
                    || strpos($error, "header.login.errorLoginRequiredOrInvalid") !== false
                    || strpos($error, "header.login.error.userUnknown") !== false
                    || strpos($error, "O login do utilizador que inseriu não é válido") !== false
                    || strpos($error, "Palavra-passe: campo obrigatório.") !== false
                ) {
                    throw new CheckRetryNeededException(5, 0);
                }
                $this->sendNotification('check msg // ZM');

                break;
            }

            if ($message = $selenium->waitForElement(WebDriverBy::xpath("//span[contains(.,'O estado da sua conta não lhe permite aceder a este link. Por favor, contacte o serviço de apoio Miles & Go')]"), 0)
            ) {
                $msg = $message->getText();
                $this->logger->error($msg);

                if ($this->attempt >= 3 || (time() - $this->requestDateTime) > 90) {
                    throw new \CheckException($msg, ACCOUNT_PROVIDER_ERROR);
                }

                throw new CheckRetryNeededException(5, 0);
            }

            if ($message = $selenium->waitForElement(WebDriverBy::xpath("//*[contains(normalize-space(text()),'Login temporariamente indisponivel. Reservas online"), 0)) {
                $msg = $message->getText();
                $this->logger->error($msg);
                $this->sendNotification('check msg // ZM');

                if ($this->attempt >= 3 || (time() - $this->requestDateTime) > 90) {
                    throw new \CheckException($msg, ACCOUNT_PROVIDER_ERROR);
                }

                throw new CheckRetryNeededException(5, 0);
            }
            $this->savePageToLogs($selenium);
        }

        if (!$login) {
            $script = "return sessionStorage.getItem('userData');";
            $this->logger->debug("[run script]");
            $this->logger->debug($script, ['pre' => true]);
            $userData = $selenium->driver->executeScript($script);

            if (!empty($userData)) {
                $this->logger->debug("logged in");
                $login = true;
                $this->savePageToLogs($selenium);
            }
        }

        try {
            $selenium->driver->executeScript("
            if (!document.querySelector('#pay-miles').checked)
                document.querySelector('#pay-miles').click();
        ");
        } catch (\UnexpectedJavascriptException $e) {
            $this->logger->error('UnexpectedJavascriptException: ' . $e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        }
        $this->savePageToLogs($selenium);

        return $login;
    }

    private function tryAjax($selenium, $fields, ?bool $alliance = true)
    {
        $this->logger->notice(__METHOD__);
        //sleep(5);
        $dateStr = date('dmY', $fields['DepDate']);
        $dateStrCheck = date('Y-m-d', $fields['DepDate']);

        if (empty($this->sessionToken)) {
            return null;
        }

        if ($alliance) {
            $this->logger->info('tap+alliance');
            sleep(2);
        } else {
            $this->logger->info('tap only');
        }

        $returnData = $selenium->driver->executeScript($tt =
            '
                    var xhttp = new XMLHttpRequest();
                    xhttp.withCredentials = true;
                    xhttp.open("POST", "https://booking.flytap.com/bfm/rest/booking/availability/search?payWithMiles=true&starAlliance=' . var_export($alliance, true) . '", false);
                    xhttp.setRequestHeader("Content-type", "application/json");
                    xhttp.setRequestHeader("Accept", "application/json, text/plain, */*");
                    xhttp.setRequestHeader("Authorization", "Bearer ' . $this->sessionToken . '");
                    xhttp.setRequestHeader("Connection", "keep-alive");
                    xhttp.setRequestHeader("Accept-Encoding", "gzip, deflate, br");
                    xhttp.setRequestHeader("Origin", "https://booking.flytap.com");
                    xhttp.setRequestHeader("Sec-Fetch-Dest", "empty");
                    xhttp.setRequestHeader("Sec-Fetch-Mode", "cors");
                    xhttp.setRequestHeader("Sec-Fetch-Site", "same-origin");
                    xhttp.setRequestHeader("Referer", "https://booking.flytap.com/booking/flights");

        
                    var data = JSON.stringify({"adt":' . $fields['Adults'] . ',"airlineId":"TP","c14":0,"cabinClass":"E","chd":0,"departureDate":["' . $dateStr . '"],"destination":["' . $fields['ArrCode'] . '"],"inf":0,"language":"en-us","market":"US","origin":["' . $fields['DepCode'] . '"],"passengers":{"ADT":' . $fields['Adults'] . ',"YTH":0,"CHD":0,"INF":0},"returnDate":"' . $dateStr . '","tripType":"O","validTripType":true,"payWithMiles":true,"starAlliance":' . var_export($alliance, true) . ',"yth":0});
                    var responseText = null;
                    xhttp.onreadystatechange = function() {
                        responseStatus = this.status;
                        if (this.readyState == 4 && this.status == 200) {
                            responseText = this.responseText;
                        }
                    };
                    xhttp.send(data);
                    return responseText;
        '
        );
        $this->logger->debug($tt, ['pre' => true]);

        if (strpos($returnData, '"desc":"Invalid FlightSearch data') !== false) {
            // перелеты могут быть. ретрай не помогает вообще, только полный рестарт
            throw new CheckRetryNeededException(5, 10);
        }

        if (empty($returnData)
            || (
                strpos($returnData, '"errors":[{"code":') === false
                && strpos($returnData, 'departureDate":"' . $dateStrCheck) === false
            )
            || $this->http->FindPreg('/"desc":"Read timed out/', false, $returnData)
            || strpos($returnData, '"desc":"Past date/time not allowed"') !== false
            || strpos($returnData, '"desc":"Bad value (coded) - timeDetails"') !== false
            || strpos($returnData, '"code":"500","type":"ERROR"') !== false
            || strpos($returnData, '"desc":"11|Session|"') !== false
            || strpos($returnData, '"desc":"Server is busy, please try again in a few minutes') !== false
            || $this->http->FindPregAll('/<body>Bad Request<\/body>/', $returnData, PREG_PATTERN_ORDER, false, false)
            || strpos($returnData, '"desc":"42|Application|Too many opened conversations. Please close them and try again') !== false
        ) {
            sleep(2);

            if ($this->http->FindPreg('/"desc":"Read timed out/', false, $returnData)) {
                $this->sendNotification("check retry on error // ZM");
            }

            $this->savePageToLogs($selenium);
            // helped
            $this->logger->debug($tt, ['pre' => true]);
            $returnData = $selenium->driver->executeScript($tt);
        }

        if (
            !empty($returnData)
            && strpos($returnData, '"errors":[{"code":') === false
            && strpos($returnData, 'departureDate":"' . $dateStrCheck) === false
        ) {
            $this->logger->error('wrong response/departureDate');

            throw new CheckRetryNeededException(5, 10);
        }
        // TODO: above?
        // Transaction unable to process : TECH INIT   ||   Transaction unable to process : AVL
        if ($this->http->FindPreg('/"desc":"Transaction unable to process/', false, $returnData)) {
            sleep(2);

            $this->savePageToLogs($selenium);

            // helped
            $this->logger->debug($tt, ['pre' => true]);
            $returnData = $selenium->driver->executeScript($tt);
        }

        if ($this->http->FindPregAll('/<body>Bad Request<\/body>/', $returnData, PREG_PATTERN_ORDER, false, false)
            || $this->http->FindPreg('/"desc":"\s*11\|Session\|"/', false, $returnData)
            || $this->http->FindPreg('/"desc":"Server is busy, please try again in a few minutes/', false, $returnData)
            || strpos($returnData, '"desc":"Invalid FlightSearch data') !== false
        ) {
            throw new CheckRetryNeededException(5, 10);
        }

        if ($alliance) {
            $this->bodyResponseAlliance = $returnData;
        } else {
            $this->bodyResponseOnlyTap = $returnData;
        }

        return $this->http->JsonLog($returnData);
    }

    private function fillAirport($selenium, $id, $val)
    {
        $inp = $selenium->waitForElement(\WebDriverBy::id($id), 0);

        if ($inp) {
            $inp->click();
            $inp->clear();
            $inp->sendKeys($val);
            $inp->sendKeys(\WebDriverKeys::TAB);
            sleep(1);
            $text = $selenium->driver->executeScript("return document.querySelector('#flight-search-from').previousSibling.previousSibling.innerText");

            if (!preg_match("/^[A-Z]{3}$/", trim($text))) {
                $text = $selenium->driver->executeScript("return document.querySelector('#flight-search-from').previousSibling.innerText");
            }
            $this->logger->error($text);
        }
    }

    private function checkRouteData($selenium, $fields)
    {
        $this->logger->notice(__METHOD__);

        if (empty($this->sessionToken)) {
            return null;
        }

        $origins = \Cache::getInstance()->get('ra_tapportugal_origins');

        if (!is_array($origins)) {
            $originData = $selenium->driver->executeScript($tt =
                '
                    var xhttp = new XMLHttpRequest();
                    xhttp.open("POST", "https://booking.flytap.com/bfm/rest/journey/origin/search", false);
                    xhttp.setRequestHeader("Content-type", "application/json");
                    xhttp.setRequestHeader("Accept", "application/json, text/plain, */*");
                    xhttp.setRequestHeader("Authorization","Bearer ' . $this->sessionToken . '");
                    xhttp.setRequestHeader("Origin","https://booking.flytap.com");
                    xhttp.setRequestHeader("Referer","https://booking.flytap.com/booking/flights");
        
                    var data = JSON.stringify({"tripType":"O","market":"US","language":"en-us","airlineIds":["TP"],"payWithMiles":true});
                    xhttp.onreadystatechange = function() {
                        if (this.readyState == 4 && this.status == 200) {
                            localStorage.setItem("retData",this.responseText);
                        }
                    };
                    xhttp.send(data);
                    v = localStorage.getItem("retData");
                    return v;
        '
            );
            $this->logger->debug($tt);
            $data = $this->http->JsonLog($originData, 1, true);

            $origins = [];

            if (isset($data['data']['origins'])) {
                $origins = array_map(function ($d) {
                    return $d['airport'];
                }, $data['data']['origins']);

                if (!empty($origins)) {
                    \Cache::getInstance()->set('ra_tapportugal_origins', $origins, 24 * 60 * 60);
                }
            }
        }

        if (is_array($origins) && !empty($origins) && !in_array($fields['DepCode'], $origins)) {
            $this->SetWarning('No flights from ' . $fields['DepCode']);
            $this->noRoute = true;

            return false;
        }

        $returnData = $selenium->driver->executeScript($tt =
            '
                    var xhttp = new XMLHttpRequest();
                    xhttp.open("POST", "https://booking.flytap.com/bfm/rest/journey/destination/search", false);
                    xhttp.setRequestHeader("Content-type", "application/json");
                    xhttp.setRequestHeader("Accept", "application/json, text/plain, */*");
                    xhttp.setRequestHeader("Authorization","Bearer ' . $this->sessionToken . '");
                    xhttp.setRequestHeader("Origin","https://booking.flytap.com");
                    xhttp.setRequestHeader("Referer","https://booking.flytap.com/booking/flights");
        
                    var data = JSON.stringify({"tripType":"O","market":"US","language":"en-us","airlineIds":["TP"],"payWithMiles":true,"origin":"' . $fields['DepCode'] . '"});
                    xhttp.onreadystatechange = function() {
                        if (this.readyState == 4 && this.status == 200) {
                            localStorage.setItem("retData",this.responseText);
                        }
                    };
                    xhttp.send(data);
                    v = localStorage.getItem("retData");
                    return v;
        '
        );
        $this->logger->debug($tt);

        if (empty($returnData) || strpos('"code":"500","type":"ERROR"', $returnData) !== false) {
            sleep(2);
            // helped
            $returnData = $selenium->driver->executeScript($tt);

            if (strpos('"code":"500","type":"ERROR"', $returnData) !== false) {
                $returnData = null;
            }
        }
        $data = $this->http->JsonLog($returnData, 1, true);
        $noFlight = true;
        $flight = null;

        if (isset($data['data']['destinations'])) {
            foreach ($data['data']['destinations'] as $destination) {
                if ($destination['airport'] === $fields['ArrCode']) {
                    $flight = $destination;
                    $noFlight = false;
                    $this->tapRoute = $destination['tapRoute'];
                    $this->starAllianceRoute = $destination['starAllianceRoute'];

                    break;
                }
            }

            if ($noFlight) {
                $this->SetWarning('No flights from ' . $fields['DepCode'] . ' to ' . $fields['ArrCode']);
                $this->noRoute = true;

                return false;
            }

            if ($flight && array_key_exists('tapRoute', $flight)) {
                return $flight['tapRoute'];
            }
        }

        return true;
    }

    private function savePageToLogs($selenium)
    {
        $this->logger->notice(__METHOD__);
        // save page to logs
        try {
            $selenium->http->SaveResponse();
            // save page to logs
            $this->http->SetBody($selenium->driver->executeScript('return document.documentElement.innerHTML'));
            $this->http->SaveResponse();
        } catch (\WebDriverException $e) {
            throw new \CheckRetryNeededException(5, 0);
        }
    }

    private function isBadProxy()
    {
        return $this->http->FindSingleNode("//h1[contains(., 'This site can’t be reached')]")
            || $this->http->FindSingleNode("//h1[normalize-space()='Access Denied']")
            || $this->http->FindSingleNode("//span[contains(text(), 'This site can’t be reached')]")
            || $this->http->FindSingleNode("//span[contains(text(), 'This page isn’t working')]")
            || $this->http->FindSingleNode("//p[contains(text(), 'There is something wrong with the proxy server, or the address is incorrect.')]");
    }

    /**
     * TODO: All bullshit, don't use.
     */
    /*private function validRoute($fields): bool
    {
        $this->logger->notice(__METHOD__);
        $this->http->GetURL("https://book.flytap.com/air/WebServices/Schedule/AirportCombination.asmx/CheckEligibleRoute", [
            'origin' => $fields['DepCode'],
            'destination' => $fields['ArrCode'],
        ]);
        $data = $this->http->JsonLog();
        return $data->domestic === true;
    }*/

    /*private function validRoute($fields): bool
    {
        $this->logger->notice(__METHOD__);
        $airports = \Cache::getInstance()->get('ra_tapportugal_airports');
        if (!$airports || !is_array($airports)) {
            $this->http->GetURL("https://www.flytap.com/api/general/masterdata?sc_mark=US&sc_lang=en-US", [
                'Connection' => 'keep-alive',
                'Cache-Control' => 'no-cache',
                'Accept' => '* / *',
            ], 10);
            $airports = $this->http->JsonLog(null, 0);
            if (!isset($airports->Airports)) {
                throw new \CheckException("Something went wrong", ACCOUNT_ENGINE_ERROR);
            }
            $airports = $airports->Airports;
            if (!empty($airports)) {
                \Cache::getInstance()->set('ra_tapportugal_airports', $airports, 60 * 60 * 24);
            } else {
                $this->sendNotification("RA check airports // MI");
            }
        }
        if ($airports && is_array($airports)) {
            foreach ($airports as $airport) {
                if ($airport->IATACode == $fields['DepCode']) {
                    return in_array($fields['ArrCode'], $airport->Connections);
                }
            }
        }
        return false;
    }*/
}
