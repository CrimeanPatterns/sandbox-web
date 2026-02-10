<?php

namespace AwardWallet\Engine\ichotelsgroup\RewardAvailability;

use AwardWallet\Engine\ProxyList;
use AwardWallet\Engine\Settings;

class HotelParser extends \TAccountChecker
{
    use ProxyList;
    use \SeleniumCheckerHelper;

    private $fields;
    private $headers;

    public static function getRASearchLinks(): array
    {
        return ['https://www.ihg.com/hotels/us/en/reservation' => 'search page'];
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

        if ($fields['Rooms'] > 1) {
            $this->SetWarning('When redeeming points for a Reward Night, you may only book one room at a time. Once you complete your Reward Night reservation you will be given the option to duplicate this reservation.');

            return ['hotels' => []];
        }

        if ($fields['CheckOut'] == $fields['CheckIn']) {
            $this->SetWarning('You can’t book a day-use room.');

            return ['hotels' => []];
        }

        if (($fields['Adults'] + $fields['Kids']) > 8) {
            $this->SetWarning('Maximum: 8 total guests');

            return ['hotels' => []];
        }

        $this->fields = $fields;
        $this->fields['Nights'] = ($fields['CheckOut'] - $fields['CheckIn']) / 24 / 60 / 60;
        $this->logger->debug('Nights: ' . $this->fields['Nights']);

        $response = $this->selenium();

        if (!$response) {
            if ($this->ErrorCode == ACCOUNT_WARNING) {
                return ['hotels' => []];
            }

            throw new \CheckRetryNeededException(5, 0);
        }

        return ['hotels' => $this->parseData($response)];
    }

