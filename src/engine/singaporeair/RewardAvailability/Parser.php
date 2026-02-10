<?php

namespace AwardWallet\Engine\singaporeair\RewardAvailability;

use AwardWallet\Engine\ProxyList;
use AwardWallet\Engine\Settings;

class Parser extends \TAccountCheckerSingaporeair
{
    use \SeleniumCheckerHelper;
    use ProxyList;
    public $isRewardAvailability = true;
    private $browser;
    private $warningMessages;
    private $warningMessage; // if only return flights (which skipped)
    private $warningMessageNoTaxes = false;
    private $defaultCurrency;
    private $requestCurrency;

    // CREDENTIALS for test
    // Login    959311078
    // Pass     195458

    public static function getRASearchLinks(): array
    {
        return ['https://www.singaporeair.com/en_UK/us/home' => 'search page'];
    }

    public function InitBrowser()
    {
        \TAccountChecker::InitBrowser();
        $this->KeepState = true;
        $this->http->setHttp2(true);
//        $this->setProxyBrightData();
//        $this->setProxyBrightData(null, Settings::RA_ZONE_STATIC);

        switch (random_int(0, 3)) {
            case 0:
            case 1:
            case 2:
                $this->setProxyBrightData(null, Settings::RA_ZONE_STATIC);

                break;

//            case 1:
//                $this->setProxyOxylabs();
//
//                break;

            case 3:
//                $this->setProxyNetNut();
                $this->setProxyGoProxies();

                break;
        }

        //$this->http->setRandomUserAgent(20);
        //$this->http->setUserAgent("Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:81.0) Gecko/20100101 Firefox/81.0");
        $this->http->setDefaultHeader("Accept-Encoding", "gzip, deflate, br");
    }

    public function LoadLoginForm()
    {
        $debug = $this->AccountFields['DebugState'] ?? false;

        if ($debug) {
            return parent::LoadLoginForm();
        }
        $this->logger->error('parser off. seems all accounts lock');

        throw new \CheckException('something wrong', ACCOUNT_ENGINE_ERROR);
    }

    /*function IsLoggedIn()
    {
        return false;
    }

    public function LoadLoginForm()
    {
        //$this->http->removeCookies();
        return true;
    }

    public function Login()
    {
        return true;
    }*/

    public function getRewardAvailabilitySettings()
    {
        $arrCurrencies = ['USD', 'SGD'];

        return [
            'supportedCurrencies'      => $arrCurrencies,
            'supportedDateFlexibility' => 0,
            'defaultCurrency'          => 'USD',
        ];
    }

