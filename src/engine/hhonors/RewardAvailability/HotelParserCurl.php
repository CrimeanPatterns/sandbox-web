<?php

namespace AwardWallet\Engine\hhonors\RewardAvailability;

use AwardWallet\Engine\ProxyList;
use AwardWallet\Engine\Settings;

class HotelParserCurl extends \TAccountChecker
{
    use ProxyList;

    private $downloadPreview;

    public function InitBrowser()
    {
        parent::InitBrowser();
//        $this->setProxyBrightData(null, Settings::RA_ZONE_STATIC);
        $this->setProxyGoProxies(null, 'us', 'los angeles');
    }

    public function IsLoggedIn()
    {
        return true;
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

        $this->http->GetURL("https://www.hilton.com/en/");

        $checkInStr = date('Y-m-d', $fields['CheckIn']);
        $checkOutStr = date('Y-m-d', $fields['CheckOut']);
        $this->downloadPreview = $fields['DownloadPreview'] ?? false;

        if ($checkInStr == $checkOutStr) {
            $this->SetWarning('You can’t book a day-use room.');

            return ['hotels' => []];
        }

        $query = http_build_query([
            'query'          => $fields['Destination'],
            'arrivalDate'    => $checkInStr,
            'departureDate'  => $checkOutStr,
            'flexibleDates'  => false,
            'numRooms'       => $fields['Rooms'],
            'numAdults'      => $fields['Adults'],
            'numChildren'    => $fields['Kids'],
            'room1ChildAges' => null,
            'room1AdultAges' => null,
            'redeemPts'      => "true",
        ]);
        $url = "https://www.hilton.com/en/search/?{$query}";
        $this->http->GetURL($url);

        if ($this->http->FindSingleNode("//h2[contains(text(),'We couldn’t find the page you are looking for, but maybe these links will help.')]")) {
            $this->SetWarning('There are no results for this query');

            return ['hotels' => []];
        }

        if ($this->http->Response['code'] == 403) {
            throw new \CheckRetryNeededException(5, 0);
        }

        $jsonData = $this->http->FindSingleNode("//script[@id='__NEXT_DATA__']", null, false, "/({.+})/u");
        $data = $this->http->JsonLog($jsonData, 0, true);

        if (null === $data) {
            throw new \CheckException('no __NEXT_DATA__', ACCOUNT_ENGINE_ERROR);
        }

        $preParseData = $this->parseNextData($data);

        $auth = $this->getToken($data['runtimeConfig']['DX_AUTH_API_CUSTOMER_APP_ID'], $url);

        $headers = [
            'Accept'        => '*/*',
            'Content-Type'  => 'application/json',
            'Dx-Platform'   => 'web',
            'Referer'       => 'https://www.hilton.com/en/search/',
            'Authorization' => $auth,
        ];

        // валюта тут - курс конвертации
        // https://www.hilton.com/graphql/customer?operationName=currencies&originalOpName=currencies&appName=dx_shop_search_app&bl=en
        // на всякий если ответ будет не в $. м/б из-за региона в ответе будет иное, что вряд ли
        // TODO нужна проверка валюты и если нет, то нотификация. (прилетит нотификация, тогда доработать)

//        $quadrantIds = $this->getQuadrantIds($fields['Destination'], $headers);

        $ctyhocnsArr = array_keys($preParseData);
        $pointsInfo = $this->getPointsInfo($fields, $ctyhocnsArr, $headers);

        return ['hotels' => $this->parseRespData2($pointsInfo, $headers, $preParseData)];

        $hotelsInfo = $this->getHotelsInfo($quadrantIds, $headers);

        return ['hotels' => $this->parseRespData($pointsInfo, $hotelsInfo, $preParseData)];
    }

    private function parseNextData($data): array
    {
        $this->logger->notice(__METHOD__);

        foreach ($data['props']['pageProps']['dehydratedState']['queries'] as $query) {
            if (!isset($query['state']['data']['geocode']['hotelSummaryOptions']['hotels'])) {
                continue;
            }
            $hotelsArr = $query['state']['data']['geocode']['hotelSummaryOptions']['hotels'];

            break;
        }
        $preParseData = [];

        foreach ($hotelsArr as $hotel) {
            if (!isset($hotel['leadRate'])) {
                continue;
            }
            $preParseData[$hotel['ctyhocn']] = [
                'name'             => $hotel['name'],
                'checkInDate'      => date('Y-m-d H:i', $this->AccountFields['RaRequestFields']['CheckIn']),
                'checkOutDate'     => date('Y-m-d H:i', $this->AccountFields['RaRequestFields']['CheckOut']),
                'roomType'         => $hotel['leadRate']['hhonors']['lead']['ratePlan']['ratePlanName'],
                'hotelDescription' => null,
                'numberOfNights'   => null,
                'pointsPerNight'   => null,
                'cachePerNight'    => null,
                'distance'         => $hotel['distanceFmt'],
                'rating'           => null,
                'numberOfReviews'  => null,
                'address'          => implode(', ', $hotel['address']),
                'phone'            => $hotel['contactInfo']['phoneNumber'],
            ];
        }

        return $preParseData;
    }

