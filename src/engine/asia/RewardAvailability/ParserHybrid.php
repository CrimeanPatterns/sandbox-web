<?php

namespace AwardWallet\Engine\asia\RewardAvailability;

use AwardWallet\Engine\ProxyList;
use AwardWallet\Engine\Settings;
use SeleniumCheckerHelper;
use WebDriverBy;

//    CREDENTIALS FOR TEST
//    [
//        'Login' => '1723195329',
//        'Pass' => '!z#mil#1980AM',
//    }

class ParserHybrid extends \TAccountCheckerAsia
{
    use SeleniumCheckerHelper;
    use \PriceTools;
    use ProxyList;

    protected const Queue_CACHE_KEY = 'asia_Queue';

    public $isRewardAvailability = true;
    private $isRestartParseRA = false;
    private $warning;

    private $selenium;
    private $cookiesRefreched = false;
    private $accessDenied = false;

    public function InitBrowser()
    {
        \TAccountChecker::InitBrowser();
        $this->KeepState = true;
        $this->http->setDefaultHeader("Accept-Encoding", "gzip, deflate, br");
        $this->http->setHttp2(true);
        $this->http->disableOriginHeader();

        if ($this->attempt > 1) {
            $this->setProxyNetNut(null, 'fr');
        } elseif ($this->attempt === 1) {
            $this->setProxyDOP();
        } else {
//            $this->setProxyBrightData(null, Settings::RA_ZONE_STATIC, 'kr');
            $this->setProxyBrightData(null, Settings::RA_ZONE_STATIC);
        }

        $userAgentKey = "User-Agent";

        if (!isset($this->State[$userAgentKey]) || $this->attempt > 2) {
            $this->http->setRandomUserAgent(10);
            $agent = $this->http->getDefaultHeader("User-Agent");

            if (!empty($agent)) {
                $this->State[$userAgentKey] = $agent;
            }
        } else {
            $this->http->setUserAgent($this->State[$userAgentKey]);
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

        $debugMode = $this->AccountFields['DebugState'] ?? false;

        if (!$debugMode) {
            $this->logger->error('parser off');

            throw new \CheckException('something went wrong', ACCOUNT_ENGINE_ERROR);
        }

        try {
            if (!$this->LoginCathay()) {
                throw new \CheckRetryNeededException(5, 3);
            }
            $domain = 'cathaypacific';

            $depDate = date('Ymd', $fields['DepDate']);

            $asiaCabinCode = $this->getCabin($fields['Cabin'], true);

            $this->newSchema($asiaCabinCode, $fields, $depDate);

            if ($this->ErrorMessage !== "Unknown error") {
                return ['routes' => []];
            }
        } finally {
            if (isset($this->selenium)) {
                $this->selenium->http->cleanup();
            }
        }

//        $this->oldSchema($asiaCabinCode, $fields, $depDate, $domain);

        $pageBom = $this->http->FindPreg("/pageBom = (\{.+?\}); pageTicket/");
        $this->logger->info("[pageBom]");
        $pageBom = $this->http->JsonLog($pageBom, 6, false, 'flights');

        if (!$pageBom) {
            throw new \CheckRetryNeededException(5, 0);
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

//        $requestParams = null;

        if (isset($pageBom->modelObject->availabilities) && !isset($pageBom->modelObject->availabilities->upsell)) {
            $this->SetWarning('There are no flights available for the dates you have selected.');

            return ['routes' => []];
        }
//        $url = $this->http->FindSingleNode("//script[@src='common/js/constants/constant.js']/@src");
        $url = $this->http->FindPreg('/src="([^"]+common\/js\/constants\/constant\.js)"/');

        if ($url && strpos($url, 'https://book') === false) {
            $url = "https://book.cathaypacific.com/" . $url;
        //$this->http->NormalizeURL($url);
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

        // getCabinFromRBD
        // updateMilesInfoParams
        $this->http->RetryCount = 2;

        return [
            "routes" => $this->parseRewardFlights($pageBom, $requestParams, $config,
                $fields, $domain),
        ];
    }

    public function LoginCathay()
    {
        $this->http->removeCookies();
        $this->http->FilterHTML = false;
        $loginURL = "https://www.cathaypacific.com/cx/en_US/sign-in.html?switch=Y";

        $this->http->GetURL($loginURL);

        if ($this->http->Response['code'] == 403) {
            $userAgentKey = "User-Agent";
            $this->http->setRandomUserAgent(15);
            $agent = $this->http->getDefaultHeader("User-Agent");

            if (!empty($agent)) {
                $this->State[$userAgentKey] = $agent;
            }

            $this->http->GetURL($loginURL);

            if ($this->http->Response['code'] == 403) {
                $this->http->SetProxy($this->proxyReCaptcha());
                $this->http->setRandomUserAgent(10);
                $agent = $this->http->getDefaultHeader("User-Agent");

                if (!empty($agent)) {
                    $this->State[$userAgentKey] = $agent;
                }
                $this->http->GetURL($loginURL);

                if ($this->http->Response['code'] == 403) {
                    $this->sendNotification("403, debug // RR");
                }
            }
        }// if ($this->http->Response['code'] == 403)

        $abck = \Cache::getInstance()->get(self::ABCK_CACHE_KEY);
        $bmsz = \Cache::getInstance()->get(self::BMSZ_CACHE_KEY);
        $QueueITToken = \Cache::getInstance()->get(self::Queue_CACHE_KEY);
        $this->logger->debug("_abck from cache: {$abck}");
        $this->logger->debug("bm_sz from cache: {$bmsz}");
        $this->logger->debug("QueueITAccepted from cache: " . var_export($QueueITToken, true), ['pre' => true]);

        if (!$abck || !$QueueITToken || !$bmsz || $this->attempt > 0) {
            $this->getSensorDataFromSelenium();

            if (!$this->accessDenied) {
                return true;
            }

            if (!$this->cookiesRefreched) {
                throw new \CheckRetryNeededException(5, 0);
            }

            $this->http->removeCookies();
            $this->http->GetURL($loginURL);

            if ($this->http->Response['code'] == 403) {
                throw new \CheckRetryNeededException(3, 3);
            }
            $abck = \Cache::getInstance()->get(self::ABCK_CACHE_KEY);
            $bmsz = \Cache::getInstance()->get(self::BMSZ_CACHE_KEY);
            $QueueITToken = \Cache::getInstance()->get(self::Queue_CACHE_KEY);
        }

        $this->http->setCookie('_abck', $abck, ".cathaypacific.com");
        $this->http->setCookie('bm_sz', $bmsz, ".cathaypacific.com");

        if (!$this->http->ParseForm(null, "//div[contains(@class, 'signIn')]//form")) {
            return $this->checkErrors();
        }

        $this->http->FilterHTML = true;
        $data = [
            "response_type" => "code",
            "scope"         => "openid",
            "client_id"     => "20b10efd-dace-4053-95ac-0fcc39d43d84",
            "redirect_uri"  => "https://api.cathaypacific.com/mpo-login/v2/login/createSessionAndRedirect",
            "username"      => $this->AccountFields['Login'],
            "password"      => $this->AccountFields['Pass'],
            "login_url"     => "https://www.cathaypacific.com/cx/en_GB/login.html?source=masterLoginbox",
            "target_url"    => "https://www.cathaypacific.com:443/cx/en_GB.html",
            "login_type"    => filter_var($this->AccountFields['Login'], FILTER_VALIDATE_EMAIL) === false ? "memberIdOrUsername" : "email",
        ];
        $this->http->PostURL("https://api.cathaypacific.com/mlc2/authorize", $data);

        if ($this->http->currentUrl() === 'https://www.cathaypacific.com/cx/en_GB/sign-in.html?source=masterLoginbox&error=2009') {
            throw new \CheckException('Your member account is temporarily locked after too many unsuccessful login attempts. You can reset your password by confirming your personal information or contact our Customer Care for assistance. [Error code: 2009]', ACCOUNT_LOCKOUT);
        }

        if ($this->http->currentUrl() !== 'https://www.cathaypacific.com:443/cx/en_GB.html'
            && $this->http->currentUrl() !== 'https://www.cathaypacific.com/cx/en_HK/membership/elevate-your-cathay-experience.html'
        ) {
            if ($this->accessDenied) {
                \Cache::getInstance()->set(self::BMSZ_CACHE_KEY, '', -1);
                \Cache::getInstance()->set(self::ABCK_CACHE_KEY, '', -1);

                throw new \CheckException('failed auth', ACCOUNT_ENGINE_ERROR);
            }
            // need refresh SensorData
            $this->getSensorDataFromSelenium();
        }

        return parent::Login();
    }

    private function newSchema($asiaCabinCode, $fields, $depDate)
    {
        if (!isset($this->selenium)) {
            $this->seleniumCaptchaToken($fields,
                "https://www.cathaypacific.com/cx/en_GB/book-a-trip/redeem-flights/redeem-flight-awards.html");

            $QueueITToken = \Cache::getInstance()->get(self::Queue_CACHE_KEY);

            if (is_array($QueueITToken)) {
                foreach ($QueueITToken as $queue) {
                    switch (true) {
                        case $queue[0] === 'Queue-it':
                            $this->http->setCookie($queue[0], $queue[1], "queue.cathaypacific.com", "/",
                                strtotime("+1 year"));

                            break;

                        case $queue[0] === 'Queue-it-token':
                            $this->http->setCookie($queue[0], $queue[1], "queue.cathaypacific.com", "/",
                                strtotime("+3 minutes"));

                            break;

                        case strpos($queue[0], 'Queue-it-cathay') !== false:
                            $this->http->setCookie($queue[0], $queue[1], "queue.cathaypacific.com", "/",
                                strtotime("+5 minutes"));

                            break;

                        case strpos($queue[0], 'QueueITAccepted') !== false:
                            $this->http->setCookie($queue[0], $queue[1], ".cathaypacific.com", "/",
                                strtotime("+30 minutes"));

                            break;

                        default:
                            $this->http->setCookie($queue[0], $queue[1], "queue.cathaypacific.com", "/",
                                strtotime("+1 hour"));

                            break;
                    }
                }
            }
        }
        $this->http->setHttp2(true);

        $this->http->setCookie("LOGIN.REFRESH", "false", ".cathaypacific.com");
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
            'FLEXIBLEDATE'     => true,
            'ORIGIN[1]'        => $fields['DepCode'],
            'DESTINATION[1]'   => $fields['ArrCode'],
            'DEPARTUREDATE[1]' => $depDate,
            'LOGINURL'         =>
                'https://www.cathaypacific.com/cx/en_GB/sign-in/campaigns/miles-flight.html',
        ];

        $data = http_build_query($data);
        $this->http->GetURL("https://api.cathaypacific.com/redibe/IBEFacade?" . $data, $header);

        if (!$this->http->ParseForm('SubmissionDetails')) {
            throw new \CheckException('???', ACCOUNT_ENGINE_ERROR);
        }
        // выход на https://book.cathaypacific.com/CathayPacificAwardV3/dyn/air/booking/availability там основной ответ
        $this->http->PostForm();
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

    private function getMilesByCabin($milesInfoList, $cabin)
    {
        if (isset($milesInfoList->milesInfo->{$cabin})) {
            return $milesInfoList->milesInfo->{$cabin};
        }
        $this->sendNotification("RA miles not found // MI");

        throw new \CheckException("miles not found", ACCOUNT_ENGINE_ERROR);
    }

    private function timeFormat($milliseconds)
    {
        $seconds = floor($milliseconds / 1000);
        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);
        $minutes = $minutes % 60;

        return ($hours + $minutes > 0) ? sprintf('%02d:%02d', $hours, $minutes) : null;
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

    private function parseRewardFlights($pageBom, $requestParams, $config, $fields = [], $domain): array
    {
        $this->logger->notice(__METHOD__);
        $this->logger->info("ParseReward [" . $fields['DepDate'] . "-" . $fields['DepCode'] . "-" . $fields['ArrCode'] . "]",
            ['Header' => 2]);

        $routes = [];
        $milesInfoList = [];

        foreach ($pageBom->modelObject->availabilities->upsell->associations as $key => $associations) {
            $rbdList = $pageBom->modelObject->availabilities->upsell->recommendations->{$associations->recoId}->rbdsPerBound;
            /*$keyArr = explode('_', $key);
            for ($iter = 1; $iter < count($keyArr); $iter = $iter + 2) {
                //$this->logger->debug();
                $boundId = $iter - 2 >= 0 ? $iter - 2 : 0;
                $flight = $pageBom->modelObject->availabilities->upsell->bounds{$boundId}->flights{$keyArr[$iter]};
                //$this->logger->error(var_export($flight, true));
                $this->logger->error(var_export($rbdList[$boundId], true));
                $segmentRbd = (array)$rbdList[$boundId]->segmentRBDs;
                $associationCabin = preg_replace('/STD_\d+/', '', $keyArr[$iter - 1]);
                if (isset($flight, $segmentRbd)) {
                    $flightMilesInfoParamKey = $flight->flightIdString;
                    $flightSegmentsCabinList = [];
                    $invalidCabin = false;
                    for ($rbdIter = 0; $rbdIter < count($segmentRbd); $rbdIter++) {
                        $this->logger->error("rbdIter: {$rbdIter}");

                        $segment = $flight->segments{$rbdIter};
                        $this->logger->error(var_export($segment, true));

                        $carrier = $segment->flightIdentifier->marketingAirline;
                        $cabin = $this->getCabinFromRBD($config, $carrier, $segmentRbd{$rbdIter}->code);
                        $this->logger->error($cabin);
                        $status = $this->isDowngradable($associationCabin, $cabin);
                        $this->logger->warning('Status: ' . $status);

                        if ($cabin && strtolower($cabin) !== "unknown rbd") {
                            $flightSegmentsCabinList[] = $cabin;



                        } else {
                            $invalidCabin = true;
                        }
                    }
                    $this->logger->error(var_export($flightSegmentsCabinList, true));
                    if (!$invalidCabin) {
                        $flightMilesInfoParamKey = $flight->flightIdString . join(':', $flightSegmentsCabinList);
                        $this->logger->error("flightMilesInfoParamKey: ".$flightMilesInfoParamKey);

//                        if (!($flightMilesInfoParamKey in _upsell.milesInfoParams)) {
//                            _upsell.milesInfoParams[flightMilesInfoParamKey] = {};
//                        }

                        $milesInfoParams[$flightMilesInfoParamKey][$boundId . "_" . $keyArr[$iter] . "_" . $associationCabin] = [
                                'boundIndex'=> $boundId,
                                'flightId'=> $keyArr[$iter],
                                'cabin'=> $associationCabin,
                                'ff' => $keyArr[$iter - 1]
                            ];
                        $this->logger->debug(var_export($milesInfoParams, true));

                    }
                }
            }*/

            $associationCabin = preg_replace('/STD_\d+/', '', $key);
            $this->logger->debug("{$key} -> {$associationCabin}");
//            $associationCabinMiles = 'MILES_' . $associationCabin; // from ... miles

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

                        //$duration = $this->timeFormat($flight->duration);
                        $route = [
                            'num_stops'   => null,
                            'distance'    => null,
                            'times'       => ['flight' => null, 'layover' => null],
                            'redemptions' => [
                                'miles'   => null,
                                'program' => $this->AccountFields['ProviderCode'],
                            ],
                            'payments' => [
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
                                'num_stops' => $segment->numberOfStops,
                                'departure' => [
                                    'date'     => date('Y-m-d H:i', $segment->flightIdentifier->originDate / 1000),
                                    'dateTime' => $segment->flightIdentifier->originDate / 1000,
                                    'airport'  => $origin[1],
                                    'terminal' => preg_replace('/^T/', '', $origin[0]),
                                ],
                                'arrival' => [
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

        /*if (!empty($routes) && !empty($milesInfoList)) {
            $jsonHeaders = [
                'Accept'       => 'application/json, text/plain, * /*', //todo
                'Content-Type' => 'application/json',
                'Referer'      => 'https://book.' . $domain . '.com/',
            ];
            $data = json_encode(['milesInfoList' => $milesInfoList]);
            $this->http->RetryCount = 0;
            $this->http->PostURL('https://api.' . $domain . '.com/redibe/milesInfo/v2.0', $data, $jsonHeaders, 30);

            if (strpos($this->http->Error, 'Network error 28 - Operation timed out after') !== false
                || strpos($this->http->Error, 'Network error 52 - Empty reply from server') !== false
                || strpos($this->http->Error, 'Network error 56 - Received HTTP code 502') !== false
            ) {
                sleep(3);
                $this->http->PostURL('https://api.' . $domain . '.com/redibe/milesInfo/v2.0', $data, $jsonHeaders, 15);

                if (strpos($this->http->Error, 'Network error 28 - Operation timed out after') !== false
                    || strpos($this->http->Error, 'Network error 52 - Empty reply from server') !== false
                    || strpos($this->http->Error, 'Network error 56 - Received HTTP code 502') !== false
                ) {
                    throw new \CheckRetryNeededException(5, 3);
                }
            }

            if (in_array($this->http->Response['code'], [500, 502, 503])) {
                sleep(2);
                $this->http->PostURL('https://api.' . $domain . '.com/redibe/milesInfo/v2.0', $data, $jsonHeaders, 15);

                if (in_array($this->http->Response['code'], [500, 502, 503])) {
                    throw new \CheckRetryNeededException(5, 0);
                }
            }

            $this->http->RetryCount = 2;

            $milesInfoList = $this->http->JsonLog();

            if (empty($milesInfoList) || empty($milesInfoList->milesInfo)) {
                throw new \CheckRetryNeededException(5, 0);
            }
        }*/
        $result = [];

        $check = false;

        foreach ($routes as $route) {
            /*$miles = $this->getMilesByCabin($milesInfoList, $route['flightIdString']);

            if (is_numeric($miles) && $miles > 0) {
                $route['redemptions']['miles'] = $miles;
            } else {
                $check = true;

                continue;
            }*/
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
            'Accept' => '*/*',
            'Origin' => 'https://www.cathaypacific.com',
            'Referer'=> 'https://www.cathaypacific.com/',
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

        if (!is_array($data)) {
            $url = "https://api.cathaypacific.com/common/api/v2/airline/en/airline-by-od?destination={$fields['ArrCode']}&origin={$fields['DepCode']}&type=standard";
            $this->http->GetURL($url, $headers, 30);

            if (in_array($this->http->Response['code'], [504, 400, 500, 503, 408])) {
                sleep(2);
                $this->http->GetURL($url, $headers, 30);
            }
            $data = $this->http->JsonLog(null, 0, true);

            if (!empty($data)) {
                \Cache::getInstance()
                    ->set('ra_asia_airlines_v2_' . $fields['DepCode'] . '_' . $fields['ArrCode'], $data, 60 * 60 * 24);
            }
        }

        if (!isset($data['airlines'])) {
            if (isset($data['errors']) && strpos($this->http->Response['body'], 'No carrier found') !== false) {
                $this->SetWarning('No carrier found on route from ' . $fields['DepCode'] . ' to ' . $fields['ArrCode']);

                return false;
            }

            if (!in_array($this->http->Response['code'], [504, 400, 500, 503])
                && strpos($this->http->Error, 'Network error ') === false
            ) {
                $this->sendNotification('check valid airlines // ZM');
            }

            return true;
        }

        if (isset($data['airlines']) && !empty($data['airlines'])) {
            $airlines = [];

            foreach ($data['airlines'] as $airline) {
                $url = "https://api.cathaypacific.com/afr/searchpanel/searchoptions/en.{$fields['DepCode']}.{$fields['ArrCode']}.ow.std.{$airline['airlineDesignator']}.json";
                $this->http->GetURL($url, $headers, 30);

                if ($this->http->Response['code'] == 504) {
                    $this->http->GetURL($url, $headers, 30);
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

//                    continue;
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

    private function validRoute($fields): bool
    {
        $this->logger->notice(__METHOD__);
        $dataFrom = \Cache::getInstance()->get('ra_asia_origins');

        if (!$dataFrom || !is_array($dataFrom)) {
            $this->http->RetryCount = 0;
//            $this->http->GetURL("https://api.cathaypacific.com/redibe/airport/origin/en/", [], 20);
            $this->http->GetURL("https://api.asiamiles.com/redibe/airport/origin/en/", [], 20);
            $this->http->RetryCount = 2;

            if ($this->isBadProxy()) {
                throw new \CheckRetryNeededException(5, 0);
            }
            $dataFrom = $this->http->JsonLog(null, 0, true);

            if (!empty($dataFrom)) {
                \Cache::getInstance()->set('ra_asia_origins', $dataFrom, 60 * 60 * 24);
            }
        }

        if (isset($dataFrom['airports']) && is_array($dataFrom['airports'])) {
            $inOrigins = false;

            foreach ($dataFrom['airports'] as $origin) {
                if ($origin['airportCode'] === $fields['DepCode']) {
                    $inOrigins = true;

                    break;
                }
            }

            if (!$inOrigins) {
                $this->SetWarning($fields['DepCode'] . " is not in list of origins");

                return false;
            }
            $dataTo = \Cache::getInstance()->get('ra_asia_destinations_' . $fields['DepCode']);

            if (!$dataTo || !is_array($dataTo)) {
                $this->http->RetryCount = 0;
//                $this->http->GetURL("https://api.cathaypacific.com/redibe/airport/destination/{$fields['DepCode']}/en/", [], 20);
                $this->http->GetURL("https://api.asiamiles.com/redibe/airport/destination/{$fields['DepCode']}/en/", [], 20);
                $this->http->RetryCount = 2;
                $dataTo = $this->http->JsonLog(null, 0, true);

                if (!empty($dataTo)) {
                    \Cache::getInstance()->set('ra_asia_destinations_' . $fields['DepCode'], $dataTo, 60 * 60 * 24);
                }
            }

            if (isset($dataTo['airports']) && is_array($dataTo['airports'])) {
                $inDestinations = false;

                foreach ($dataTo['airports'] as $destinations) {
                    if ($destinations['airportCode'] === $fields['ArrCode']) {
                        $inDestinations = true;

                        break;
                    }
                }

                if (!$inDestinations) {
                    $this->SetWarning($fields['ArrCode'] . " is not in list of destinations");

                    return false;
                }
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
            || strpos($this->http->Error, 'Network error 56 - Recv failure: Connection reset by peer') !== false
        ;
    }

    private function seleniumCaptchaToken($fields, $url = null)
    {
        $this->logger->notice(__METHOD__);
        $allCookies = array_merge($this->http->GetCookies(".cathaypacific.com"), $this->http->GetCookies(".cathaypacific.com", "/", true));
        $allCookies = array_merge($allCookies, $this->http->GetCookies("api.cathaypacific.com"), $this->http->GetCookies("api.cathaypacific.com", "/", true));
        $allCookies = array_merge($allCookies, $this->http->GetCookies(".www.cathaypacific.com"), $this->http->GetCookies(".www.cathaypacific.com", "/", true));
        $allCookies = array_merge($allCookies, $this->http->GetCookies(".book.cathaypacific.com"), $this->http->GetCookies(".book.cathaypacific.com", "/", true));

        $result = false;
        $retry = false;

        try {
            if (!isset($this->selenium)) {
                $this->selenium = clone $this;

                $this->http->brotherBrowser($this->selenium->http);
                $this->logger->notice("Running Selenium...");
                $this->selenium->UseSelenium();
                //            $selenium->useGoogleChrome(\SeleniumFinderRequest::CHROME_95);
                //$this->selenium->useFirefox(\SeleniumFinderRequest::FIREFOX_84);
                $this->selenium->useChromium();
                $this->selenium->http->saveScreenshots = true;

                try {
                    $this->selenium->http->start();
                    $this->selenium->Start();
                } catch (\UnknownServerException | \SessionNotCreatedException $e) {
                    $this->markProxyAsInvalid();
                    $this->logger->error("exception: " . $e->getMessage());

                    throw new \CheckRetryNeededException(5, 0);
                }

                foreach ($allCookies as $key => $value) {
                    $this->selenium->driver->manage()->addCookie(['name' => $key, 'value' => $value, 'domain' => ".cathaypacific.com"]);
                }
            }

            if (!isset($url)) {
                $url = 'https://book.cathaypacific.com/CathayPacificAwardV3/dyn/air/booking/availability';
            }
            $this->selenium->http->GetURL($url);
            sleep(2);
            $selenium = $this->selenium;
            $selenium->waitFor(function () use ($selenium) {
                return is_null($selenium->waitForElement(\WebDriverBy::xpath("//span[normalize-space()='Please keep this page open']"),
                    0));
            }, 50);
            $this->savePageToLogs($this->selenium);

            if ($selenium->waitForElement(\WebDriverBy::xpath("//p[normalize-space()='Your turn started at']"), 0)) {
                $btn = $selenium->waitForElement(\WebDriverBy::ip('buttonConfirmRedirect'), 0);

                if ($btn) {
                    $btn->click();
                    $this->savePageToLogs($this->selenium);
                }
            }
            $this->logger->warning('[current url]: ' . $this->selenium->http->currentUrl());

            $cookies = $this->selenium->driver->manage()->getCookies();

            $QueueItToken = [];

            foreach ($cookies as $cookie) {
                if (strpos($cookie['name'], 'Queue') === 0) {
                    $QueueItToken[] = [$cookie['name'], $cookie['value']];
                }
                $this->http->setCookie($cookie['name'], $cookie['value'], $cookie['domain'], $cookie['path'],
                    $cookie['expiry'] ?? null);
            }

            if (!empty($QueueItToken)) {
                \Cache::getInstance()->set(self::Queue_CACHE_KEY, $QueueItToken, 60 * 60);
            }

            $this->http->GetURL("https://www.cathaypacific.com/cx/en_GB/book-a-trip/redeem-flights/redeem-flight-awards.html");
        } catch (\WebDriverCurlException $e) {
            $this->logger->error("WebDriverCurlException exception: " . $e->getMessage());
            $this->DebugInfo = "WebDriverCurlException";
            // retries
            if (strpos($e->getMessage(), '/session') !== false) {
                $retry = true;
            }
        }// catch (WebDriverCurlException $e)
        catch (\NoSuchDriverException | \UnknownServerException | \NoSuchWindowException | \TimeOutException $e) {
            $this->logger->error("exception: " . $e->getMessage());
            $this->DebugInfo = "exception";
            // retries
            $retry = true;
        }// catch (WebDriverCurlException $e)
        catch (\UnexpectedJavascriptException $e) {
            $this->logger->error("UnexpectedJavascriptException exception: " . $e->getMessage());
            $this->DebugInfo = "UnexpectedJavascriptException";
            // retries
            if (
                strpos($e->getMessage(), 'TypeError: document.documentElement is null') !== false
                || strpos($e->getMessage(), 'ReferenceError: $ is not defined') !== false
            ) {
                $retry = true;
            }
        }// catch (WebDriverCurlException $e)
        finally {
            // close Selenium browser
            $this->selenium->http->cleanup(); //todo
            unset($this->selenium);

            // retries
            if ($retry && ConfigValue(CONFIG_SITE_STATE) != SITE_STATE_DEBUG) {
                $this->logger->debug("[attempt]: {$this->attempt}");

                throw new \CheckRetryNeededException(3, 10);
            }
        }

        return $result;
    }

    private function sendAjaxPost2($selenium, $fields)
    {
        $this->logger->notice(__METHOD__);
        $dateStr = date('Ymd', $fields['DepDate']);

        $asiaCabinCode = $this->getCabin($fields['Cabin'], true);

        $selenium->driver->executeScript("(function(e,s){e.src=s;e.onload=function(){jQuery.noConflict();console.log('jQuery injected')};document.head.appendChild(e);})(document.createElement('script'),'//code.jquery.com/jquery-latest.min.js');");
        sleep(2);

        $tt =
            '
            jQuery.ajax(
            {
                url: "https://api.cathaypacific.com/redibe/standardAward/create",
                method :"OPTIONS",
                crossDomain: true,
                headers: {
                    "Accept": "*/*",
                    "Access-Control-Request-Headers": "cache-control,content-type,pragma",
                    "Access-Control-Request-Method": "POST",
                    "Referer": "https://www.asiamiles.com/"
                }
            });
            
                    var xhttp = new XMLHttpRequest();
                    xhttp.withCredentials = true;
                    xhttp.open("POST", "https://api.cathaypacific.com/redibe/standardAward/create", false);
                    xhttp.setRequestHeader("Content-type", "application/json;charset=utf-8");
                    xhttp.setRequestHeader("Accept", "application/json, text/plain, */*");
                    xhttp.setRequestHeader("Connection", "keep-alive");
                    xhttp.setRequestHeader("Pragma", "no-cache");
                    xhttp.setRequestHeader("Cache-Control", "no-cache");
                    xhttp.setRequestHeader("Accept-Encoding", "gzip, deflate, br");
                    xhttp.setRequestHeader("Origin", "https://www.cathaypacific.com");
                    xhttp.setRequestHeader("Referer", "https://www.cathaypacific.com");
                    var data = JSON.stringify({"brand":"CX","entryLanguage":"en","entryCountry":"US","entryPoint":"https://www.cathaypacific.com/cx/en_US/book-a-trip/redeem-flights/facade.html","returnUrl":"https://www.cathaypacific.com/cx/en_US/book-a-trip/redeem-flights/facade.html?recent_search=ow","errorUrl":"https://www.cathaypacific.com/cx/en_US/book-a-trip/redeem-flights/facade.html?recent_search=ow","segments":[{"departureDate":"' . $dateStr . '","origin":"' . $fields['DepCode'] . '","destination":"' . $fields['ArrCode'] . '"}],"cabinClass":"' . $asiaCabinCode . '","numAdult":' . $fields['Adults'] . ',"numChild":0,"promotionCode":"","awardType":"Standard","isFlexibleDate":true});
                    var responseText = null;
                    xhttp.onreadystatechange = function() {
                        if (this.readyState == 4 && this.status == 200) {
                            responseText = this.responseText;
                        }
                    };
                    xhttp.send(data);
                    return responseText;
        ';
        $this->logger->debug($tt, ['pre' => true]);
        $returnData = $selenium->driver->executeScript($tt);
        $this->logger->debug('returnData: ' . $returnData);

        /*$response = $this->http->JsonLog($returnData);

        if (!$response) {
            throw new \CheckRetryNeededException(5, 0);
        }

        if (isset($response->errors[0], $response->errors[0]->message)) {
            if ($response->errors[0]->code === 'IBE_BUS0085_S004') {
                $this->SetWarning('Your selected itinerary is not eligible for redemption');

                return [];
            }

            throw new \CheckRetryNeededException(5, 0);
        }

        if (isset($response->errors[0], $response->errors[0]->message)
            && $response->errors[0]->message === 'internal server error, please try again or contact us'
        ) {
            throw new \CheckRetryNeededException(5, 0);

        }*/
        return $returnData;
    }

    private function savePageToLogs($selenium)
    {
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

    private function sendResponseUrlToPost($response, $domain)
    {
        $headers = [
            'Accept'       => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Referer'      => 'https://www.' . $domain . '.com/',
        ];
        $data = [
            'SERVICE_ID'           => $response->parameters->SERVICE_ID,
            'LANGUAGE'             => $response->parameters->LANGUAGE,
            'EMBEDDED_TRANSACTION' => $response->parameters->EMBEDDED_TRANSACTION,
            'SITE'                 => $response->parameters->SITE,
            'ENC'                  => $response->parameters->ENC,
            'ENCT'                 => $response->parameters->ENCT,
            'ENTRYCOUNTRY'         => 'US',
            'ENTRYLANGUAGE'        => 'en',
        ];
        $this->http->PostURL($response->urlToPost, $data, $headers);

        if ($this->http->Response['code'] == 500) {
            sleep(2); // helped
            $this->http->PostURL($response->urlToPost, $data, $headers);
        }
    }

    private function getSensorDataFromSelenium()
    {
        $this->logger->notice(__METHOD__);

        $this->selenium = clone $this;
        $retry = false;
        $this->http->brotherBrowser($this->selenium->http);

        try {
            $this->logger->notice("Running Selenium...");
            $this->selenium->UseSelenium();
            $this->selenium->useChromium();
            $this->selenium->http->removeCookies();
            $this->selenium->http->saveScreenshots = true;
            $this->selenium->keepCookies(false);
            $this->selenium->http->start();
            $this->selenium->Start();
            $this->selenium->http->GetURL("https://www.cathaypacific.com/cx/en_US/sign-in.html?switch=Y");

            if (filter_var($this->AccountFields['Login'], FILTER_VALIDATE_EMAIL) === false) {
                $typeAuth = 'Sign in with membership number';
                $typeLogin = 'membernumber';
            } else {
                $typeAuth = 'Sign in with email';
                $typeLogin = 'email';
            }
            $btn = $this->selenium->waitForElement(WebDriverBy::xpath("//button[contains(normalize-space(),'{$typeAuth}')]"), 15);

            if (!$btn) {
                $this->logger->error("{$typeAuth} not found");

                throw new \CheckRetryNeededException(3, 3);
            }
            $btn->click();
            $login = $this->selenium->waitForElement(WebDriverBy::xpath("//input[@name='{$typeLogin}']"), 2);
            $pwd = $this->selenium->waitForElement(WebDriverBy::xpath("//input[@name='password']"), 0);
            $this->savePageToLogs($this->selenium);

            if (!$login && $this->waitForElement(\WebDriverBy::xpath("//div[normalize-space()='Error loading chunks!']"))) {
                $this->selenium->http->GetURL("https://www.cathaypacific.com/cx/en_US/sign-in.html?switch=Y");

                if (filter_var($this->AccountFields['Login'], FILTER_VALIDATE_EMAIL) === false) {
                    $typeAuth = 'Sign in with membership number';
                    $typeLogin = 'membernumber';
                } else {
                    $typeAuth = 'Sign in with email';
                    $typeLogin = 'email';
                }
                $btn = $this->selenium->waitForElement(WebDriverBy::xpath("//button[contains(normalize-space(),'{$typeAuth}')]"), 15);

                if (!$btn) {
                    $this->logger->error("{$typeAuth} not found");

                    throw new \CheckRetryNeededException(3, 3);
                }
                $btn->click();
                $login = $this->selenium->waitForElement(WebDriverBy::xpath("//input[@name='{$typeLogin}']"), 2);
                $pwd = $this->selenium->waitForElement(WebDriverBy::xpath("//input[@name='password']"), 0);
                $this->savePageToLogs($this->selenium);
            }

            if (!$login || !$pwd) {
                $this->logger->error("login field(s) not found");

                throw new \CheckRetryNeededException(3, 3);
            }
            $login->sendKeys($this->AccountFields['Login']);
            $pwd->sendKeys($this->AccountFields['Pass']);
            sleep(2);
            $btn = $this->selenium->waitForElement(WebDriverBy::xpath("//button[normalize-space()='Sign in']"), 0);
            $btn->click();

            $this->selenium->waitForElement(WebDriverBy::xpath("
                //span[@class='welcomeLabel'][starts-with(normalize-space(),'Welcome,')] 
                | //h2[contains(text(), 'Confirm your mobile phone number') or contains(text(), 'We need to verify your identity')] 
                | //label[contains(@class, 'textfield__errorMessage')] 
                | //div[contains(@class, 'serverSideError__messages')] 
                | //h1[contains(text(), 'Access Denied')]"), 15);
            $this->savePageToLogs($this->selenium);

            $_abck = $bm_sz = $mlc_prelogin = $QueueItToken = null;

            if ($msg = $this->http->FindSingleNode("//div[contains(@class, 'serverSideError__messages')]")) {
                if ($msg === 'Your sign-in details are incorrect. Please check your details and try again. [ Error Code: 2004 ]'
                    || strpos($msg, 'Your member account is temporarily locked after too many unsuccessful login attempts. You can reset your password by confirming your personal information') !== false
                ) {
                    throw new \CheckException($msg, ACCOUNT_PROVIDER_ERROR);
                }
                $this->sendNotification('check error');

                throw new \CheckException($msg, ACCOUNT_ENGINE_ERROR);
            }

            if ($this->http->FindSingleNode("//h2[contains(text(), 'Confirm your mobile phone number') or contains(text(), 'We need to verify your identity')]")) {
                $later = $this->selenium->waitForElement(WebDriverBy::xpath("//a[contains(normalize-space(),'Remind me later')]"), 0);

                if (!$later) {
                    throw new \CheckException('something wrong', ACCOUNT_ENGINE_ERROR);
                }
                $later->click();
                $this->selenium->waitForElement(WebDriverBy::xpath("
                    //span[@class='welcomeLabel'][starts-with(normalize-space(),'Welcome,')] 
                    | //label[contains(@class, 'textfield__errorMessage')] 
                    | //h1[contains(text(), 'Access Denied')]"), 15);
                $this->savePageToLogs($this->selenium);
            }

            if ($this->http->FindSingleNode("//span[@class='welcomeLabel'][starts-with(normalize-space(),'Welcome,')]")
                || ($this->attempt == 0) // debug
            ) {
                $this->selenium->http->GetURL("https://www.cathaypacific.com/cx/en_GB/book-a-trip/redeem-flights/redeem-flight-awards.html");
                $selenium = $this->selenium;
                $selenium->waitFor(function () use ($selenium) {
                    return is_null($selenium->waitForElement(\WebDriverBy::xpath("//span[normalize-space()='Please keep this page open']"),
                        0));
                }, 50);
                $this->savePageToLogs($this->selenium);

                $cookies = $this->selenium->driver->manage()->getCookies();

                foreach ($cookies as $cookie) {
                    if ($cookie['name'] === 'bm_sz') {
                        $bm_sz = $cookie['name'];
                        \Cache::getInstance()->set(self::BMSZ_CACHE_KEY, $cookie['value'], 60 * 60);
                    } elseif ($cookie['name'] === '_abck') {
                        $_abck = $cookie['name'];
                        \Cache::getInstance()->set(self::ABCK_CACHE_KEY, $cookie['value'], 60 * 60);
                    } elseif ($cookie['name'] === 'mlc_prelogin') {
                        $mlc_prelogin = $cookie['name'];
                    } elseif (strpos($cookie['name'], 'Queue') === 0) {
                        $QueueItToken[] = [$cookie['name'], $cookie['value']];
                    }

                    $this->http->setCookie($cookie['name'], $cookie['value'], $cookie['domain'], $cookie['path'],
                        $cookie['expiry'] ?? null);
                }
            }

            if (!empty($QueueItToken)) {
                \Cache::getInstance()->set(self::Queue_CACHE_KEY, $cookie['value'], 60 * 60);
            }

            if (isset($_abck, $bm_sz, $QueueItToken)) {
                $this->cookiesRefreched = true;
            }

            if ($this->http->FindSingleNode("//h1[contains(text(), 'Access Denied')]")) {
                $this->accessDenied = true;

                if (!$this->cookiesRefreched) {
                    $retry = true;
                }
            } elseif (null === $mlc_prelogin) {
                $this->savePageToLogs($this->selenium);
                $this->accessDenied = true; // for retry, debug
            }
        } catch (\UnknownServerException | \SessionNotCreatedException $e) {
            $this->logger->error("exception: " . $e->getMessage());
            $this->DebugInfo = "exception";
            $retry = true;
        } finally {
            if ($retry && ConfigValue(CONFIG_SITE_STATE) != SITE_STATE_DEBUG) {
                // close Selenium browser
                $this->selenium->http->cleanup();
                $this->logger->debug("[attempt]: {$this->attempt}");

                throw new \CheckRetryNeededException(3, 0);
            }

            if (isset($QueueItToken)) {
                // close Selenium browser
                $this->selenium->http->cleanup();
                unset($this->selenium);
            }
        }

        return null;
    }

    private function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        return false;
    }
}