    public function ParseRewardAvailability(array $fields)
    {
        $this->logger->info("Parse Reward Availability", ['Header' => 2]);
        $this->logger->debug("params: " . var_export($fields, true));
//        if (!$this->validRoute($fields)) {
//            return ['routes' => []];
//        }
        $supportedCurrencies = $this->getRewardAvailabilitySettings()['supportedCurrencies'];

        if (!in_array($fields['Currencies'][0], $supportedCurrencies)) {
            $fields['Currencies'][0] = $this->getRewardAvailabilitySettings()['defaultCurrency'];
            $this->logger->notice("parse with defaultCurrency: " . $fields['Currencies'][0]);
        }
        $this->requestCurrency = $fields['Currencies'][0];
        $cabins = $this->getCabinFields();

        $this->http->GetURL("https://www.singaporeair.com/en_UK/us/home");
        $sensorPostUrl = $this->http->FindSingleNode("//link[contains(@href,'/cp_challenge/')]/preceding-sibling::script[1][normalize-space()=''][@type='text/javascript']/@src");

        if (!empty($sensorPostUrl)) {
            $this->http->NormalizeURL($sensorPostUrl);
            $this->sendSensorData($sensorPostUrl);
        }

        $this->http->RetryCount = 0;
        $headers = [
            'Accept'       => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
        // 16/05/2021
        $departureMonth = date('d/m/Y', $fields['DepDate']);
        $returnMonth = date('d/m/Y', strtotime('+1 month', $fields['DepDate']));
        $data = [
            'landingSearch'              => 'true',
            '_payByMiles'                => 'on',
            'payByMiles'                 => 'true',
            'fromHomePage'               => 'true',
            'orbOrigin'                  => $fields['DepCode'],
            'orbDestination'             => $fields['ArrCode'],
            '_tripType'                  => 'on',
            'tripType'                   => 'O',
            'departureMonth'             => $departureMonth,
            'returnMonth'                => '', // TODO: As on the site
            'orbVsSwitch'                => 'true',
            //'vSEnabledCountries'         => 'ALL',
            'flexibleDates'              => 'on',
            'cabinClass'                 => $cabins[$fields['Cabin']],
            'numOfAdults'                => $fields['Adults'],
            'numOfChildren'              => '0',
            'flowIdentifier'             => 'redemptionBooking',
            'numOfInfants'               => '0',
            '_eventId_flightSearchEvent' => '',
            'isLoggedInUser'             => 'true',
            'numOfChildrenNominees'      => '0',
            'numOfAdultNominees'         => '0',
        ];
        $this->http->PostURL('https://www.singaporeair.com/booking-flow.form', $data, $headers, 20);

        if (in_array($this->http->Response['code'], [404, 403])) {
            // retry PostURL not helped
            throw new \CheckRetryNeededException(5, 0);
        }

        $data = [
            'payByMiles'     => 'true',
            'orbOrigin'      => $fields['DepCode'],
            'orbDestination' => $fields['ArrCode'],
            'tripType'       => 'O',
            'flexibleDates'  => 'true',
            'departureMonth' => $departureMonth,
            'returnMonth'    => '', // TODO: As on the site
            'cabinClass'     => $cabins[$fields['Cabin']],
            'numOfAdults'    => $fields['Adults'],
            'numOfChildren'  => '0',
            'numOfInfants'   => '',
            'isLoggedInUser' => '',
            'fromEditSearch' => '',
            'flowIdentifier' => 'redemptionBooking',
        ];

        if ($this->http->ParseForm('bpredirect')) {
            $this->http->PostForm();
        } else {
            $this->logger->error('no bpredirect');
            $this->http->PostURL('https://www.singaporeair.com/redemption/searchFlight.form', $data, $headers);
        }

        if ($this->http->currentUrl() === 'https://www.singaporeair.com/redemption/searchFlight.form'
            && $key = $this->http->FindSingleNode('//iframe[contains(@src,"recaptcha")]/@data-key')
        ) {
            $token = $this->parseReCaptchaItinerary($key);

            if ($token) {
                $this->http->GetURL("https://www.singaporeair.com/_sec/cp_challenge/verify?cpt-token={$token}");
                $this->http->PostURL('https://www.singaporeair.com/redemption/searchFlight.form', $data, $headers);
            }
        }

        if ($this->http->Response['code'] == 403 || strpos($this->http->Error, 'empty body') !== false) {
            sleep(1);
            $this->http->PostURL('https://www.singaporeair.com/redemption/searchFlight.form', $data, $headers, 30);
        }

        if ($this->http->Response['code'] == 403) {
            throw new \CheckRetryNeededException(5, 0);
        }

        if ($this->http->Response['code'] != 200) {
            sleep(1);
            $this->http->PostURL('https://www.singaporeair.com/redemption/searchFlight.form', $data, $headers, 30);
        }

        if ($this->http->Response['code'] !== 200
            && (strpos($this->http->Response['errorMessage'], 'Operation timed out after') !== false
                || strpos($this->http->Response['errorMessage'], 'Network error 56 - Unexpected EOF') !== false
            )
        ) {
            throw new \CheckRetryNeededException(5, 0);
        }

        if ($this->http->currentUrl() === 'https://www.singaporeair.com/en_UK/us/home') {
            $this->SetWarning('does not allow searching along the given route');

            return ['routes' => []];
        }

        if (strpos($this->http->currentUrl(),
                'https://www.singaporeair.com/en_UK/us/home?errorCategory=flightsearchORB&errorKey=flightSearch.noFlight') !== false) {
            $this->SetWarning('Due to a system limitation, the flights for the origin and destination you have selected cannot be displayed. Please select another date/itinerary.');

            return ['routes' => []];
        }

        $routes = [];

        // KrisFlyer
        $pageDate = $this->http->FindPreg('/,"flightSearch":\s*(\{.+?\}),"gtmScriptBlocked":/');
        $flightSearch = $this->http->JsonLog($pageDate, 4);

        if (isset($flightSearch->response->data->flights) && $this->http->FindPreg('/"messageContent":\s*"Select a different date. Alternatively, search for seats on/',
                false, json_encode($flightSearch->response->data->flights))) {
            $warning = "There are no flights";
        } else {
            $routes = $this->parseRewardFlights($flightSearch, $fields);
        }
        $headers = [
            'Accept'              => 'application/json, text/plain, */*',
            'Referer'             => 'https://www.singaporeair.com/redemption/loadFlightSearchPage.form',
            'X-Sec-Clge-Req-Type' => 'ajax',
        ];
        // Star Alliance
        $this->http->GetURL('https://www.singaporeair.com/redemption/getFlightSearch.form?carrierType=SA', $headers,
            20);
        $response = $this->http->JsonLog(null, 4);

        if ($this->http->Response['code'] == 403) {
            sleep(2);
            $this->http->GetURL('https://www.singaporeair.com/redemption/getFlightSearch.form?carrierType=SA', $headers,
                20);

            if ($this->http->Response['code'] == 403) {
                throw new \CheckRetryNeededException(5, 0);
            }
            $response = $this->http->JsonLog(null, 4);
        }

        if (!$response
            || (
                isset($response->flightSearch->response->data->flights)
                && $this->http->FindPreg('/"messageContent":\s*"Select a different date. Alternatively, search for seats on/',
                    false, json_encode($response->flightSearch->response->data->flights))
            )
            || (isset($response->status) && $response->status === 'FAILURE')
        ) {
            $this->logger->warning('without Star Alliance');

            if (empty($routes) && $this->http->FindPreg('/\{"status":"FAILURE"\}/')) {
                // TODO: seems block
                throw new \CheckRetryNeededException(5, 0);
            }
            // try to get warning text
            if (empty($routes) && !empty($response->flightSearch->response->data->flights)) {
                foreach ($response->flightSearch->response->data->flights as $item) {
                    if ($item->originAirportCode != $fields['DepCode']) {
                        $this->logger->notice("Skip: return flight {$item->originAirportCode} -> {$item->destinationAirportCode}");

                        if (isset($item->nextCabinClass, $item->nextCabinClass->messageTitle)
                            && strpos($item->nextCabinClass->messageTitle, 'There are no available') === 0) {
                            $this->warningMessage = $item->nextCabinClass->messageTitle;
                        }

                        continue;
                    }

                    if (isset($item->nextCabinClass, $item->nextCabinClass->messageTitle)) {
                        $this->warningMessages[] = $item->nextCabinClass->messageTitle;
                        $this->logger->error($item->nextCabinClass->messageTitle);
                    }
                }
            }

            if (empty($routes) && isset($warning)) {
                $this->warningMessages[] = $warning;
            }
        } else {
            $routes = array_merge($this->parseRewardFlights($response->flightSearch, $fields), $routes);
        }

        $this->http->RetryCount = 2;

        foreach ($routes as $key => $route) {
            if ($route['payments']['taxes'] == null
                && ($route['payments']['currency'] == $this->requestCurrency || empty($route['payments']['currency']))
            ) {
                $routes[$key]['payments']['currency'] = $this->defaultCurrency ?? $this->requestCurrency;
            }
        }

        if (empty($routes)) {
            if (is_array($this->warningMessages)) {
                $this->warningMessages = array_unique($this->warningMessages);
                $this->logger->notice(var_export($this->warningMessages, true), ['pre' => true]);
                $this->SetWarning(array_shift($this->warningMessages));
            } elseif (!empty($this->warningMessage)) {
                $this->SetWarning($this->warningMessage);
            }
        }

        if (!empty($routes) && $this->warningMessageNoTaxes) {
            $this->SetWarning('Not all flights collected taxes');
        }
        $this->logger->debug(var_export($routes, true));

        return ['routes' => $routes];
    }

    public function sendSensorData($sensorPostUrl)
    {
        $this->logger->notice(__METHOD__);

        $sensorData = [
            "7a74G7m23Vrp0o5c9298521.7-1,2,-94,-100,Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:92.0) Gecko/20100101 Firefox/92.0,uaend,11059,20100101,ru-RU,Gecko,0,0,0,0,402713,9629542,1536,824,1536,864,1536,347,1550,,cpen:0,i1:0,dm:0,cwen:0,non:1,opc:0,fc:1,sc:0,wrc:1,isc:84.80000305175781,vib:1,bat:0,x11:0,x12:1,5557,0.609336269304,818364814771,0,loc:-1,2,-94,-101,do_en,dm_en,t_dis-1,2,-94,-105,0,-1,1,0,-1,324,0;0,-1,0,0,1779,1230,0;1,-1,0,0,2142,883,0;0,2,0,0,1809,1230,0;1,-1,0,0,2172,883,0;0,-1,0,0,937,937,0;0,-1,0,0,763,1062,0;0,-1,0,0,764,1063,0;0,-1,0,0,765,1064,0;0,-1,0,0,766,1065,0;0,-1,0,0,767,1066,0;0,-1,0,0,768,1067,0;0,-1,0,0,769,1068,0;0,-1,0,0,770,1069,0;0,-1,0,0,-1,678,0;1,-1,0,0,2432,883,0;0,-1,0,0,1303,648,0;0,-1,0,0,2187,1186,0;0,-1,0,0,1071,1490,0;0,-1,0,0,1103,1190,0;0,-1,1,0,1189,1011,0;0,-1,1,0,1738,1689,0;-1,2,-94,-102,0,-1,1,0,-1,324,0;0,-1,0,0,1779,1230,0;1,-1,0,0,2142,883,0;0,2,0,0,1809,1230,0;1,-1,0,0,2172,883,0;0,-1,0,0,937,937,0;0,-1,0,0,763,1062,0;0,-1,0,0,764,1063,0;0,-1,0,0,765,1064,0;0,-1,0,0,766,1065,0;0,-1,0,0,767,1066,0;0,-1,0,0,768,1067,0;0,-1,0,0,769,1068,0;0,-1,0,0,770,1069,0;0,-1,0,0,-1,678,0;1,-1,0,0,2432,883,0;0,-1,0,0,1303,648,0;0,-1,0,0,2187,1186,0;0,-1,0,0,1071,1490,0;0,-1,0,0,1103,1190,0;0,-1,1,0,1189,1011,0;0,-1,1,0,1738,1689,0;-1,2,-94,-108,-1,2,-94,-110,-1,2,-94,-117,-1,2,-94,-111,-1,2,-94,-109,-1,2,-94,-114,-1,2,-94,-103,-1,2,-94,-112,https://www.singaporeair.com/en_UK/sg/home#/book/bookflight-1,2,-94,-115,1,32,32,0,0,0,0,2,0,1636729629542,-999999,17509,0,0,2918,0,0,3,0,0,0E4988E903B90744D161E867DC87A147~-1~YAAQlwVJF6oqiQl9AQAAfiCwFAa+x15xv8CBHsE34Xd18amd1J3M9mVyfbEpmqB8HPZkfru6GIiS2TAQGw+Wcytu7Lw/vsiW/g/nUggn1eSRjX0VWGseCQoj+ngZiTTJ6G5oBAWBBD6T6uT7oXoJVtrd2tAq7dnbEw9Tp0H16i/TyQcNehJbXzi33ynNX2rHLhC5MFVjvxC4ygheOC9cewo2n1ryCWhNjZ6O7s5MOIuy9vSEBqFB7xjMbOTtRIfydkdvzXrv7FH6uZtSSrC2KY4q1fVzKXH7s4dWl3rWRh1qDJAWpNtM70hExq4bCZ3MLIL/MKdkHZLD3zgnmYoeo/uuw1SHlcE/KcW7G7z2hLaZDTDdJhoZ5zNXHxwaKTA5dg8O7sYkly9DDxF5BpvrVvHkdOS+walXJyvwXue41Q==~0~-1~-1,39563,-1,-1,26067385,PiZtE,39621,54,0,-1-1,2,-94,-106,0,0-1,2,-94,-119,-1-1,2,-94,-122,0,0,0,0,1,0,0-1,2,-94,-123,-1,2,-94,-124,-1,2,-94,-126,-1,2,-94,-127,8-1,2,-94,-70,-1-1,2,-94,-80,94-1,2,-94,-116,144443124-1,2,-94,-118,129536-1,2,-94,-129,-1,2,-94,-121,;3;-1;0",
            "7a74G7m23Vrp0o5c9203151.7-1,2,-94,-100,Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:92.0) Gecko/20100101 Firefox/92.0,uaend,11059,20100101,ru-RU,Gecko,0,0,0,0,403115,3187573,1536,824,1536,864,1283,375,1294,,cpen:0,i1:0,dm:0,cwen:0,non:1,opc:0,fc:1,sc:0,wrc:1,isc:85.5999984741211,vib:1,bat:0,x11:0,x12:1,5557,0.955912671477,819181593786,0,loc:-1,2,-94,-101,do_en,dm_en,t_dis-1,2,-94,-105,0,-1,1,0,-1,324,0;0,-1,0,0,1779,1230,0;1,-1,0,0,2142,883,0;0,2,0,0,1809,1230,0;1,-1,0,0,2172,883,0;0,-1,0,0,937,937,0;0,-1,0,0,763,1062,0;0,-1,0,0,764,1063,0;0,-1,0,0,765,1064,0;0,-1,0,0,766,1065,0;0,-1,0,0,767,1066,0;0,-1,0,0,768,1067,0;0,-1,0,0,769,1068,0;0,-1,0,0,770,1069,0;0,-1,0,0,-1,678,0;1,-1,0,0,2432,883,0;0,-1,0,0,1303,648,0;0,-1,0,0,2187,1186,0;0,-1,0,0,1071,1490,0;0,-1,0,0,1103,1190,0;0,-1,1,0,1189,1011,0;0,-1,1,0,1738,1689,0;-1,2,-94,-102,0,-1,1,0,-1,324,0;0,-1,0,0,1779,1230,0;1,-1,0,0,2142,883,0;0,2,0,0,1809,1230,0;1,-1,0,0,2172,883,0;0,-1,0,0,937,937,0;0,-1,0,0,763,1062,0;0,-1,0,0,764,1063,0;0,-1,0,0,765,1064,0;0,-1,0,0,766,1065,0;0,-1,0,0,767,1066,0;0,-1,0,0,768,1067,0;0,-1,0,0,769,1068,0;0,-1,0,0,770,1069,0;0,-1,0,0,-1,678,0;1,-1,0,0,2432,883,0;0,-1,0,0,1303,648,0;0,-1,0,0,2187,1186,0;0,-1,0,0,1071,1490,0;0,-1,0,0,1103,1190,0;0,-1,1,0,1189,1011,0;0,-1,1,0,1738,1689,0;-1,2,-94,-108,-1,2,-94,-110,-1,2,-94,-117,-1,2,-94,-111,-1,2,-94,-109,-1,2,-94,-114,-1,2,-94,-103,-1,2,-94,-112,https://www.singaporeair.com/en_UK/us/home#/book/bookflight-1,2,-94,-115,1,32,32,0,0,0,0,2,0,1638363187572,-999999,17526,0,0,2921,0,0,6,0,0,753A3C698F792574A3AEB3DDDBDC9A0A~-1~YAAQN1oDF1771FF9AQAAeT8OdgaRVQ5DW/UV3GJvxXy0LuL7mOZaHwcy52MKDnuO3Tj4Iq6ySPOHqqhtbGbl7r/SyLUKLWdqHKhiRl2Q3TnHVUwA+PVV5FhWIKHVHTJQnvCLPzwXySGeVnMcucHuhBSFJeu7bO0CC5BUd4xJjm8ytbUS5RAlqytG53kIu9jJje98JNzqAry6uew1DmxmorPRSEdey0ckjL4f1CO46WvGjFOyMYEoCCB40ALh8NuvCEIaDgvwbZTq9YKjIJSfh/jzVHllfMYkHiZCTN4lPedtgswITly5164+8OctW81l7HVvmb5KelZMrRBcvvmQug4012jrfvlKVmFGfKTU+Sx+CUaHfJ1p2s8os5yUrKbdXSLRo6JLpynEj8sSzMWoDCY=~-1~-1~-1,37816,-1,-1,25543097,PiZtE,37744,22,0,-1-1,2,-94,-106,0,0-1,2,-94,-119,-1-1,2,-94,-122,0,0,0,0,1,0,0-1,2,-94,-123,-1,2,-94,-124,-1,2,-94,-126,-1,2,-94,-127,8-1,2,-94,-70,-1-1,2,-94,-80,94-1,2,-94,-116,86064093-1,2,-94,-118,127837-1,2,-94,-129,-1,2,-94,-121,;20;-1;0",
        ];

        $secondSensorData = [
            "7a74G7m23Vrp0o5c9298521.7-1,2,-94,-100,Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:92.0) Gecko/20100101 Firefox/92.0,uaend,11059,20100101,ru-RU,Gecko,0,0,0,0,402713,9629542,1536,824,1536,864,1536,347,1550,,cpen:0,i1:0,dm:0,cwen:0,non:1,opc:0,fc:1,sc:0,wrc:1,isc:84.80000305175781,vib:1,bat:0,x11:0,x12:1,5557,0.12981035764,818364814774,0,loc:-1,2,-94,-101,do_en,dm_en,t_dis-1,2,-94,-105,0,-1,1,0,-1,324,0;0,-1,0,0,1779,1230,0;1,-1,0,0,2142,883,0;0,2,0,0,1809,1230,0;1,-1,0,0,2172,883,0;0,-1,0,0,937,937,0;0,-1,0,0,763,1062,0;0,-1,0,0,764,1063,0;0,-1,0,0,765,1064,0;0,-1,0,0,766,1065,0;0,-1,0,0,767,1066,0;0,-1,0,0,768,1067,0;0,-1,0,0,769,1068,0;0,-1,0,0,770,1069,0;0,-1,0,0,-1,678,0;1,-1,0,0,2432,883,0;0,-1,0,0,1303,648,0;0,-1,0,0,2187,1186,0;0,-1,0,0,1071,1490,0;0,-1,0,0,1103,1190,0;0,-1,1,0,1189,1011,0;0,-1,1,0,1738,1689,0;-1,2,-94,-102,0,-1,1,0,-1,324,0;0,-1,0,0,1779,1230,0;1,-1,0,0,2142,883,0;0,2,0,0,1809,1230,0;1,-1,0,0,2172,883,0;0,-1,0,0,937,937,0;0,-1,0,0,763,1062,0;0,-1,0,0,764,1063,0;0,-1,0,0,765,1064,0;0,-1,0,0,766,1065,0;0,-1,0,0,767,1066,0;0,-1,0,0,768,1067,0;0,-1,0,0,769,1068,0;0,-1,0,0,770,1069,0;0,-1,0,0,-1,678,0;1,-1,0,0,2432,883,0;0,-1,0,0,1303,648,0;0,-1,0,0,2187,1186,0;0,-1,0,0,1071,1490,0;0,-1,0,0,1103,1190,0;0,-1,1,0,1189,1011,0;0,-1,1,0,1738,1689,0;0,-1,0,0,788,788,0;-1,2,-94,-108,-1,2,-94,-110,-1,2,-94,-117,-1,2,-94,-111,-1,2,-94,-109,-1,2,-94,-114,-1,2,-94,-103,-1,2,-94,-112,https://www.singaporeair.com/en_UK/sg/home#/book/bookflight-1,2,-94,-115,1,32,32,0,0,0,0,1018,0,1636729629548,10,17509,0,0,2918,0,0,1018,0,0,0E4988E903B90744D161E867DC87A147~-1~YAAQlwVJF6oqiQl9AQAAfiCwFAa+x15xv8CBHsE34Xd18amd1J3M9mVyfbEpmqB8HPZkfru6GIiS2TAQGw+Wcytu7Lw/vsiW/g/nUggn1eSRjX0VWGseCQoj+ngZiTTJ6G5oBAWBBD6T6uT7oXoJVtrd2tAq7dnbEw9Tp0H16i/TyQcNehJbXzi33ynNX2rHLhC5MFVjvxC4ygheOC9cewo2n1ryCWhNjZ6O7s5MOIuy9vSEBqFB7xjMbOTtRIfydkdvzXrv7FH6uZtSSrC2KY4q1fVzKXH7s4dWl3rWRh1qDJAWpNtM70hExq4bCZ3MLIL/MKdkHZLD3zgnmYoeo/uuw1SHlcE/KcW7G7z2hLaZDTDdJhoZ5zNXHxwaKTA5dg8O7sYkly9DDxF5BpvrVvHkdOS+walXJyvwXue41Q==~0~-1~-1,39563,694,-545810275,26067385,PiZtE,14311,23,0,-1-1,2,-94,-106,9,1-1,2,-94,-119,0,0,0,0,0,200,0,0,200,0,0,0,0,0,-1,2,-94,-122,0,0,0,0,1,0,0-1,2,-94,-123,-1,2,-94,-124,-1,2,-94,-126,-1,2,-94,-127,11133333331333333333-1,2,-94,-70,1436327638;1247333925;dis;;true;true;true;-180;true;24;24;true;false;unspecified-1,2,-94,-80,6461-1,2,-94,-116,144443124-1,2,-94,-118,133294-1,2,-94,-129,5bcc2979186a30ddc6859e7fea6e834a62b2c5aa4a926535706369893555dcd8,1.25,0aff22edcc367b46cca4c87eaa3a9207ba86a7fb36f3574a2dade646c5d0b2dd,Google Inc.,ANGLE (Intel(R) HD Graphics 400 Direct3D11 vs_5_0 ps_5_0),2669885e0376ed57265f041bfbd5a2174ca5b7a1d3bc28048453faef8450e0ef,26-1,2,-94,-121,;30;6;0",
            "7a74G7m23Vrp0o5c9203151.7-1,2,-94,-100,Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:92.0) Gecko/20100101 Firefox/92.0,uaend,11059,20100101,ru-RU,Gecko,0,0,0,0,403115,3187573,1536,824,1536,864,1283,375,1294,,cpen:0,i1:0,dm:0,cwen:0,non:1,opc:0,fc:1,sc:0,wrc:1,isc:85.5999984741211,vib:1,bat:0,x11:0,x12:1,5557,0.285972382142,819181593786,0,loc:-1,2,-94,-101,do_en,dm_en,t_dis-1,2,-94,-105,0,-1,1,0,-1,324,0;0,-1,0,0,1779,1230,0;1,-1,0,0,2142,883,0;0,2,0,0,1809,1230,0;1,-1,0,0,2172,883,0;0,-1,0,0,937,937,0;0,-1,0,0,763,1062,0;0,-1,0,0,764,1063,0;0,-1,0,0,765,1064,0;0,-1,0,0,766,1065,0;0,-1,0,0,767,1066,0;0,-1,0,0,768,1067,0;0,-1,0,0,769,1068,0;0,-1,0,0,770,1069,0;0,-1,0,0,-1,678,0;1,-1,0,0,2432,883,0;0,-1,0,0,1303,648,0;0,-1,0,0,2187,1186,0;0,-1,0,0,1071,1490,0;0,-1,0,0,1103,1190,0;0,-1,1,0,1189,1011,0;0,-1,1,0,1738,1689,0;-1,2,-94,-102,0,-1,1,0,-1,324,0;0,-1,0,0,1779,1230,0;1,-1,0,0,2142,883,0;0,2,0,0,1809,1230,0;1,-1,0,0,2172,883,0;0,-1,0,0,937,937,0;0,-1,0,0,763,1062,0;0,-1,0,0,764,1063,0;0,-1,0,0,765,1064,0;0,-1,0,0,766,1065,0;0,-1,0,0,767,1066,0;0,-1,0,0,768,1067,0;0,-1,0,0,769,1068,0;0,-1,0,0,770,1069,0;0,-1,0,0,-1,678,0;1,-1,0,0,2432,883,0;0,-1,1,0,1303,648,0;0,-1,0,0,2187,1186,0;0,-1,0,0,1071,1490,0;0,-1,0,0,1103,1190,0;0,-1,1,0,1189,1011,0;0,-1,1,0,1738,1689,0;0,-1,0,0,788,788,0;-1,2,-94,-108,-1,2,-94,-110,-1,2,-94,-117,-1,2,-94,-111,-1,2,-94,-109,-1,2,-94,-114,-1,2,-94,-103,-1,2,-94,-112,https://www.singaporeair.com/en_UK/us/home#/book/bookflight-1,2,-94,-115,1,32,32,0,0,0,0,1536,0,1638363187572,13,17526,0,0,2921,0,0,1537,0,0,753A3C698F792574A3AEB3DDDBDC9A0A~-1~YAAQN1oDF7X71FF9AQAAJEgOdgaDXSqzpDjyQ2mX01ghWLpL59gzCgzDTYn63fI/8Pn3hp75pfT9u8erimL/LtHYMuCI+bNTkVKrwMkg8OYsMlekq+q20xpyhUF4WVhMP+vAjZgnwmEMbuhf+hX4jVlX8xzujbdZIR7v8FT9UXDEg8G9pLPEQT+4TBItliifk0YxHm8IgZ21BrkZKTssoJtpge6jSW82A8FJGL+xQYkcuCa325E3Y2/2SBIAlqyeUxYmm85f1S3wfMNbKzjj4Ru5/++CNu/q4puW1hMx2gpnGirMzYiAAvdjacnsBRPUjSlNN/Y8kRrcYXnL8mPtVFp/DaYwlz0XCPln/+F7PAJuwm51wBYg1Fjr1QUJyEX/moVWFZH/rq5cl0Gdu/754iw=~-1~||1-EqKQIVrKuO-1-10-1000-2||~-1,39446,681,-440630286,25543097,PiZtE,76912,34,0,-1-1,2,-94,-106,9,1-1,2,-94,-119,0,0,0,0,0,0,0,0,0,0,0,0,0,200,-1,2,-94,-122,0,0,0,0,1,0,0-1,2,-94,-123,-1,2,-94,-124,-1,2,-94,-126,-1,2,-94,-127,11133333331333333333-1,2,-94,-70,1436327638;1247333925;dis;;true;true;true;-180;true;24;24;true;false;1-1,2,-94,-80,5343-1,2,-94,-116,86064093-1,2,-94,-118,133120-1,2,-94,-129,5bcc2979186a30ddc6859e7fea6e834a62b2c5aa4a926535706369893555dcd8,1.25,0,Google Inc.,ANGLE (Intel(R) HD Graphics 400 Direct3D11 vs_5_0 ps_5_0),2669885e0376ed57265f041bfbd5a2174ca5b7a1d3bc28048453faef8450e0ef,26-1,2,-94,-121,;34;24;0",
        ];

        if (count($sensorData) != count($secondSensorData)) {
            $this->logger->error("wrong sensor data values");

            return false;
        }

        $key = array_rand($sensorData);
        $this->logger->notice("key: {$key}");

        $form = $this->http->Form;
        $formURL = $this->http->FormURL;

        $this->http->RetryCount = 0;
        $sensorDataHeaders = [
            "Accept"       => "*/*",
            "Origin"       => "https://www.singaporeair.com",
        ];
        $sensorData = [
            'sensor_data' => $sensorData[$key],
        ];
        $this->http->PostURL($sensorPostUrl, json_encode($sensorData), $sensorDataHeaders);
        $this->http->JsonLog();
        sleep(1);
        $sensorData = [
            'sensor_data' => $secondSensorData[$key],
        ];
        $this->http->PostURL($sensorPostUrl, json_encode($sensorData), $sensorDataHeaders);
        $this->http->JsonLog();
        sleep(1);
        $this->http->RetryCount = 2;

        $this->http->Form = $form;
        $this->http->FormURL = $formURL;

        return true;
    }

    public function parseRewardFlights($flightSearch, array $fields)
    {
        $this->logger->notice(__METHOD__);

        if (!isset($flightSearch->response->data->flights) || !is_array($flightSearch->response->data->flights)) {
            $this->logger->error('flightSearch->response->data->flights empty');

            throw new \CheckRetryNeededException(5, 0);
        }

        $cabins = $this->getCabinFields(false);
        $cabin = null;

        if (!isset($flightSearch->response->data->miles->cabinClass) || !isset($cabins[$flightSearch->response->data->miles->cabinClass])) {
            $this->sendNotification('RA Cabin empty // MI');

            throw new \CheckException('Cabin empty', ACCOUNT_ENGINE_ERROR);
        }
        $cabin = $cabins[$flightSearch->response->data->miles->cabinClass];
        $routes = [];
        $cntRoutes = count($flightSearch->response->data->flights);

        foreach ($flightSearch->response->data->flights as $item) {
            if ($item->originAirportCode != $fields['DepCode']) {
                $this->logger->notice("Skip: return flight {$item->originAirportCode} -> {$item->destinationAirportCode}");

                if (isset($item->nextCabinClass, $item->nextCabinClass->messageTitle)
                    && strpos($item->nextCabinClass->messageTitle, 'There are no available') === 0) {
                    $this->warningMessage = $item->nextCabinClass->messageTitle;
                }

                continue;
            }

            foreach ($item->segments as $numSeg => $segment) {
                $stop = $segment->numberOfStops ?? 0;

                foreach ($flightSearch->response->data->miles->sellingClass as $sellingClass) {
                    $this->http->RetryCount = 0;
                    $headers = [
                        'Accept'       => 'application/json',
                        'Content-Type' => 'application/json; charset=UTF-8',
                    ];
                    $data = '{"selectedOnwardSegments":[{"sellingClass":"' . $sellingClass->code . '","sellingClassDescription":"' . $sellingClass->description . '","segmentId":' . $segment->segmentId . ',"depatureDateTime":"' . $segment->departureDateTime . '","waitListed":false,"originAirportCode":"' . $segment->originAirportCode . '","destinationAirportCode":"' . $segment->destinationAirportCode . '"}],"selectedReturnSegments":[]}';
                    $this->http->PostURL('https://www.singaporeair.com/redemption/getFare.form', $data, $headers, 20);
                    $fare = $this->http->JsonLog();
                    $parseWithoutTaxes = false;

                    if (!isset($fare->bookingSummary->flights[0])
                        || !isset($fare->bookingSummary->flights[0]->flightSegments[0])
                        || !isset($fare->bookingSummary->flights[0]->flightSegments[0]->waitList)
                    ) {
                        if ($cntRoutes < 5 && $this->attempt == 0) {
                            throw new \CheckRetryNeededException(5, 0);
                        }
                        sleep(2);
                        $this->http->PostURL('https://www.singaporeair.com/redemption/getFare.form', $data, $headers,
                            20);
                        $fare = $this->http->JsonLog();

                        if (!isset($fare->bookingSummary->flights[0])
                            || !isset($fare->bookingSummary->flights[0]->flightSegments[0])
                            || !isset($fare->bookingSummary->flights[0]->flightSegments[0]->waitList)
                        ) {
                            sleep(2);
                            $this->http->PostURL('https://www.singaporeair.com/redemption/getFare.form', $data, $headers,
                                20);
                            $fare = $this->http->JsonLog();

                            if (!isset($fare->bookingSummary->flights[0])
                                || !isset($fare->bookingSummary->flights[0]->flightSegments[0])
                                || !isset($fare->bookingSummary->flights[0]->flightSegments[0]->waitList)
                            ) {
                                $parseWithoutTaxes = true;
                            }
                        }
                    }

                    if (!isset($fare->bookingSummary)) {
                        if (isset($fare->status) && $fare->status === 'FAILURE'
                            && isset($fare->errorMessageList[0])
                            && strpos($fare->errorMessageList[0]->wrapperDescription,
                                'We cannot process your request right now. Please try again later') !== false
                        ) {
                            $this->warningMessages[] = $fare->errorMessageList[0]->wrapperDescription;
                        }
                        $this->warningMessageNoTaxes = true;
                        $parseWithoutTaxes = true;
                    }

                    if (!$parseWithoutTaxes && $fare->bookingSummary->flights[0]->flightSegments[0]->waitList === true) {
                        $this->logger->error('skip fare - waitList');
                        $hasWaitList = true;

                        continue;
                    }

                    if ($parseWithoutTaxes) {
                        $miles = $sellingClass->miles;
                        $taxes = null;
                    } else {
                        $miles = preg_replace('/[^\d]+/', '', $sellingClass->miles);
                        $taxes = round($fare->bookingSummary->taxTotal / $fare->bookingSummary->adultCount, 2);
                    }
                    $this->http->RetryCount = 2;
                    $headData = [
                        //'distance' => null,
                        'redemptions' => [
                            'miles'   => $miles,
                            'program' => $this->AccountFields['ProviderCode'],
                        ],
                        'payments' => [
                            'currency' => $fare->bookingSummary->currency ?? $this->requestCurrency,
                            'taxes'    => $taxes,
                            'fees'     => null,
                        ],
                        'award_type'     => $sellingClass->description,
                        'classOfService' => $flightSearch->response->data->miles->description ?? null,
                    ];

                    if (!$this->defaultCurrency && isset($fare->bookingSummary)) {
                        $this->defaultCurrency = $fare->bookingSummary->currency;
                    }

                    $connections = [];
                    $seats = 0;

                    foreach ($segment->legs as $numLeg => $leg) {
                        $seatsRes = $this->inSellingClass($sellingClass->code, $leg);

                        if ($seatsRes === false) {
                            $this->logger->notice("Skip: price empty {$item->originAirportCode} -> {$item->destinationAirportCode} : {$sellingClass->description}");

                            continue 2;
                        }

                        $seats += $seatsRes;
                        $depTime = strtotime($leg->departureDateTime);
                        $arrTime = strtotime($leg->arrivalDateTime);
                        $flight = "{$leg->carrierCode}{$leg->flightNumber}";

//                        $aircraft = $flightSearch->response->dictionary->aircraft->{$leg->aircraftCode} ?? $leg->aircraftCode;
                        $aircraft = $leg->aircraftCode;
                        $connections[] = [
                            'departure' => [
                                'date'     => date('Y-m-d H:i', $depTime),
                                'dateTime' => $depTime,
                                'airport'  => $leg->originAirportCode,
                            ],
                            'arrival' => [
                                'date'     => date('Y-m-d H:i', $arrTime),
                                'dateTime' => $arrTime,
                                'airport'  => $leg->destinationAirportCode,
                            ],
                            //'meal' => null,
                            'cabin'      => $cabin,
                            'fare_class' => $flightSearch->response->data->miles->cabinClass,
                            'flight'     => [$flight],
                            'airline'    => $leg->carrierCode,
                            'operator'   => $leg->carrierCode,
                            //'distance' => null,
                            'aircraft' => $aircraft,
                            'times'    => [
                                'flight'  => $this->formatTimes($leg->flightDuration),
                                'layover' => $this->formatTimes($leg->layoverDuration),
                            ],
                        ];
                    }

                    $headData['connections'] = $connections;
                    $headData += [
                        //'tickets' => $seats,
                        'num_stops' => $stop,
                        'times'     => $this->sumTimes($connections),
                    ];
                    $routes[] = $headData;
                    $this->logger->debug('Parsed data:');
                    $this->logger->debug(var_export($headData, true), ['pre' => true]);
                }
            }
        }

        // try to get warning text
        if (empty($routes) && !empty($flightSearch->response->data->flights)) {
            foreach ($flightSearch->response->data->flights as $item) {
                if ($item->originAirportCode != $fields['DepCode']) {
                    $this->logger->notice("Skip: return flight {$item->originAirportCode} -> {$item->destinationAirportCode}");

                    if (isset($item->nextCabinClass, $item->nextCabinClass->messageTitle)
                        && strpos($item->nextCabinClass->messageTitle, 'There are no available') === 0) {
                        $this->warningMessage = $item->nextCabinClass->messageTitle;
                    }
                    $skipped = true;

                    continue;
                }

                if (isset($item->nextCabinClass, $item->nextCabinClass->messageTitle)) {
                    $this->warningMessages[] = $item->nextCabinClass->messageTitle;
                    $this->logger->error($item->nextCabinClass->messageTitle);
                }
            }
        }

        if (isset($skipped) && empty($this->warningMessages) && empty($this->warningMessage)) {
            $this->warningMessage = 'There are no available seats for redemption.';
        }

        if (empty($routes) && empty($this->warningMessages) && empty($this->warningMessage) && isset($hasWaitList)) {
            $this->warningMessage = 'There are no available seats for redemption. Only waitList';
        }

        return $routes;
    }

    protected function parseReCaptchaDistil($retry)
    {
        $this->logger->notice(__METHOD__);
        $key =
            $this->http->FindSingleNode("//form[@id = 'distilCaptchaForm']//div[@class = 'g-recaptcha']/@data-sitekey")
            ?? $this->http->FindSingleNode("//iframe[@id = 'sec-cpt-if']/@data-key")
        ;
        $this->logger->debug("data-sitekey: {$key}");

        if (!$key) {
            return false;
        }

        // watchdog workaround
        $this->increaseTimeLimit(180);

        $recognizer = $this->getCaptchaRecognizer(self::CAPTCHA_RECOGNIZER_ANTIGATE_API_V2);
        $recognizer->RecognizeTimeout = 120;
        $parameters = [
            "type"         => "RecaptchaV2TaskProxyless",
            "websiteURL"   => $this->http->currentUrl(),
            "websiteKey"   => $key,
            "apiDomain"    => "www.recaptcha.net",
        ];

        return $this->recognizeAntiCaptcha($recognizer, $parameters);
    }

    protected function parseReCaptchaItinerary($key)
    {
        $this->logger->notice(__METHOD__);
        $this->logger->debug("data-sitekey: {$key}");

        if (!$key) {
            return false;
        }

        // watchdog workaround
        $this->increaseTimeLimit(180);

        $recognizer = $this->getCaptchaRecognizer(self::CAPTCHA_RECOGNIZER_ANTIGATE_API_V2);
        $recognizer->RecognizeTimeout = 120;
        $parameters = [
            "type"         => "RecaptchaV2TaskProxyless",
            "websiteURL"   => $this->http->currentUrl(),
            "websiteKey"   => $key,
        ];
        $captcha = $this->recognizeAntiCaptcha($recognizer, $parameters);

        return $captcha;
    }

    private function getCabinFields($flip = true): array
    {
        $cabins = [
            'Y' => 'economy',
            'S' => 'premiumEconomy',
            'J' => 'business',
            'F' => 'firstClass',
        ];

        if ($flip) {
            return array_flip($cabins);
        }

        return $cabins;
    }

    private function inSellingClass($code, $leg)
    {
        foreach ($leg->cabinClassAvailability as $legSellingClass) {
            if ($code == $legSellingClass->sellingClass) {
                return $legSellingClass->numberOfSeats ?? true;
            }
        }

        return false;
    }

    private function aircraftInFareLegs($legId, $segId, $fare)
    {
        foreach ($fare->bookingSummary->flights[0]->flightSegments as $segment) {
            if ($segId == $segment->segmentId) {
                foreach ($segment->legs as $leg) {
                    if ($legId == $leg->legId) {
                        return $leg->aircraftTypeCode ?? null;
                    }
                }
            }
        }

        return null;
    }

    private function formatTimes($milliseconds)
    {
        $seconds = floor($milliseconds / 1000);
        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);
        $minutes = $minutes % 60;

        return ($hours + $minutes > 0) ? sprintf('%02d:%02d', $hours, $minutes) : null;
    }