    private function getToken($app_id, $url): string
    {
        $this->logger->notice(__METHOD__);
        $headers = [
            'Accept'       => 'application/json; charset=utf-8',
            'Content-Type' => 'application/json; charset=utf-8',
            'Referer'      => $url,
        ];
        $payload = '{"app_id":"' . $app_id . '"}';
        $this->http->RetryCount = 0;
        $this->http->PostURL("https://www.hilton.com/dx-customer/auth/applications/token", $payload, $headers);

        $data = $this->http->JsonLog(null, 0);

        if (null === $data) {
            throw new \CheckException('no token', ACCOUNT_ENGINE_ERROR);
        }

        return $data->token_type . ' ' . $data->access_token;
    }

    private function getQuadrantIds(string $destination, array $headers): array
    {
        $this->logger->notice(__METHOD__);
        $payload = '{"query":"query hotelQuadrants {\n  hotelQuadrants {\n    id\n    amenityIds\n    brands {\n      code\n      amenityIds\n    }\n    bounds {\n      northeast {\n        latitude\n        longitude\n      }\n      southwest {\n        latitude\n        longitude\n      }\n    }\n    countries {\n      code\n      states\n    }\n  }\n}","operationName":"hotelQuadrants","variables":{}}';
        $this->http->PostURL("https://www.hilton.com/graphql/customer?operationName=hotelQuadrants&originalOpName=hotelQuadrants&appName=dx_shop_search_app&bl=en",
            $payload, $headers);
        $quadrants = $this->http->JsonLog(null, 0, true);

        $payload = '{"query":"query geocode_hotelSummaryOptions($address: String, $distanceUnit: HotelDistanceUnit, $language: String!, $placeId: String, $queryLimit: Int!, $sessionToken: String) {\n  geocode(\n    language: $language\n    address: $address\n    placeId: $placeId\n    sessionToken: $sessionToken\n  ) {\n    match {\n      id\n      address {\n        city\n        country\n        state\n      }\n      name\n      type\n      geometry {\n        location {\n          latitude\n          longitude\n        }\n        bounds {\n          northeast {\n            latitude\n            longitude\n          }\n          southwest {\n            latitude\n            longitude\n          }\n        }\n      }\n    }\n    hotelSummaryOptions(distanceUnit: $distanceUnit, sortBy: distance) {\n      bounds {\n        northeast {\n          latitude\n          longitude\n        }\n        southwest {\n          latitude\n          longitude\n        }\n      }\n      amenities {\n        id\n        name\n        hint\n      }\n      amenityCategories {\n        name\n        id\n        amenityIds\n      }\n      brands {\n        code\n        name\n      }\n      hotels(first: $queryLimit) {\n        _id: ctyhocn\n        amenityIds\n        brandCode\n        ctyhocn\n        distance\n        distanceFmt\n        facilityOverview {\n          allowAdultsOnly\n        }\n        name\n        display {\n          open\n          openDate\n          preOpenMsg\n          resEnabled\n          resEnabledDate\n        }\n        contactInfo {\n          phoneNumber\n        }\n        address {\n          city\n          country\n          state\n        }\n        localization {\n          coordinate {\n            latitude\n            longitude\n          }\n        }\n        masterImage(variant: searchPropertyImageThumbnail) {\n          altText\n          variants {\n            size\n            url\n          }\n        }\n        leadRate {\n          hhonors {\n            lead {\n              dailyRmPointsRate\n              dailyRmPointsRateNumFmt: dailyRmPointsRateFmt(hint: number)\n              ratePlan {\n                ratePlanName\n                ratePlanDesc\n              }\n            }\n            max {\n              rateAmount\n              rateAmountFmt\n              dailyRmPointsRate\n              dailyRmPointsRateRoundFmt: dailyRmPointsRateFmt(hint: round)\n              dailyRmPointsRateNumFmt: dailyRmPointsRateFmt(hint: number)\n              ratePlan {\n                ratePlanCode\n              }\n            }\n            min {\n              rateAmount(decimal: 1)\n              rateAmountFmt\n              dailyRmPointsRate\n              dailyRmPointsRateRoundFmt: dailyRmPointsRateFmt(hint: round)\n              dailyRmPointsRateNumFmt: dailyRmPointsRateFmt(hint: number)\n              ratePlan {\n                ratePlanCode\n              }\n            }\n          }\n        }\n      }\n    }\n  }\n}","operationName":"geocode_hotelSummaryOptions","variables":{"address":"' . $destination . '","language":"en","placeId":null,"queryLimit":150}}';
        $this->http->PostURL("https://www.hilton.com/graphql/customer?operationName=geocode_hotelSummaryOptions&originalOpName=geocode_hotelSummaryOptions&appName=dx_shop_search_app&bl=en",
            $payload, $headers);
        $geocode = $this->http->JsonLog(null, 0, true);

        $quadrantIds = [];

        foreach ($quadrants['data']['hotelQuadrants'] as $quadrant) {
            foreach ($quadrant['countries'] as $country) {
                if ($country['code'] !== $geocode['data']['geocode']['match']['address']['country']) {
                    continue;
                }

                if (empty($country['states'])) {
                    $quadrantIds[] = $quadrant['id'];

                    continue;
                }

                if (!empty($geocode['data']['geocode']['match']['address']['state']) && !in_array($geocode['data']['geocode']['match']['address']['state'], $country['states'])) {
                    continue;
                }
                $quadrantIds[] = $quadrant['id'];
            }
        }

        return $quadrantIds;
    }

