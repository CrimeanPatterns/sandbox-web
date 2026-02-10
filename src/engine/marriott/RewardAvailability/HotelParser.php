<?php

namespace AwardWallet\Engine\marriott\RewardAvailability;

use AwardWallet\Engine\ProxyList;
use AwardWallet\Engine\Settings;

class HotelParser extends \TAccountChecker
{
    use ProxyList;
    use \SeleniumCheckerHelper;

    private const SIGNATURE_NUMBER = "fd3d0f8c30825e1af6e0eccbc879a77b5c2dd7c6cb5797502e9ac4dd27264f8b";

    // TODO переписать на селениум - нет смысла в пробросах - только сложности с переменными
    private $fields;
    private $currentUrl;
    private $xRequestId;
    private $signature;
    private $skippedCache = 0;

    public static function getRASearchLinks(): array
    {
        return ['https://www.marriott.com/default.mi' => 'search page'];
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->http->setDefaultHeader("Accept-Encoding", "gzip, deflate, br");
        $this->http->setHttp2(true);
        $this->setProxyBrightData(null, Settings::RA_ZONE_STATIC);
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

    public function ParseRewardAvailability(array $fields)
    {
        $this->logger->info("Parse Reward Availability", ['Header' => 2]);
        $this->logger->debug("params: " . var_export($fields, true));
        $this->logger->notice(__METHOD__);

        if ($fields['Rooms'] > 3) {
            $this->SetWarning('You can reserve a maximum of three rooms at a time.');

            return ['hotels' => []];
        }

        if (($fields['Adults'] + $fields['Kids']) > 8) {
            $this->SetWarning('Maximum: 8 total guests');

            return ['hotels' => []];
        }

        if ($fields['CheckOut'] == $fields['CheckIn']) {
            $this->SetWarning('You can’t book a day-use room.');

            return ['hotels' => []];
        }

        $this->fields = $fields;

        $this->fields['Nights'] = ($fields['CheckOut'] - $fields['CheckIn']) / 24 / 60 / 60;
        $this->logger->debug('Nights: ' . $this->fields['Nights']);

        /** Получаем данные и cookies из браузера */
        $data = $this->selenium();

        return ['hotels' => $data];
    }

    private function selenium()
    {
        $this->logger->notice(__METHOD__);
        $selenium = clone $this;
        $this->http->brotherBrowser($selenium->http);

        try {
            $this->logger->notice("Running Selenium...");
            $selenium->UseSelenium();
            $selenium->useGoogleChrome(\SeleniumFinderRequest::CHROME_99);
//            $selenium->useFirefox(\SeleniumFinderRequest::FIREFOX_100);
            $selenium->disableImages();
            $selenium->seleniumOptions->recordRequests = true;
            $selenium->http->saveScreenshots = true;

            $resolutions = [
                [1152, 864],
                [1280, 720],
                [1280, 768],
                [1280, 800],
                [1360, 768],
                [1366, 768],
                [1920, 1080],
            ];

            $resolution = $resolutions[array_rand($resolutions)];
            $selenium->setScreenResolution($resolution);
            $selenium->http->start();
            $selenium->Start();

            $selenium->http->GetURL('https://www.marriott.com/default.mi');

            if ($selenium->http->Response['code'] == 403) {
                throw new \CheckRetryNeededException(5, 0);
            }
            $this->catchFetchPlacesQuery($selenium);

            $destination = $selenium->waitForElement(\WebDriverBy::xpath("//input[@id='downshift-0-input']"), 10);

            if (!$destination) {
                $this->logger->error("page not load");

                throw new \CheckRetryNeededException(5, 0);
            }
            $destination->sendKeys($this->fields['Destination']);

            sleep(2);
            $res = trim($this->getArgFetchPlacesQuery($selenium), "'");
            $res = $this->http->JsonLog($res, 1, true);

            if (!isset($res['headers']['x-request-id'], $res['headers']['graphql-operation-signature'])) {
                $this->logger->error('no data');

                throw new \CheckRetryNeededException(5, 0);
            }

            $dataPlace = $this->runFetchPlacesQuery($selenium, $res['headers']['x-request-id'],
                $res['headers']['graphql-operation-signature']);

            if (!isset($dataPlace['data']['suggestedPlaces']['edges'][0]['node']['placeId'])) {
                $this->logger->error("new place format");

                throw new \CheckException('something went wrong', ACCOUNT_ENGINE_ERROR);
            }

            $query = $this->makeSearchStringByGeo($dataPlace['data']['suggestedPlaces']['edges'][0]['node']['placeId']);
            $url = 'https://www.marriott.com/search/findHotels.mi?' . $query;

            $selenium->http->GetURL($url);

            $selenium->waitForElement(\WebDriverBy::xpath("
                (//div[normalize-space(@class)='property-card'])[1]
                | //div[@id='m-alert-inline-sub-content']
                | //div[normalize-space(text())='Our server is being stubborn, please try again']
            "), 45);
            $selenium->saveResponse();

            $this->catchFetchRatesByGeoQuery($selenium, $url);

            $selenium->waitForElement(\WebDriverBy::xpath("
                (//div[normalize-space(@class)='property-card'])[1]
                | //div[@id='m-alert-inline-sub-content']
                | //div[normalize-space(text())='Our server is being stubborn, please try again']
            "), 45);
            $selenium->saveResponse();

            if ($alert =
                $selenium->waitForElement(\WebDriverBy::xpath("//div[@id='m-alert-inline-sub-content']"), 0)
            ) {
                $this->SetWarning($alert->getText());

                return [];
            }

            if ($alert =
                $selenium->waitForElement(\WebDriverBy::xpath("//div[normalize-space(text())='Our server is being stubborn, please try again']"),
                    0)
            ) {
                throw new \CheckException($alert->getText(), ACCOUNT_PROVIDER_ERROR);
            }

            $this->logger->debug('arguments for new request');
            $res = trim($this->getArgFetchRatesByGeoQuery($selenium), "'");
            $resArg = $this->http->JsonLog($res, 1, true);

            $this->logger->debug('arguments for new request');
            $res = trim($this->getFetchRatesByGeoQuery($selenium), "'");
            $resDataMin = $this->http->JsonLog($res, 1, true);

            $total = $resDataMin['data']['searchLowestAvailableRatesByGeolocation']['total'] ?? 0;
            $resData = null;
            // TODO - body  некорректно идут \n - надо доработать
//            if ($total>count($resDataMin['data']['searchLowestAvailableRatesByGeolocation']['edges'])) {
//                $resData = $this->runFetchRatesByGeoQuery(
//                    $selenium,
//                    $resArg['headers'],
//                    preg_replace('/(\\"limit\\":)(\d+)(,)/','$1'.$total.'$2',$resArg['body'])
//                );
//            }
            if (!isset($resData)) {
                $resData = $resDataMin;
            }

            $this->currentUrl = $selenium->http->currentUrl();
            $this->xRequestId = $resArg['headers']['x-request-id'];
            $this->signature = $resArg['headers']['graphql-operation-signature'];

            $resData = $this->parseDataJson($selenium, $resData, $resArg['headers']);

            $cookies = $selenium->driver->manage()->getCookies();

            foreach ($cookies as $cookie) {
                $this->http->setCookie($cookie['name'], $cookie['value'], $cookie['domain'], $cookie['path'],
                    $cookie['expiry'] ?? null);
            }
        } catch (
        \WebDriverCurlException
        |\WebDriverException
        |\Facebook\WebDriver\Exception\WebDriverCurlException
        |\Facebook\WebDriver\Exception\WebDriverException $e
        ) {
            $this->logger->error($e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        } finally {
            // close Selenium browser
            $selenium->http->cleanup();
        }

        return $resData;
    }

    private function runFetchPlacesQuery($selenium, $xRequestId, $signature)
    {
        $this->logger->notice(__METHOD__);
        $body = addslashes('{"operationName":"phoenixShopSuggestedPlacesQuery","variables":{"query":"' . $this->fields['Destination'] . '"},"query":"query phoenixShopSuggestedPlacesQuery($query: String!) {\n  suggestedPlaces(query: $query) {\n    edges {\n      node {\n        placeId\n        description\n        primaryDescription\n        secondaryDescription\n        __typename\n      }\n      __typename\n    }\n    total\n    __typename\n  }\n}\n"}');
        $script = '
            fetch("https://www.marriott.com/mi/query/phoenixShopSuggestedPlacesQuery", {
                "credentials": "include",
                "headers": {
                    "Accept": "*/*",
                    "Accept-Language": "en-US",
                    "content-type": "application/json",
                    "apollographql-client-name": "phoenix_homepage",
                    "apollographql-client-version": "v1",
                    "x-request-id": "' . $xRequestId . '",
                    "application-name": "homepage",
                    "graphql-require-safelisting": "true",
                    "graphql-operation-signature": "' . $signature . '"
                },
                "referrer": "https://www.marriott.com/default.mi",
                "body": "' . $body . '",
                "method": "POST",
                "mode": "cors"
            })
                .then( response => response.json())
                .then( result => {
                    let script = document.createElement("script");
                    let id = "placesQuery";
                    script.id = id;            
                    script.setAttribute(id, JSON.stringify(result));
                    document.querySelector("body").append(script);
                })
        ;';
        $this->logger->info($script, ['pre' => true]);
        $selenium->driver->executeScript($script);
        $this->savePageToLogs($selenium);

        $placesQuery = $selenium->waitForElement(\WebDriverBy::xpath('//script[@id="placesQuery"]'), 20, false);

        if (!$placesQuery) {
            $this->savePageToLogs($selenium);

            throw new \CheckRetryNeededException(5, 0);
        }
        $placesQuery = $placesQuery->getAttribute("placesQuery");
        $placesQuery = htmlspecialchars_decode($placesQuery);

        return $this->http->JsonLog($placesQuery, 1, true);
    }

    private function catchFetchPlacesQuery($selenium)
    {
        $this->logger->notice(__METHOD__);

        $selenium->driver->executeScript(/** @lang JavaScript */
            '
            const constantMock = window.fetch;
            window.fetch = function() {
                console.log(arguments);
                if (arguments[0]==="/mi/query/phoenixShopSuggestedPlacesQuery") {
                    localStorage.setItem("phoenixShopSuggestedPlacesQuery", JSON.stringify(arguments[1]));
                }
                return new Promise((resolve, reject) => {
                    constantMock.apply(this, arguments)
                        .then((response) => {
                            resolve(response);
                        })
                        .catch((error) => {
                            reject(response);
                        })
                });
            }
            '
        );
    }

    private function getArgFetchPlacesQuery($selenium)
    {
        $this->logger->notice(__METHOD__);

        return $selenium->driver->executeScript(/** @lang JavaScript */
            'return localStorage.getItem("phoenixShopSuggestedPlacesQuery");'
        );
    }

    private function catchFetchRatesByGeoQuery(self $selenium, $url)
    {
        $this->logger->notice(__METHOD__);

        $selenium->driver->executeScript(
            $script = /** @lang JavaScript */ '
            const constantMock = window.fetch;
            window.fetch = function() {
                console.log(arguments);
                if (arguments[0]==="/mi/query/phoenixShopLowestAvailableRatesByGeoQuery") {
                    localStorage.setItem("phoenixShopLowestAvailableRatesByGeoQuery", JSON.stringify(arguments[1]));
                }
                return new Promise((resolve, reject) => {
                    constantMock.apply(this, arguments)
                        .then((response) => {
                            if(response.url.indexOf("mi/query/phoenixShopLowestAvailableRatesByGeoQuery") > -1) {
                                response
                                 .clone()
                                 .json()
                                 .then(body => {
                                     localStorage.setItem("LowestAvailableRatesByGeoQuery", JSON.stringify(body))
                                 });
                            }
                            
                            resolve(response);
                        })
                        .catch((error) => {
                            reject(response);
                        })
                });
            }
            '
        );
        $this->logger->debug($script, ['pre' => true]);
        $this->savePageToLogs($selenium);

        if ($this->http->FindSingleNode("//label[contains(text(),'Use Points/Certificates')]/preceding-sibling::*[1][self::input]/@value") != 'true') {
            $selenium->waitForElement(\WebDriverBy::xpath("//label[contains(text(),'Use Points/Certificates')]"),
                0)->click();
        }
        $this->savePageToLogs($selenium);
        $this->logger->debug("use point: " . $this->http->FindSingleNode("//label[contains(text(),'Use Points/Certificates')]/preceding-sibling::*[1][self::input]/@value"));
        $selenium->waitForElement(\WebDriverBy::xpath("//span[normalize-space()='Update Search']/ancestor::*[1][self::button]"),
            0)->click();
    }

    private function runFetchRatesByGeoQuery(self $selenium, $headers, $body)
    {
        $this->logger->notice(__METHOD__);
        $headers = json_encode($headers);

        $script = /** @lang JavaScript */
            '
            fetch("https://www.marriott.com/mi/query/phoenixShopLowestAvailableRatesByGeoQuery", {
                "credentials": "include",
                "headers": ' . $headers . ',
                "referrer": "' . $selenium->http->currentUrl() . '",
                "body": \'' . $body . '\',
                "method": "POST",
                "mode": "cors"
            })
                .then( response => response.json())
                .then( result => {
                    let script = document.createElement("script");
                    let id = "destinationRA";
                    script.id = id;
                    script.setAttribute(id, JSON.stringify(result));
                    document.querySelector("body").append(script);
                })
            ;
            ';
        $this->logger->info($script, ['pre' => true]);

        throw new \CheckRetryNeededException(5, 0);
        $selenium->driver->executeScript($script);
        $destinationRA = $this->waitForElement(\WebDriverBy::xpath('//script[@id="destinationRA"]'), 10, false);
        $this->saveResponse();

        if (!$destinationRA) {
            throw new \CheckRetryNeededException(5, 0);
        }

        return $this->http->JsonLog($destinationRA->getAttribute("destinationRA"), 1, true);
    }

    private function runFetchPropertyInfoCall(self $selenium, $id, $xRequestId)
    {
        $this->logger->notice(__METHOD__);

        $body = addslashes('{"operationName":"phoenixShopPropertyInfoCall","variables":{"propertyId":"' . $id . '","filter":"PHONE","descriptionsFilter":["LOCATION","RESORT_FEE_DESCRIPTION","DESTINATION_FEE_DESCRIPTION"]},"query":"query phoenixShopPropertyInfoCall($propertyId: ID!, $filter: [ContactNumberType], $descriptionsFilter: [PropertyDescriptionType]) {\n  property(id: $propertyId) {\n    id\n    basicInformation {\n      name\n      latitude\n      longitude\n      isAdultsOnly\n      isMax\n      brand {\n        id\n        __typename\n      }\n      openingDate\n      bookable\n      resort\n      descriptions(filter: $descriptionsFilter) {\n        text\n        type {\n          code\n          label\n          description\n          __typename\n        }\n        localizedText {\n          sourceText\n          translatedText\n          __typename\n        }\n        __typename\n      }\n      hasUniquePropertyLogo\n      nameInDefaultLanguage\n      __typename\n    }\n    contactInformation {\n      address {\n        line1\n        city\n        postalCode\n        stateProvince {\n          label\n          description\n          code\n          __typename\n        }\n        country {\n          code\n          description\n          label\n          __typename\n        }\n        __typename\n      }\n      contactNumbers(filter: $filter) {\n        phoneNumber {\n          display\n          original\n          __typename\n        }\n        __typename\n      }\n      __typename\n    }\n    airports {\n      name\n      distanceDetails {\n        description\n        __typename\n      }\n      contactNumber {\n        number\n        __typename\n      }\n      url\n      complimentaryShuttle\n      id\n      __typename\n    }\n    otherTransportation {\n      name\n      contactInformation {\n        phones\n        __typename\n      }\n      type {\n        description\n        code\n        __typename\n      }\n      __typename\n    }\n    reviews {\n      stars {\n        count\n        __typename\n      }\n      numberOfReviews {\n        count\n        __typename\n      }\n      __typename\n    }\n    parking {\n      fees {\n        fee\n        description\n        __typename\n      }\n      description\n      __typename\n    }\n    policies {\n      checkInTime\n      checkOutTime\n      smokefree\n      petsAllowed\n      petsPolicyDescription\n      localizedPetsPolicyDescription {\n        translatedText\n        __typename\n      }\n      petsPolicyDetails {\n        additionalPetFee\n        numberAllowed\n        refundableFee\n        refundableFeeType\n        nonRefundableFee\n        nonRefundableFeeType\n        additionalPetFeeType\n        weightRestricted\n        maxWeight\n        __typename\n      }\n      __typename\n    }\n    ... on Hotel {\n      seoNickname\n      __typename\n    }\n    __typename\n  }\n}\n"}');
        $script = /* * @lang JavaScript */
            '
            fetch("https://www.marriott.com/mi/query/phoenixShopPropertyInfoCall", {
              "headers": {
                    "Accept": "*/*",
                    "Accept-Language": "en-US",
                    "content-type": "application/json",
                    "apollographql-client-name": "phoenix_shop",
                    "apollographql-client-version": "v1",
                    "x-request-id": "' . $xRequestId . '",
                    "application-name": "shop",
                    "graphql-require-safelisting": "true",
                    "graphql-operation-signature": "' . self::SIGNATURE_NUMBER . '"
                },
                "referrer": "' . $this->currentUrl . '",
                "referrerPolicy": "strict-origin-when-cross-origin",
                "body": "' . $body . '",
                "method": "POST",
                "mode": "cors",
                "credentials": "include"
                })
                .then( response => response.json())
                .then( result => {
                    let script = document.createElement("script");
                    let id = "' . $id . '";
                    script.id = id;
                    script.setAttribute(id, JSON.stringify(result));
                    document.querySelector("body").append(script);
                })
            ;
            ';
        $this->logger->info($script, ['pre' => true]);
        $selenium->driver->executeScript($script);
        $data = $selenium->waitForElement(\WebDriverBy::xpath('//script[@id="' . $id . '"]'), 10, false);
        $selenium->saveResponse();

        if (!$data) {
            throw new \CheckRetryNeededException(5, 0);
        }

        return $this->http->JsonLog($data->getAttribute($id), 1, true);
    }

    private function getArgFetchRatesByGeoQuery($selenium)
    {
        $this->logger->notice(__METHOD__);

        return $selenium->driver->executeScript(/** @lang JavaScript */
            'return localStorage.getItem("phoenixShopLowestAvailableRatesByGeoQuery");'
        );
    }

    private function getFetchRatesByGeoQuery($selenium)
    {
        $this->logger->notice(__METHOD__);

        return $selenium->driver->executeScript(/** @lang JavaScript */
            'return localStorage.getItem("LowestAvailableRatesByGeoQuery");'
        );
    }

    private function makeSearchStringByGeo($placeId)
    {
        // TODO разные для разного числа комнат
        $this->logger->notice(__METHOD__);
        $checkIn = date('m/d/Y', $this->fields['CheckIn']);
        $checkOut = date('m/d/Y', $this->fields['CheckOut']);

        $getData = [
            'fromToDate_submit' => $checkOut,
            'fromDate' => $checkIn,
            'toDate' => $checkOut,
            'toDateDefaultFormat' => $checkOut,
            'fromDateDefaultFormat' => $checkIn,
            'flexibleDateSearch' => false,
            't-start' => $checkIn,
            't-end' => $checkOut,
            'lengthOfStay' => $this->fields['Nights'],
            'childrenCountBox' => $this->fields['Kids'] . '+Children+Per+Room',
            'childrenCount' => $this->fields['Kids'],
            'clusterCode' => 'none',
            'useRewardsPoints' => true,
            'marriottBrands' => '',
            'isAdvanceSearch' => false,
            'recordsPerPage' => 100,
            'isInternalSearch' => true,
            'vsInitialRequest' => false,
            'searchType' => 'InCity',
            'singleSearchAutoSuggest' => 'Unmatched',
            'destinationAddress.placeId' => $placeId,
            'for-hotels-nearme' => 'Near', // not always??
            'collapseAccordian' => 'is-hidden',
            'singleSearch' => true,
            'isTransient' => true,
            //initialRequest=true&
            'initialRequest' => false,
            'flexibleDateSearchRateDisplay' => false,
            'isSearch' => true,
            //isRateCalendar=true&
            'isRateCalendar' => false,
            'destinationAddress.destination' => $this->fields['Destination'],
            'isHideFlexibleDateCalendar' => false,
            'roomCountBox' => '1+Room',
            'roomCount' => $this->fields['Rooms'],
            'guestCountBox' => $this->fields['Adults'] . '+Adult+Per+Room',
            'numAdultsPerRoom' => $this->fields['Adults'],
            // not always
            'destinationAddress.location' => $this->fields['Destination'],
            'fromToDate' => date('m/d/Y'),
            'isFlexibleDatesOptionSelected' => false,
            'numberOfRooms' => $this->fields['Rooms'],
            'view' => 'list',
        ];

        if ($this->fields['Kids'] > 0) {
            $getData['childrenAges'] = 0;
        }

        return http_build_query($getData) . '#/0/';
    }

    private function parseDataJson(self $selenium, $data, $arg)
    {
        $this->logger->notice(__METHOD__);
        $selenium->waitForElement(\WebDriverBy::xpath("(//div[normalize-space()='Points / Stay']/preceding::button[normalize-space()='View Hotel Details'])[1]"),
            0)->click();

        if ($selenium->waitForElement(\WebDriverBy::xpath("//span[normalize-space()='View Hotel Website']"), 15)) {
            $selenium->waitForElement(\WebDriverBy::xpath("//span[normalize-space()='View Hotel Website']/preceding::div[normalize-space(@aria-label)='Close pop up']"),
                0)->click();
        }

        if ($selenium->waitForElement(\WebDriverBy::xpath("//h1[contains(.,'Your session timed out, but you can start a new hotel search below.')]"),
            0)) {
            throw new \CheckException('session timed out', ACCOUNT_ENGINE_ERROR);
        }

        $session = $this->getSessionToken($selenium);

        $result = [];

        foreach ($data['data']['searchLowestAvailableRatesByGeolocation']['edges'] as $num => $datum) {
            $node = $datum['node'];
            $points = $cache = $currency = null;

            if ($num > 0 && ($num % 80 === 0)) {
                $this->increaseTimeLimit();
            }

            foreach ($node['rates'] as $rate) {
                if ($rate['status']['code'] === 'unavailable') {
                    continue;
                }

                if ($rate['status']['code'] !== 'available') {
                    $this->sendNotification("check status code rate // ZM");
                }

                foreach ($rate['rateAmounts'] as $rateAmount) {
                    if (isset($rateAmount['points'])) {
                        $points = $rateAmount['points'];

                        continue;
                    }
                    $currency = $rateAmount['amount']['origin']['currency'];

                    if (!isset($cache) && $rateAmount['amount']['origin']['value'] > 0) {
                        $cache = round($rateAmount['amount']['origin']['value']) / (10 ** $rateAmount['amount']['origin']['valueDecimalPoint']);
                    }
                }
            }

            if (!isset($points, $cache, $currency)) {
                $this->sendNotification("check node {$num} // ZM");

                continue;
            }

            if (!$node['property']['basicInformation']['bookable']) {
                $this->logger->debug("skip not bookable");

                continue;
            }

            $dataLayer = $this->runDataLayer($selenium, $session["sessionToken"], $node['property']['id']);
            $x_request_id = $this->getXRequestID($dataLayer["component"]["data"]["dataProperties"]);

            $details = $this->runFetchPropertyInfoCall($selenium, $node['property']['id'], $x_request_id);

            if (!$details || isset($details['errors'])) {
                throw new \CheckException('empty data', ACCOUNT_ENGINE_ERROR);
            }
//
//            $headers = [
//                "accept" => "*/*",
//                "accept-language" => "en-US",
//                "apollographql-client-name" => "phoenix_shop",
//                "apollographql-client-version" => "v1",
//                "application-name" => "shop",
//                "cache-control" => "no-cache",
//                "content-type" => "application/json",
//                "graphql-operation-signature" => $this->signature,
//                "graphql-require-safelisting" => "true",
//                "x-request-id" => $this->xRequestId,
//                "referrer" => $this->currentUrl
//            ];
//            $payload = "{\"operationName\":\"phoenixShopPropertyInfoCall\",\"variables\":{\"propertyId\":\"".$node['property']['id']."\",\"filter\":\"PHONE\",\"descriptionsFilter\":[\"LOCATION\",\"RESORT_FEE_DESCRIPTION\",\"DESTINATION_FEE_DESCRIPTION\"]},\"query\":\"query phoenixShopPropertyInfoCall(\$propertyId: ID!, \$filter: [ContactNumberType], \$descriptionsFilter: [PropertyDescriptionType]) {\\n  property(id: \$propertyId) {\\n    id\\n    basicInformation {\\n      name\\n      latitude\\n      longitude\\n      isAdultsOnly\\n      isMax\\n      brand {\\n        id\\n        __typename\\n      }\\n      openingDate\\n      bookable\\n      resort\\n      descriptions(filter: \$descriptionsFilter) {\\n        text\\n        type {\\n          code\\n          label\\n          description\\n          __typename\\n        }\\n        localizedText {\\n          sourceText\\n          translatedText\\n          __typename\\n        }\\n        __typename\\n      }\\n      hasUniquePropertyLogo\\n      nameInDefaultLanguage\\n      __typename\\n    }\\n    contactInformation {\\n      address {\\n        line1\\n        city\\n        postalCode\\n        stateProvince {\\n          label\\n          description\\n          code\\n          __typename\\n        }\\n        country {\\n          code\\n          description\\n          label\\n          __typename\\n        }\\n        __typename\\n      }\\n      contactNumbers(filter: \$filter) {\\n        phoneNumber {\\n          display\\n          original\\n          __typename\\n        }\\n        __typename\\n      }\\n      __typename\\n    }\\n    airports {\\n      name\\n      distanceDetails {\\n        description\\n        __typename\\n      }\\n      contactNumber {\\n        number\\n        __typename\\n      }\\n      url\\n      complimentaryShuttle\\n      id\\n      __typename\\n    }\\n    otherTransportation {\\n      name\\n      contactInformation {\\n        phones\\n        __typename\\n      }\\n      type {\\n        description\\n        code\\n        __typename\\n      }\\n      __typename\\n    }\\n    reviews {\\n      stars {\\n        count\\n        __typename\\n      }\\n      numberOfReviews {\\n        count\\n        __typename\\n      }\\n      __typename\\n    }\\n    parking {\\n      fees {\\n        fee\\n        description\\n        __typename\\n      }\\n      description\\n      __typename\\n    }\\n    policies {\\n      checkInTime\\n      checkOutTime\\n      smokefree\\n      petsAllowed\\n      petsPolicyDescription\\n      localizedPetsPolicyDescription {\\n        translatedText\\n        __typename\\n      }\\n      petsPolicyDetails {\\n        additionalPetFee\\n        numberAllowed\\n        refundableFee\\n        refundableFeeType\\n        nonRefundableFee\\n        nonRefundableFeeType\\n        additionalPetFeeType\\n        weightRestricted\\n        maxWeight\\n        __typename\\n      }\\n      __typename\\n    }\\n    ... on Hotel {\\n      seoNickname\\n      __typename\\n    }\\n    __typename\\n  }\\n}\\n\"}";
//            $this->http->PostURL("https://www.marriott.com/mi/query/phoenixShopPropertyInfoCall",$payload, $headers);
//            $this->runFetchDetails($selenium,);
//            $details = $this->http->JsonLog(null, 1, true);

            $address = implode(', ', array_filter([
                $details['data']['property']['contactInformation']['address']['line1'] ?? null,
                $details['data']['property']['contactInformation']['address']['city'] ?? null,
                $details['data']['property']['contactInformation']['address']['stateProvince']['description'] ?? null,
                $details['data']['property']['contactInformation']['address']['country']['description'] ?? null,
            ]));
            $url = 'https://www.marriott.com/hotels/travel/' . $details['data']['property']['seoNickname'];

            if (isset($node['property']['media']['primaryImage']['edges'][0]['node']['imageUrls']['wideHorizontal']) !== false) {
                $preview = $this->getBase64FromImageUrl('https://cache.marriott.com' . $node['property']['media']['primaryImage']['edges'][0]['node']['imageUrls']['wideHorizontal']);
            } else {
                $preview = null;
            }

            $result[] = [
                'name' => $node['property']['basicInformation']['nameInDefaultLanguage'],
                'checkInDate' => date("Y-m-d H:i",
                    strtotime($details['data']['property']['policies']['checkInTime'], $this->fields['CheckIn'])),
                'checkOutDate' => date("Y-m-d H:i",
                    strtotime($details['data']['property']['policies']['checkOutTime'], $this->fields['CheckOut'])),
                'roomType' => null,
                'hotelDescription' => $node['property']['basicInformation']['descriptions'][0]['text'] ?? null,
                'numberOfNights' => $this->fields['Nights'],
                'pointsPerNight' => ((float)$points) / $this->fields['Nights'],
                'cashPerNight' => $cache,
                'distance' => $node['distance'],
                'rating' => $node['property']['reviews']['stars']['count'] ?? null,
                'numberOfReviews' => $node['property']['reviews']['numberOfReviews']['count'] ?? null,
                'address' => $address,
                'detailedAddress' => [
                    'addressLine' => $details['data']['property']['contactInformation']['address']['line1'] ?? null,
                    'city' => $details['data']['property']['contactInformation']['address']['city'] ?? null,
                    'state' => $details['data']['property']['contactInformation']['address']['stateProvince']['description'] ?? null,
                    'countryName' => $details['data']['property']['contactInformation']['address']['country']['description'] ?? null,
                    'postalCode' => $details['data']['property']['contactInformation']['address']['postalCode'] ?? null,
                    'lat' => $node['property']['basicInformation']['latitude'] ?? null,
                    'lng' => $node['property']['basicInformation']['longitude'] ?? null,
                    'timezone' => null,
                ],
                'phone' => $details['data']['property']['contactInformation']['contactNumbers'][0]['phoneNumber']['original'] ?? null,
                'url' => $url,
                'preview' => $preview,
            ];
        }

        return $result;
    }

    private function getBase64FromImageUrl(?string $url): ?string
    {
        if (null === $url) {
            $this->logger->warning('Provided URL is null.');

            return null;
        }

        $this->logger->info('Download image: ' . $url);
        $http2 = clone $this->http;

        try {
            $file = $http2->DownloadFile($url);

            if (!file_exists($file)) {
                $this->logger->error('Failed to download file from URL: ' . $url);

                return null;
            }

            $imageSize = getimagesize($file);

            if (!$imageSize) {
                return null;
            }

            if ($imageSize[0] > 400) {
                $image = new \Imagick($file);
                $image->scaleImage(400, 0);

                file_put_contents($file, $image);
                $imageSize = getimagesize($file);
            }

            $imageData = base64_encode(file_get_contents($file));
            $this->logger->debug("<img src='data:{$imageSize['mime']};base64,{$imageData}' {$imageSize[3]} />",
                ['HtmlEncode' => false]);

            return $imageData;
        } catch (Exception $e) {
            $this->logger->error('Error downloading or processing image from URL: ' . $url . ' - ' . $e->getMessage());

            return null;
        }
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

    private function getSessionToken(self $selenium): array
    {
        $this->logger->notice(__METHOD__);

        $script = /* * @lang JavaScript */
            '
            fetch("https://www.marriott.com/mi/phoenix-gateway/v1/session", {
                "credentials": "include",
                "headers": {
                    "Accept": "application/json, text/plain, */*",
                    "Accept-Language": "en-US",
                    "content-type": "application/json",
                },
                "referrer": "' . $this->currentUrl . '",
                "body": "{\"keys\": \"sessionToken,rewardsId,memberLevel,name,accessToken,consumerID,propertyId,AriesRewards.savedHotelList,AriesReservation.propertyId,AriesReservation.errorMessages,AriesCommon.prop_name,AriesCommon.savedProps,AriesCommon.revisionToken,AriesCommon.memState,AriesCommon.ptsBal,AriesCommon.search_destination_city,AriesCommon.search_destination_country,AriesCommon.search_destination_state,AriesSearch.search_availability_search,AriesSearch.search_date_type,AriesSearch.search_location_or_date_change,AriesSearch.rememberedMemberLevel,AriesSearch.searchCriteria,AriesSearch.search_keyword,AriesSearch.search_date_check_out_day_of_week,AriesSearch.search_date_check_in_day_of_week,AriesSearch.search_advance_purchase_days,AriesSearch.propertyFilterCriteria,AriesSearch.hotelDirectoryFilterCriteria,AriesSearch.search_is_weekend_stay,AriesSearch.search_criteria_changed,AriesSearch.search_google_places_destination,AriesSearch.propertyRecordsCount,AriesSearch.propertyId,AriesSearch.errorMessages,AriesSearch.search_dates_flexible\"}",
                "method": "POST",
                "mode": "cors"
            })
                .then( response => response.json())
                .then( result => {
                    let script = document.createElement("script");
                    let id = "session";
                    script.id = id;            
                    script.setAttribute(id, JSON.stringify(result));
                    document.querySelector("body").append(script);
                });
                ';
        $this->logger->info($script, ['pre' => true]);
        $selenium->driver->executeScript($script);
        $data = $selenium->waitForElement(\WebDriverBy::xpath('//script[@id="session"]'), 10, false);
        $selenium->saveResponse();

        if (!$data) {
            throw new \CheckRetryNeededException(5, 0);
        }

        return $this->http->JsonLog($data->getAttribute("session"), 1, true);
    }

    private function runDataLayer(self $selenium, string $sessionToken, string $id): array
    {
        $this->logger->notice(__METHOD__);
        $body = addslashes('{"sessionToken": "' . $sessionToken . '", "sourceURI": "/search/findHotels.mi", "variation": "0.1", "context": {"absolutePageURL": "/search/findHotels.mi", "applicationName": "AriesSearch", "brandCode": "CY", "channel": "marriott", "localeKey": "en_US", "marshaCode": "' . $id . '", "mobileAuthEnabled": "false", "pageContent": [], "pageURI": "/search/findHotels","productSiteId": "search", "products": "search", "programFlag": "", "propertyId": "' . $id . '", "referer": "' . $this->currentUrl . '", "seoQueryParams": {}, "siteName": "marriott.com", "template": "V2"}}');
        $script = /* * @lang JavaScript */
            '
            fetch("https://www.marriott.com/mi/phoenix-common/v1/dataLayer", {
                "credentials": "include",
                "headers": {
                    "Accept": "application/json, text/plain, */*",
                    "Accept-Language": "en-US",
                    "content-type": "application/json",
                    "Origin": "https://www.marriott.com",
                    "Referer": "' . $this->currentUrl . '"
                },
                "body": "' . $body . '",
                "method": "POST",
                "mode": "cors"
            })
                .then( response => response.json())
                .then( result => {
                    let script = document.createElement("script");
                    let id = "dataLayer";
                    script.id = id;            
                    script.setAttribute(id, JSON.stringify(result));
                    document.querySelector("body").append(script);
                });
            ';
        $this->logger->info($script, ['pre' => true]);
        $selenium->driver->executeScript($script);
        $data = $selenium->waitForElement(\WebDriverBy::xpath('//script[@id="dataLayer"]'), 10, false);
        $selenium->saveResponse();

        if (!$data) {
            throw new \CheckRetryNeededException(5, 0);
        }

        return $this->http->JsonLog($data->getAttribute("datalayer"), 1, true);
    }

    private function getXRequestID(array $dataProperties): string
    {
        $this->logger->notice(__METHOD__);

        $dataProperties = array_reverse($dataProperties);

        foreach ($dataProperties as $property) {
            if ($property["key"] == "request_id") {
                $this->logger->debug('X_Request_ID received');

                return $property["value"];
            }
        }

        return "Request ID not found";
    }
}