    private function sumTimes($connections)
    {
        $minutesFlight = $minutesLayover = 0;

        foreach ($connections as $value) {
            if (isset($value['times']['flight'])) {
                [$hour, $minute] = explode(':', $value['times']['flight']);
                $minutesFlight += $hour * 60;
                $minutesFlight += $minute;
                /*
                if (isset($value['times']['layover'])) {
                    list($hour, $minute) = explode(':', $value['times']['layover']);
                    $minutesFlight += $hour * 60;
                    $minutesFlight += $minute;
                }
                */
            }

            if (isset($value['times']['layover'])) {
                [$hour, $minute] = explode(':', $value['times']['layover']);
                $minutesLayover += $hour * 60;
                $minutesLayover += $minute;
            }
        }
        $hoursFlight = floor($minutesFlight / 60);
        $minutesFlight -= floor($minutesFlight / 60) * 60;
        $hoursLayover = floor($minutesLayover / 60);
        $minutesLayover -= floor($minutesLayover / 60) * 60;

        return [
            'flight'  => sprintf('%02d:%02d', $hoursFlight, $minutesFlight),
            'layover' => ($hoursLayover + $minutesLayover > 0) ?
                sprintf('%02d:%02d', $hoursLayover, $minutesLayover) : null,
        ];
    }
}