    private function getPointsInfo($fields, $ctyhocnsArr, $headers)
    {
        $this->logger->notice(__METHOD__);
        $checkInStr = date('Y-m-d', $fields['CheckIn']);
        $checkOutStr = date('Y-m-d', $fields['CheckOut']);
        $pointsInfo = [];

        do {
            $ctyhocns = array_slice($ctyhocnsArr, 0, 20);

            $payload = '{"query":"query shopMultiPropAvail($ctyhocns: [String!], $language: String!, $input: ShopMultiPropAvailQueryInput!) {\n  shopMultiPropAvail(input: $input, language: $language, ctyhocns: $ctyhocns) {\n    ageBasedPricing\n    ctyhocn\n    currencyCode\n    statusCode\n    statusMessage\n    lengthOfStay\n    notifications {\n      subType\n      text\n      type\n    }\n    summary {\n      hhonors {\n        dailyRmPointsRate\n        dailyRmPointsRateFmt\n        rateChangeIndicator\n        ratePlan {\n          ratePlanName @toUpperCase\n        }\n      }\n      lowest {\n        cmaTotalPriceIndicator\n        feeTransparencyIndicator\n        rateAmountFmt(strategy: trunc, decimal: 0)\n        rateAmount(currencyCode: \"USD\")\n        ratePlanCode\n        rateChangeIndicator\n        ratePlan {\n          ratePlanName @toUpperCase\n          specialRateType\n          salesRate\n          dogEar\n          confidentialRates\n        }\n        amountAfterTax(currencyCode: \"USD\")\n        amountAfterTaxFmt(decimal: 0, strategy: trunc)\n      }\n      status {\n        type\n      }\n    }\n  }\n}","operationName":"shopMultiPropAvail","variables":{"input":{"guestId":0,"guestLocationCountry":"RU","arrivalDate":"' . $checkInStr . '","departureDate":"' . $checkOutStr . '","numAdults":' . $fields['Adults'] . ',"numChildren":' . $fields['Kids'] . ',"numRooms":' . $fields['Rooms'] . ',"childAges":[13],"ratePlanCodes":[],"rateCategoryTokens":[],"specialRates":{"aaa":false,"aarp":false,"corporateId":"","governmentMilitary":false,"groupCode":"","hhonors":true,"pnd":"","offerId":null,"promoCode":"","senior":false,"travelAgent":false,"teamMember":false,"familyAndFriends":false,"owner":false,"ownerHGV":false}},"ctyhocns":["' . implode('","',
                    $ctyhocns) . '"],"language":"en"}}';

            $this->http->PostURL("https://www.hilton.com/graphql/customer?operationName=shopMultiPropAvail&originalOpName=shopMultiPropAvailPoints&appName=dx_shop_search_app&bl=en",
                $payload, $headers);

            $pointsInfo[] = $this->http->JsonLog(null, 0, true);

            $ctyhocnsArr = array_slice($ctyhocnsArr, 20);
        } while (!empty($ctyhocnsArr));

        return $pointsInfo;
    }