    private function selenium()
    {
        $this->logger->notice(__METHOD__);
        $selenium = clone $this;
        $this->http->brotherBrowser($selenium->http);

        try {
            $this->logger->notice("Running Selenium...");
            $selenium->UseSelenium();
            $selenium->useChromePuppeteer(\SeleniumFinderRequest::CHROME_PUPPETEER_103);
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

            $seleniumDriver = $selenium->http->driver;

            $query = $this->makeSearchString();

            $selenium->http->GetURL("https://www.ihg.com/hotels/us/en/find-hotels/hotel-search?{$query}");

            if ($selenium->http->Response['code'] == 403
                || $selenium->waitForElement(\WebDriverBy::xpath("//h1[contains(text(),'Access Denied')]"), 5)
            ) {
                throw new \CheckRetryNeededException(5, 0);
            }

            if ($msg = $selenium->waitForElement(\WebDriverBy::xpath("//span[contains(text(),'sorry, but we do not have any hotels in that exact location')]"), 10)) {
                $selenium->saveResponse();
                $this->SetWarning($msg->getText());

                return null;
            }
            $XPATH_LOAD = '
                    //div[@data-slnm-ihg="numberOfHotelsInList"]
                    | //div[contains(text(),"Hotel Found")]
                    | //div[contains(text(),"Hotels Found")]
                    ';
            $load = $selenium->waitForElement(\WebDriverBy::xpath($XPATH_LOAD), 40);

            if (!$load) {
                $this->sendNotification('check load // ZM');
                $selenium->http->GetURL("https://www.ihg.com/hotels/us/en/find-hotels/hotel-search?{$query}");
                $load = $selenium->waitForElement(\WebDriverBy::xpath($XPATH_LOAD), 30);

                if (!$load) {
                    throw new \CheckException('something wrong', ACCOUNT_ENGINE_ERROR);
                }
            }

            if ($load->getText() === '0 Hotel Found') {
                $this->SetWarning('No hotels available');

                return null;
            }

            $requests = $seleniumDriver->browserCommunicator->getRecordedRequests();

            $hotels = null;
            $hotelsProfile = [];

            foreach ($requests as $xhr) {
                if (strpos($xhr->request->getUri(), 'availability/v3/hotels/offers') !== false) {
                    $resp = $xhr->response->getBody();

                    if (isset($resp['hotels'])) {
                        $hotels = $resp['hotels'];
                    }
                }

                if (strpos($xhr->request->getUri(), 'hotels/v1/profiles/') !== false) {
                    $resp = $xhr->response->getBody();

                    if (isset($resp['hotelInfo'])) {
                        $hotelsProfile[] = $resp['hotelInfo'];
                    }
                }
            }
            $selenium->saveResponse();

            if (!$hotels || empty($hotelsProfile)) {
                throw new \CheckException('Empty data from xhr, check it!', ACCOUNT_ENGINE_ERROR);
            }

            return $this->prepareData($hotels, $hotelsProfile);
        } catch (
        \WebDriverCurlException
        | \WebDriverException
        | \Facebook\WebDriver\Exception\WebDriverCurlException
        | \Facebook\WebDriver\Exception\WebDriverException $e
        ) {
            $this->logger->error($e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        } finally {
            // close Selenium browser
            $selenium->http->cleanup();
        }

        return null;
    }

    private function makeSearchString()
    {
        $this->logger->notice(__METHOD__);

        $monthCi = date('n', $this->fields['CheckIn']) - 1;
        $yearCi = date('Y', $this->fields['CheckIn']);
        $monthCo = date('n', $this->fields['CheckOut']) - 1;
        $yearCo = date('Y', $this->fields['CheckOut']);

        $queryParams = [
            'qDest' => $this->fields['Destination'],
            'qCiD'  => date('j', $this->fields['CheckIn']),
            'qCiMy' => $monthCi . $yearCi,
            'qCoD'  => date('j', $this->fields['CheckOut']),
            'qCoMy' => $monthCo . $yearCo,
            'qRms'  => $this->fields['Rooms'],
            'qAdlt' => $this->fields['Adults'],
            'qChld' => $this->fields['Kids'],
            'qRad'  => 100,
            'qRdU'  => 'mi',
            'qRtP'  => 'IVANI',
            'qPt'   => 'POINTS',
        ];

        return http_build_query($queryParams);
    }

    private function prepareData($hotels, $hotelsProfile)
    {
        $this->logger->notice(__METHOD__);
        $hotelsWithPoints = [];
        $skipped = false;

        $this->logger->notice('hotels');
        $this->http->JsonLog(json_encode($hotels), 1);

        $this->logger->notice('hotelsProfile');
        $this->http->JsonLog(json_encode($hotelsProfile), 1);

        foreach ($hotels as $hotel) {
            if (!isset($hotel['lowestPointsOnlyCost'])) {
                $skipped = true;

                continue;
            }

            $hotelsWithPoints[$hotel['hotelMnemonic']] = [
                'lowestPointsOnlyCost' => $hotel['lowestPointsOnlyCost'],
                'lowestCashOnlyCost'   => $hotel['lowestCashOnlyCost'],
                'propertyCurrency'     => $hotel['propertyCurrency'],
                'distance'             => $hotel['distance'] ?? null,
            ];
        }

        foreach ($hotelsProfile as $profile) {
            if (!isset($hotelsWithPoints[$profile['brandInfo']['mnemonic']])) {
                continue;
            }

            $hotelsWithPoints[$profile['brandInfo']['mnemonic']] += [
                'brandName' => $profile['brandInfo']['brandName'],
                'profile'   => [
                    'name'          => $profile['profile']['name'],
                    'averageReview' => $profile['profile']['averageReview'] ?? null,
                    'totalReviews'  => $profile['profile']['totalReviews'] ?? null,
                    'latitude'      => $profile['profile']['latitude'],
                    'longitude'     => $profile['profile']['longitude'],
                ],
                'policies' => [
                    'checkinTime'  => $profile['policies']['checkinTime'],
                    'checkoutTime' => $profile['policies']['checkoutTime'],
                ],
                'facilities' => $profile['facilities'],
                'address'    => $profile['address'],
                //                'contact'    => $profile['contact'],
                'imgUrl'     => $profile['profile']['primaryImageUrl']['originalUrl'] ?? null,
            ];
        }

        if (empty($hotelsWithPoints) && $skipped) {
            $this->SetWarning('No hotels available');

            return null;
        }

        return $hotelsWithPoints;
    }

    private function parseData($response)
    {
        $this->logger->notice(__METHOD__);

        $result = [];

        $num = 0;

        foreach ($response as $hotel) {
            $this->logger->debug('hotel #' . $num++);
            $name = $hotel['brandName'] . ' ' . $hotel['profile']['name'];
            $chekIn = substr(date('Y-m-d', $this->fields['CheckIn']) . ' ' . $hotel['policies']['checkinTime'], 0, -3);
            $chekOut = substr(date('Y-m-d', $this->fields['CheckOut']) . ' ' . $hotel['policies']['checkoutTime'], 0, -3);
            $description = $this->makeDescription($hotel['facilities']);
            $address = $this->makeAddress($hotel['address']);

            $preview = null;

            if ($this->fields['DownloadPreview']) {
                $imgUrl = $hotel['imgUrl'] . '?wid=340&fit=constrain';
                $this->logger->warning($imgUrl);
                $preview = $this->getBase64FromImageUrl($imgUrl);
            }

            $res = [
                'name'             => ($name !== ' ') ? $name : null,
                'checkInDate'      => $chekIn,
                'checkOutDate'     => $chekOut,
                'roomType'         => null,
                'hotelDescription' => $description,
                'numberOfNights'   => $this->fields['Nights'],
                'pointsPerNight'   => $hotel['lowestPointsOnlyCost']['points'],
                'cashPerNight'     => $hotel['lowestCashOnlyCost']['amountAfterTax'],
                'currency'         => $hotel['propertyCurrency'],
                'distance'         => $hotel['distance'],
                'rating'           => $hotel['profile']['averageReview'],
                'numberOfReviews'  => $hotel['profile']['totalReviews'],
                'address'          => $address,
                'detailedAddress'  => [
                    'addressLine'  => $hotel['address']['street1'] ?? null,
                    'city'         => $hotel['address']['city'] ?? null,
                    'stateName'    => $hotel['address']['state']['name'] ?? null,
                    'countryName'  => $hotel['address']['country']['name'] ?? null,
                    'postalCode'   => $hotel['address']['zip'] ?? null,
                    'lat'          => $hotel['profile']['latitude'] ?? null,
                    'lng'          => $hotel['profile']['longitude'] ?? null,
                    'timezone'     => null,
                ],
                //                'phone'            => $hotel['contact'][0]['frontDeskNumber'] ?? null,
                'preview'          => $preview,
            ];
            $this->logger->debug(var_export($res, true), ['pre' => true]);
            $result[] = $res;
        }

        return $result;
    }

    private function makeDescription($data)
    {
        $description = '';

        foreach ($data as $item) {
            $description .= $item['name'] . ', ';
        }

        return (strlen($description) > 0) ? substr($description, 0, -2) : null;
    }

    private function makeAddress($data)
    {
        $address = (isset($data['street1'])) ? $data['street1'] . ', ' : '';
        $address .= (isset($data['city'])) ? $data['city'] . ', ' : '';
        $address .= (isset($data['state']) && isset($data['state']['name'])) ? $data['state']['name'] . ', ' : '';
        $address .= (isset($data['zip'])) ? $data['zip'] . ', ' : '';
        $address .= (isset($data['country']) && isset($data['country']['name'])) ? $data['country']['name'] . ', ' : '';

        return (strlen($address) > 0) ? substr($address, 0, -2) : null;
    }

    private function getBase64FromImageUrl(?string $url): ?string
    {
        if (null === $url) {
            return null;
        }
        $this->logger->info('download image: ' . $url);
        $http2 = clone $this->http;
        $file = $http2->DownloadFile($url);
        $imageSize = getimagesize($file);

        if (!$imageSize) {
            return null;
        }
        $imageData = base64_encode(file_get_contents($file));
        $this->logger->debug("<img src='data:{$imageSize['mime']};base64,{$imageData}' {$imageSize[3]} />", ['HtmlEncode'=>false]);

        return $imageData;
    }
}
