<?php

namespace AwardWallet\Engine\hhonors\RewardAvailability;

use AwardWallet\Engine\ProxyList;
use AwardWallet\Engine\Settings;

class HotelParser extends \TAccountChecker
{
    use ProxyList;
    use \SeleniumCheckerHelper;

    private $downloadPreview;
    private string $token;

    public static function getRASearchLinks(): array
    {
        return ['https://www.hilton.com/en/search/find-hotels/' => 'search page'];
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->UseSelenium();
        $this->useGoogleChrome(\SeleniumFinderRequest::CHROME_95);
        $this->disableImages();
        $this->setScreenResolution([1280, 800]);
        $this->http->saveScreenshots = true;
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

        if ($fields['Rooms'] > 9) {
            $this->SetWarning('Maximum 9 rooms');

            return ['hotels' => []];
        }

        $checkInStr = date('Y-m-d', $fields['CheckIn']);
        $checkOutStr = date('Y-m-d', $fields['CheckOut']);
        $this->downloadPreview = $fields['DownloadPreview'] ?? false;

        if ($checkInStr == $checkOutStr) {
            $this->SetWarning('You can’t book a day-use room.');

            return ['hotels' => []];
        }

        $this->http->GetURL('https://www.hilton.com/en');
        $this->token = $this->getToken('https://www.hilton.com/en');
        $this->logger->debug(var_export($this->token, true), ['pre' => true]);


        // TODO:
        /* if (strtotime("+3 month", strtotime($checkInStr)) <= strtotime($checkOutStr)) {
             $this->SetWarning('You cannot book a room with points for such a long period of time.');

             return ['hotels' => []];
         }*/

        $query = http_build_query([
            'query' => $fields['Destination'],
            'arrivalDate' => $checkInStr,
            'departureDate' => $checkOutStr,
            'flexibleDates' => false,
            'numRooms' => $fields['Rooms'],
            'numAdults' => $fields['Adults'],
            'numChildren' => $fields['Kids'],
            'room1ChildAges' => null,
            'room1AdultAges' => null,
            'redeemPts' => "true",
        ]);

        $url = "https://www.hilton.com/en/search/?{$query}";
        $this->http->GetURL($url);
        $this->saveResponse();

        if (!$this->checkErrors()) {
            return ['hotels' => []];
        }

        try {
            $data = $this->getData($fields, $checkInStr, $checkOutStr);
        } catch (\WebDriverException $e) {
            $this->logger->error($e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        }

        $res = $this->parseRespData($data);

        return ['hotels' => $res];
    }

    private function getData(array $fields, string $checkInStr, string $checkOutStr): array
    {
        $this->logger->notice(__METHOD__);

        $script = '
            fetch("https://www.hilton.com/graphql/customer?operationName=geocode_hotelSummaryOptions&originalOpName=geocode_hotelSummaryOptions&appName=dx_shop_search_app&bl=en", {
              "headers": {
                "accept": "*/*",
                "authorization": "' . $this->token . '",
                "content-type": "application/json",
              },
              "referrer": "https://www.hilton.com/en/search/",
              "body": "{\"query\":\"query geocode_hotelSummaryOptions($address: String, $distanceUnit: HotelDistanceUnit, $language: String!, $placeId: String, $queryLimit: Int!, $sessionToken: String) {  geocode(    language: $language    address: $address    placeId: $placeId    sessionToken: $sessionToken  ) {    match {      id      address {        city        country        }      name      type      geometry {        location {          latitude          longitude        }        bounds {          northeast {            latitude            longitude          }          southwest {            latitude            longitude          }        }      }    }    hotelSummaryOptions(distanceUnit: $distanceUnit, sortBy: distance) {      bounds {        northeast {          latitude          longitude        }        southwest {          latitude          longitude        }      }      amenities {        id        name        hint      }      amenityCategories {        name        id        amenityIds      }      brands {        code        name      }      hotels(first: $queryLimit) {        _id: ctyhocn        amenityIds        brandCode        ctyhocn        distance        distanceFmt        facilityOverview {          allowAdultsOnly        homeUrl        }        name        display {          open          openDate          preOpenMsg          resEnabled          resEnabledDate        }        contactInfo {          phoneNumber        }        address {          addressFmt          addressLine1          city          country          state        postalCode        }        localization {          coordinate {            latitude            longitude          }        }        masterImage(variant: searchPropertyImageThumbnail) {          altText          variants {            size            url          }        }        tripAdvisorLocationSummary {          numReviews          rating          ratingFmt(decimal: 1)          ratingImageUrl          reviews {            id            rating            helpfulVotes            ratingImageUrl            text            travelDate            user {              username            }            title          }        }        leadRate {          hhonors {            lead {              dailyRmPointsRate              dailyRmPointsRateNumFmt: dailyRmPointsRateFmt(hint: number)              ratePlan {                ratePlanName                ratePlanDesc              }            }            max {              rateAmount              rateAmountFmt              dailyRmPointsRate              dailyRmPointsRateRoundFmt: dailyRmPointsRateFmt(hint: round)              dailyRmPointsRateNumFmt: dailyRmPointsRateFmt(hint: number)              ratePlan {                ratePlanCode              }            }            min {              rateAmount(decimal: 1)              rateAmountFmt              dailyRmPointsRate              dailyRmPointsRateRoundFmt: dailyRmPointsRateFmt(hint: round)              dailyRmPointsRateNumFmt: dailyRmPointsRateFmt(hint: number)              ratePlan {                ratePlanCode              }            }          }        }      }    }    ctyhocnList: hotelSummaryOptions(distanceUnit: $distanceUnit, sortBy: distance) {      hotelList: hotels(first: 150) {        ctyhocn      }    }  }}\",\"operationName\":\"geocode_hotelSummaryOptions\",\"variables\":{\"address\":\"' . $fields['Destination'] . '\",\"language\":\"en\",\"placeId\":null,\"queryLimit\":40}}",
              "method": "POST",
            }).then( response => response.json())
              .then( result => {
                let script = document.createElement("script");
                let id = "geocode_hotelSummaryOptions_response";
                script.id = id;
                script.setAttribute(id, JSON.stringify(result.data.geocode.hotelSummaryOptions.hotels));
                document.querySelector("body").append(script);
            });
        ';

        $this->logger->debug("Execute script:");
        $this->logger->debug($script, ['pre' => true]);
        $this->driver->executeScript($script);

        $json = $this->waitForElement(\WebDriverBy::xpath('//script[@id="geocode_hotelSummaryOptions_response"]'), 10,
            false);

        if (!$json) {
            throw new \CheckRetryNeededException(5, 0);
        }

        $data = [];
        $hotelsArr = [];
        $result = $this->http->JsonLog($json->getAttribute('geocode_hotelSummaryOptions_response'), 1, true);

        foreach ($result as $hotelSummary) {
            $data[$hotelSummary['_id']] = $hotelSummary;
            $hotelsArr[] = $hotelSummary['_id'];
        }

        $hotelsArr = array_chunk($hotelsArr, 20);

        foreach ($hotelsArr as $index => $value) {
            $body = addslashes('{"query":"query shopMultiPropAvail($ctyhocns: [String!], $language: String!, $input: ShopMultiPropAvailQueryInput!) {\n  shopMultiPropAvail(input: $input, language: $language, ctyhocns: $ctyhocns) {\n    ageBasedPricing\n    ctyhocn\n    currencyCode\n    statusCode\n    statusMessage\n    lengthOfStay\n    notifications {\n      subType\n      text\n      type\n    }\n    summary {\n      hhonors{\n      dailyRmPointsRate\n     dailyRmPointsRateFmt\n     rateChangeIndicator\n    ratePlan {\n    ratePlanName @toUpperCase\n    }\n     }\n       lowest {\n        cmaTotalPriceIndicator\n        feeTransparencyIndicator\n        rateAmountFmt(strategy: trunc, decimal: 0)\n        rateAmount(currencyCode: \"USD\")\n        ratePlanCode\n        rateChangeIndicator\n        ratePlan {\n          attributes\n          ratePlanName @toUpperCase\n          specialRateType\n          confidentialRates\n        }\n        amountAfterTax(currencyCode: \"USD\")\n        amountAfterTaxFmt(decimal: 0, strategy: trunc)\n      }\n      status {\n        type\n      }\n    }\n  }\n}","operationName":"shopMultiPropAvail","variables":{"input":{"guestId":0,"guestLocationCountry":"US","arrivalDate":"' . $checkInStr . '","departureDate":"' . $checkOutStr . '","numAdults":' . $fields['Adults'] . ',"numChildren":0,"numRooms":' . $fields['Rooms'] . ',"childAges":[],"ratePlanCodes":[],"rateCategoryTokens":[],"specialRates":{"aaa":false,"aarp":false,"corporateId":"","governmentMilitary":false,"groupCode":"","hhonors":true,"pnd":"","offerId":null,"promoCode":"","senior":false,"smb":false,"travelAgent":false,"teamMember":false,"familyAndFriends":false,"owner":false,"ownerHGV":false}},"ctyhocns":["' . implode('","',
                    $value) . '"],"language":"en"}}');
            $script = '
                fetch("https://www.hilton.com/graphql/customer?operationName=shopMultiPropAvail&originalOpName=shopMultiPropAvailPoints&appName=dx_shop_search_app&bl=en", {
                  "headers": {
                    "accept": "*/*",
                    "authorization": "' . $this->token . '",
                    "content-type": "application/json",
                  },
                  "referrer": "https://www.hilton.com/en/search/",
                  "body": "' . $body . '",
                  "method": "POST",
                }).then( response => response.json())
                  .then( result => {
                    let script = document.createElement("script");
                    let id = "shopMultiPropAvail-response' . $index . '";
                    script.id = id;
                    script.setAttribute(id, JSON.stringify(result.data.shopMultiPropAvail));
                    document.querySelector("body").append(script);
                });
            ';

            $this->logger->debug("Execute script:");
            $this->logger->debug($script, ['pre' => true]);
            $this->driver->executeScript($script);
        }

        for ($i = 0, $iMax = count($hotelsArr); $i < $iMax; $i++) {
            $json = $this->waitForElement(\WebDriverBy::xpath("//script[@id='shopMultiPropAvail-response{$i}']"), 10,
                false);

            if (!$json) {
                throw new \CheckRetryNeededException(5, 0);
            }
            $result = $this->http->JsonLog($json->getAttribute("shopMultiPropAvail-response{$i}"), 1, true);

            foreach ($result as $shopMultiPropAvail) {
                $data[$shopMultiPropAvail['ctyhocn']] = $data[$shopMultiPropAvail['ctyhocn']] + $shopMultiPropAvail;
            }
        }

        return $data;
    }

    private function checkErrors()
    {
        if ($this->waitForElement(\WebDriverBy::xpath('
            //h2[contains(text(),"find the page you are looking")]
            | //div[contains(text(),"entries contained an error")]
            | //h1[contains(text(),"WE\'RE SORRY!")]
        '), 1)) {
            $this->SetWarning('Invalid search data. Please verify your entries and try again');

            return false;
        }

        if ($err = $this->waitForElement(\WebDriverBy::xpath('//h2[contains(text(),"something went wrong.")]'), 0)) {
            $this->logger->error($err->getText());

            throw new \ProviderError($err->getText());
        }

        return true;
    }

    private function parseRespData($data)
    {
        $this->logger->notice(__METHOD__);
        $parseData = [];
        $msgSkippedHotel = null;

        foreach ($data as $row) {
            if ($row['summary']['status']['type'] !== 'AVAILABLE') {
                continue;
            }

            if (!isset($row['summary']['hhonors']['dailyRmPointsRate'])) {
                $this->logger->debug('no points rate. skip');

                if ($row['notifications'][0]['type'] === 'info' && !isset($msgSkippedHotel)) {
                    $msgSkippedHotel = $row['notifications'][0]['text'] ?? null;
                }
                $skipedHotel = true;

                continue;
            }
            $preview = null;

            if (isset($row['masterImage']['variants'][0]) && $this->downloadPreview) {
                $urlImg = $row['masterImage']['variants'][0]['url'] ?? null;
                $preview = $this->getBase64FromImageUrl($urlImg);
            }

            $parseData[] = [
                'name' => $row['name'] ?? null,
                'checkInDate' => date('Y-m-d H:i', $this->AccountFields['RaRequestFields']['CheckIn']),
                'checkOutDate' => date('Y-m-d H:i', $this->AccountFields['RaRequestFields']['CheckOut']),
                'roomType' => $row['summary']['hhonors']['ratePlan']['ratePlanName'] ?? null,
                'hotelDescription' => null,
                'numberOfNights' => null,
                'pointsPerNight' => $row['summary']['hhonors']['dailyRmPointsRate'],
                'cashPerNight' => $row['summary']['lowest']['amountAfterTax'],
                'distance' => $row['distanceFmt'],
                'rating' => $row['tripAdvisorLocationSummary']['rating'] ?? null,
                'numberOfReviews' => $row['tripAdvisorLocationSummary']['numReviews'] ?? null,
                'address' => $row['address']['addressFmt'] ?? null,
                'detailedAddress' => [
                    'addressLine' => $row['address']['addressLine1'] ?? null,
                    'city' => $row['address']['city'] ?? null,
                    'stateName' => $row['address']['state'] ?? null,
                    'countryName' => $row['address']['country'] ?? null,
                    'postalCode' => $row['address']['postalCode'] ?? null,
                    'lat' => $row['localization']['coordinate']['latitude'] ?? null,
                    'lng' => $row['localization']['coordinate']['longitude'] ?? null,
                    'timezone' => null,
                ],
                'phone' => $row['contactInfo']['phoneNumber'] ?? null,
                'url' => $row['facilityOverview']['homeUrl'] ?? null,
                'preview' => $preview ?? null,
            ];
        }

        if (isset($skipedHotel)) {
            if (isset($msgSkippedHotel)) {
                $this->SetWarning($msgSkippedHotel);
            }
            $this->sendNotification("check skipped // ZM");
        }

        return $parseData;
    }

    private function getToken($url): string
    {
        $app_id = $this->driver->executeScript('return window.__NEXT_DATA__.props.pageProps.env.DX_AUTH_API_CUSTOMER_APP_ID');

        $payload = '{"app_id":"' . $app_id . '"}';
        $script = '
            let xhr = new XMLHttpRequest();
            xhr.open("POST", "https://www.hilton.com/dx-customer/auth/applications/token", false);
            xhr.setRequestHeader("Accept","application/json; charset=utf-8")
            xhr.setRequestHeader("Content-Type","application/json; charset=utf-8")
            xhr.setRequestHeader("Referer","' . $url . '")
            xhr.send(\'' . $payload . '\')
            return xhr.responseText
        ';
        $json = $this->driver->executeScript($script);

        $data = $this->http->JsonLog($json, 1);

        if (null === $data) {
            throw new \CheckException('no token', ACCOUNT_ENGINE_ERROR);
        }
        return $data->token_type . ' ' . $data->access_token;
    }


    private function getBase64FromImageUrl(?string $url): ?string
    {
        if (null === $url) {
            return null;
        }
        $file = $this->http->DownloadFile($url);
        $imageSize = getimagesize($file);
        $imageData = base64_encode(file_get_contents($file));

        if (!empty($imageSize)) {
            $this->logger->debug("<img src='data:{$imageSize['mime']};base64,{$imageData}' {$imageSize[3]} />",
                ['HtmlEncode' => false]);
        }

        return $imageData;
    }

}