    private function getHotelsInfo($quadrantIds, $headers): array
    {
        $this->logger->notice(__METHOD__);
        $hotelsInfo = [];

        foreach ($quadrantIds as $quadrantId) {
            $payload = '{"query":"query hotelSummaryOptions($language: String!, $input: HotelSummaryOptionsInput) {\n  hotelSummaryOptions(language: $language, input: $input) {\n    hotels {\n      _id: ctyhocn\n      amenityIds\n      brandCode\n      ctyhocn\n      distance\n      distanceFmt\n      facilityOverview {\n        allowAdultsOnly\n      }\n      name\n      display {\n        open\n        openDate\n        preOpenMsg\n        resEnabled\n        resEnabledDate\n      }\n      contactInfo {\n        phoneNumber\n      }\n      disclaimers {\n        desc\n        type\n      }\n      address {\n        addressLine1\n        city\n        country\n        countryName\n        state\n        stateName\n        _id\n      }\n      localization {\n        currencyCode\n        coordinate {\n          latitude\n          longitude\n        }\n      }\n      masterImage(variant: searchPropertyImageThumbnail) {\n        altText\n        variants {\n          size\n          url\n        }\n      }\n      images {\n        carousel(variant: searchPropertyImageThumbnail) {\n          altText\n          variants {\n            size\n            url\n          }\n        }\n      }\n      tripAdvisorLocationSummary {\n        numReviews\n        rating\n        ratingFmt(decimal: 1)\n        ratingImageUrl\n        reviews {\n          id\n          rating\n          helpfulVotes\n          ratingImageUrl\n          text\n          travelDate\n          user {\n            username\n          }\n          title\n        }\n      }\n      leadRate {\n        lowest {\n          rateAmount(currencyCode: \"USD\")\n          rateAmountFmt(decimal: 0, strategy: trunc)\n          ratePlanCode\n          ratePlan {\n            ratePlanName\n            ratePlanDesc\n          }\n        }\n        hhonors {\n          lead {\n            dailyRmPointsRate\n            dailyRmPointsRateNumFmt: dailyRmPointsRateFmt(hint: number)\n            ratePlan {\n              ratePlanName\n              ratePlanDesc\n            }\n          }\n          max {\n            rateAmount\n            rateAmountFmt\n            dailyRmPointsRate\n            dailyRmPointsRateRoundFmt: dailyRmPointsRateFmt(hint: round)\n            dailyRmPointsRateNumFmt: dailyRmPointsRateFmt(hint: number)\n            ratePlan {\n              ratePlanCode\n            }\n          }\n          min {\n            rateAmount\n            rateAmountFmt\n            dailyRmPointsRate\n            dailyRmPointsRateRoundFmt: dailyRmPointsRateFmt(hint: round)\n            dailyRmPointsRateNumFmt: dailyRmPointsRateFmt(hint: number)\n            ratePlan {\n              ratePlanCode\n            }\n          }\n        }\n      }\n    }\n  }\n}","operationName":"hotelSummaryOptions","variables":{"language":"en","input":{"quadrantId":"' . $quadrantId . '"}}}';

            $this->http->PostURL("https://www.hilton.com/graphql/customer?operationName=hotelSummaryOptions&originalOpName=hotelSummaryOptions&appName=dx_shop_search_app&bl=en",
                $payload, $headers);
            $hotelsInfo[] = $this->http->JsonLog(null, 0, true);
        }

        return $hotelsInfo;
    }

    private function parseRespData($pointsInfo, $hotelsInfo, $preParseData)
    {
        //TODO: need optimisation
        $this->logger->notice(__METHOD__);
        $tmpData = [];
        $availableHotels = [];

        foreach ($pointsInfo as $value) {
            foreach ($value['data']['shopMultiPropAvail'] as $pointInfo) {
                if ($pointInfo['summary']['status']['type'] === 'AVAILABLE') {
                    $tmpData[$pointInfo['ctyhocn']]['pointInfo'] = $pointInfo;
                    $availableHotels[] = $pointInfo['ctyhocn'];
                }
            }
        }

        foreach ($hotelsInfo as $value) {
            foreach ($value['data']['hotelSummaryOptions']['hotels'] as $hotelInfo) {
                if (in_array($hotelInfo['_id'], $availableHotels)) {
                    $tmpData[$hotelInfo['_id']]['hotelsInfo'] = $hotelInfo;
                }
            }
        }

        $parseData = [];

        foreach ($tmpData as $id => $hotel) {
            if (isset($hotel['hotelsInfo']['address']['addressLine1'])) {
                $address = $hotel['hotelsInfo']['address']['addressLine1'] . ', ' . $preParseData[$id]['address'];
            } else {
                $address = null;
            }

            $parseData[] = [
                'name'             => $preParseData[$id]['name'],
                'checkInDate'      => $preParseData[$id]['checkInDate'],
                'checkOutDate'     => $preParseData[$id]['checkOutDate'],
                'roomType'         => $preParseData[$id]['roomType'],
                'hotelDescription' => null,
                'numberOfNights'   => null,
                'pointsPerNight'   => $hotel['pointInfo']['summary']['hhonors']['dailyRmPointsRate'] ?? null,
                'cachePerNight'    => $hotel['pointInfo']['summary']['lowest']['amountAfterTax'] ?? null,
                'distance'         => $preParseData[$id]['distance'],
                'rating'           => $hotel['hotelsInfo']['tripAdvisorLocationSummary']['rating'] ?? null,
                'numberOfReviews'  => $hotel['hotelsInfo']['tripAdvisorLocationSummary']['numReviews'] ?? null,
                'address'          => $address,
                'phone'            => $preParseData[$id]['phone'],
            ];
        }

        return array_values($parseData);
    }

    private function parseRespData2($pointsInfo, $headers, $preParseData)
    {
        $this->logger->notice(__METHOD__);
        $tmpData = [];

        foreach ($pointsInfo as $value) {
            foreach ($value['data']['shopMultiPropAvail'] as $pointInfo) {
                if ($pointInfo['summary']['status']['type'] === 'AVAILABLE') {
                    $tmpData[$pointInfo['ctyhocn']]['pointInfo'] = $pointInfo;
                    $hotelInfo = $this->getHotelInfo($pointInfo['ctyhocn'], $headers);
                    $tmpData[$pointInfo['ctyhocn']]['hotelsInfo'] = $hotelInfo['data']['hotel'];
                }
            }
        }

        $parseData = [];

        foreach ($tmpData as $id => $hotel) {
            $preview = null;

            if (isset($hotel['hotelsInfo']['images']['master']['variants']) && $this->downloadPreview) {
                $defaultImgUrl = $hotel['hotelsInfo']['images']['master']['variants'][0]['url'];
                $variants = array_filter(array_map(function ($s) {
                    return $s['size'] === 'md' ? $s['url'] : null;
                }, $hotel['hotelsInfo']['images']['master']['variants']));
                $imgUrl = array_shift($variants) ?? $defaultImgUrl;
                $preview = $this->getBase64FromImageUrl($imgUrl);
            }
            $parseData[] = [
                'name'             => $preParseData[$id]['name'],
                'checkInDate'      => $preParseData[$id]['checkInDate'],
                'checkOutDate'     => $preParseData[$id]['checkOutDate'],
                'roomType'         => $preParseData[$id]['roomType'],
                'hotelDescription' => $hotel['hotelsInfo']['facilityOverview']['shortDesc'] ?? null,
                'numberOfNights'   => null,
                'pointsPerNight'   => $hotel['pointInfo']['summary']['hhonors']['dailyRmPointsRate'] ?? null,
                'cachePerNight'    => $hotel['pointInfo']['summary']['lowest']['amountAfterTax'] ?? null,
                'distance'         => $preParseData[$id]['distance'],
                'rating'           => $hotel['hotelsInfo']['tripAdvisorLocationSummary']['rating'] ?? null,
                'numberOfReviews'  => $hotel['hotelsInfo']['tripAdvisorLocationSummary']['numReviews'] ?? null,
                'address'          => $hotel['hotelsInfo']['address']['addressFmt'] ?? null,
                'detailedAddress'  => [
                    'addressLine' => $hotel['hotelsInfo']['address']['addressLine1'],
                    'city'        => $hotel['hotelsInfo']['address']['city'],
                    'stateName'   => $hotel['hotelsInfo']['address']['state'],
                    'countryName' => $hotel['hotelsInfo']['address']['country'],
                    'postalCode'  => $hotel['hotelsInfo']['address']['postalCode'],
                    'lat'         => $hotel['hotelsInfo']['localization']['coordinate']['latitude'] ?? null,
                    'lng'         => $hotel['hotelsInfo']['localization']['coordinate']['longitude'] ?? null,
                    'timezone'    => null,
                ],
                'phone'   => $hotel['hotelsInfo']['contactInfo']['phoneNumber'] ?? $preParseData[$id]['phone'],
                'url'     => $hotel['hotelsInfo']['facilityOverview']['homeUrl'] ?? null,
                'preview' => $preview,
            ];
        }

        return array_values($parseData);
    }

    private function getBase64FromImageUrl(?string $url): ?string
    {
        if (null === $url) {
            return null;
        }
        $file = $this->http->DownloadFile($url);
        $imageSize = getimagesize($file);
        $imageData = base64_encode(file_get_contents($file));
        $this->logger->debug("<img src='data:{$imageSize['mime']};base64,{$imageData}' {$imageSize[3]} />", ['HtmlEncode'=>false]);

        return $imageData;
    }

    private function getHotelInfo($ctyhocn, $headers)
    {
        // маленький хак. подмена в payload
        // localization {\n      currencyCode\n    }
        // на
        // localization {\n          coordinate {\n            latitude\n            longitude\n          }\n        }
        $payload = '{"query":"query hotel($ctyhocn: String!, $language: String!) {\n  hotel(ctyhocn: $ctyhocn, language: $language) {\n    __typename\n    brandCode\n    ctyhocn\n    facilityOverview {\n      allowAdultsOnly\n      homeUrl\n      homeUrlPathTemplate\n      shortDesc\n    }\n    name\n    display {\n      open\n      openDate\n      preOpenMsg\n      resEnabled\n      resEnabledDate\n    }\n    contactInfo {\n      phoneNumber\n    }\n    disclaimers {\n      desc\n      type\n    }\n    address {\n      _id\n      addressFmt\n      addressLine1\n      city\n      countryName\n      country\n      postalCode\n      state\n      stateName\n    }\n    amenities {\n      id\n      name\n      hint\n    }\n    images {\n      master {\n        altText\n        variants {\n          size\n          url\n        }\n      }\n      carousel(imageVariant: searchPropertyCarousel) {\n        altText\n        variants {\n          size\n          url\n        }\n      }\n    }\n    localization {\n          coordinate {\n            latitude\n            longitude\n          }\n        }\n    tripAdvisorLocationSummary {\n      numReviews\n      rating\n      ratingFmt(decimal: 1)\n      ratingImageUrl\n      reviews {\n        id\n        rating\n        helpfulVotes\n        ratingImageUrl\n        text\n        travelDate\n        user {\n          username\n        }\n        title\n      }\n    }\n    leadRate {\n      lowest {\n        rateAmount(currencyCode: \"USD\")\n        rateAmountFmt(decimal: 0, strategy: trunc)\n        ratePlan {\n          ratePlanDesc\n          ratePlanName\n          ratePlanCode\n        }\n      }\n      hhonors {\n        lead {\n          dailyRmPointsRate\n          dailyRmPointsRateNumFmt: dailyRmPointsRateFmt(hint: number)\n          ratePlan {\n            ratePlanName\n            ratePlanDesc\n          }\n        }\n        max {\n          rateAmount\n          rateAmountFmt\n          dailyRmPointsRate\n          dailyRmPointsRateRoundFmt: dailyRmPointsRateFmt(hint: round)\n          dailyRmPointsRateNumFmt: dailyRmPointsRateFmt(hint: number)\n          ratePlan {\n            ratePlanCode\n          }\n        }\n        min {\n          rateAmount\n          rateAmountFmt\n          dailyRmPointsRate\n          dailyRmPointsRateRoundFmt: dailyRmPointsRateFmt(hint: round)\n          dailyRmPointsRateNumFmt: dailyRmPointsRateFmt(hint: number)\n          ratePlan {\n            ratePlanCode\n          }\n        }\n      }\n    }\n  }\n}","operationName":"hotel","variables":{"language":"en","ctyhocn":"' . $ctyhocn . '"}}';
        $this->http->PostURL("https://www.hilton.com/graphql/customer?operationName=hotel&originalOpName=hotel&appName=dx_shop_search_app&bl=en", $payload, $headers);

        return $this->http->JsonLog(null, 1, true);
    }
}
