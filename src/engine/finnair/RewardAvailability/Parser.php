<?php

namespace AwardWallet\Engine\finnair\RewardAvailability;

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
    private $supportedCurrencies = ['EUR'];
    private $codesForSearch = [];
    private $validRouteProblem;
    private $response;
    private $selenium;
    private $bearer;
    private $debugMode = false;

    public static function getRASearchLinks(): array
    {
        return ['https://www.finnair.com/en'=>'search page'];
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->UseSelenium();

        $this->useGoogleChrome(\SeleniumFinderRequest::CHROME_95);
        $type = 'chrome';

        $request = ($type == 'ff') ? FingerprintRequest::firefox() : FingerprintRequest::chrome();
        //$request->browserVersionMin = 100;
        $request->browserVersionMin = \HttpBrowser::BROWSER_VERSION_MIN - 5;
        $request->platform = (random_int(0, 1)) ? 'MacIntel' : 'Win32';
        $fingerprint = $this->services->get(FingerprintFactory::class)->getOne([$request]);
        $this->setProxyNetNut();
        $this->http->setUserAgent($fingerprint->getUseragent());

        $resolutions = [
            [1152, 864],
            [1152, 864],
            [1280, 720],
            [1280, 768],
            [1280, 800],
            [1360, 768],
            [1366, 768],
        ];

        $chosenResolution = $resolutions[array_rand($resolutions)];
        $this->setScreenResolution($chosenResolution);

        $this->http->saveScreenshots = true;
        $this->disableImages();

        $regions = ['us', 'au', 'ca'];
        $region = $regions[random_int(0, count($regions) - 1)];
        $this->setProxyNetNut(null, $region);

        $this->seleniumRequest->setHotSessionPool(self::class, $this->AccountFields['ProviderCode']);

        $this->KeepState = false;

        $this->logger->notice("Running Selenium...");
    }

    public function getRewardAvailabilitySettings()
    {
        return [
            'supportedCurrencies'      => $this->supportedCurrencies,
            'supportedDateFlexibility' => 0,
            'defaultCurrency'          => 'EUR',
        ];
    }

    public function IsLoggedIn()
    {
        $this->logger->debug("--IsLoggedIn--");

        return false;
    }

    public function LoadLoginForm()
    {
        $this->logger->debug("--LoadLoginForm--");

        return true;
    }

    public function Login()
    {
        $this->logger->debug("--Login--");

        $this->http->GetURL("https://www.finnair.com/en");

        $cookie_allow_all_btn = $this->waitForElement(\WebDriverBy::id('allow-all-btn'), 10);

        if ($cookie_allow_all_btn) {
            $cookie_allow_all_btn->click();
            $this->someSleep();
        }

        $btn_login = $this->waitForElement(\WebDriverBy::xpath('//span[text()="Login"]/ancestor::fin-login-button'), 10);
        $btn_login->click();

        $email = $this->waitForElement(\WebDriverBy::xpath('//fcom-text-input//div[contains(text(),"Email")]/parent::*/following-sibling::input'), 10);
        $email->click();

        $member_id = $this->AccountFields['Login'];
        $email->sendKeys($member_id); //$fields['Login'] '730736113'

        $password = $this->waitForElement(\WebDriverBy::xpath('//fcom-text-input//div[contains(text(),"Password")]/parent::*/following-sibling::input'), 10);
        $password->click();
        $pass = $this->AccountFields['Pass'];
        $password->sendKeys($pass); //$fields['Login'] 'flglF!7#fHf'

        $btn_submit = $this->waitForElement(\WebDriverBy::xpath('//fcom-checkbox/following-sibling::fcom-button'), 10);
        $btn_submit->click();

        $failed_sing_in = $this->waitForElement(\WebDriverBy::xpath('//fcom-notification//span[contains(text(),"Login failed")]'), 10);

        if ($failed_sing_in) {
            $this->logger->debug("--Sign in failed--");

            return false;
        } else {
            $this->logger->debug("--Sign in Successful--");

            return true;
        }
    }

    public function ParseRewardAvailability(array $fields)
    {
        $this->logger->info("Parse Reward Availability", ['Header' => 2]);
        $this->logger->debug('Params: ' . var_export($fields, true));

        if (in_array($fields['Currencies'][0], $this->getRewardAvailabilitySettings()['supportedCurrencies']) !== true) {
            $fields['Currencies'][0] = $this->getRewardAvailabilitySettings()['defaultCurrency'];
            $this->logger->notice("parse with defaultCurrency: " . $fields['Currencies'][0]);
        }

        if ($fields['Adults'] > 7) {
            $this->SetWarning("It's too much travellers");

            return [];
        }

        try {
            $result = $this->loadPage($fields);
        } catch (\WebDriverCurlException | \WebDriverException | \UnrecognizedExceptionException | \NoSuchDriverException
        | \NoSuchWindowException | \TimeOutException | \StaleElementReferenceException
        | \Facebook\WebDriver\Exception\WebDriverCurlException | UnexpectedResponseException
        | \Facebook\WebDriver\Exception\UnknownErrorException
        | \Facebook\WebDriver\Exception\WebDriverException
        | \Facebook\WebDriver\Exception\UnrecognizedExceptionException $e) {
            $this->logger->error(get_class($e) . ": " . $e->getMessage());
            $this->logger->error($e->getTraceAsString());
        }

        if (is_string($result)) {
            $this->logger->info('load page error');
            $this->SetWarning($result);

            return [];
        }

        return $result;
    }

    private function loadPage(array $fields, $isRetry = false)
    {
        $this->http->GetURL("https://www.finnair.com/en");

        $award_flight = $this->waitForElement(\WebDriverBy::xpath('//fcom-carousel//span[contains(text(),"Award flight")]/parent::*'), 10);
        $award_flight->click();

        $this->someSleep();
        $btn_trip_type = $this->waitForElement(\WebDriverBy::xpath('//button[@type="button"][@data-testid="booking-widget-selector-modal"]'), 10);
        $btn_trip_type->click();

        $oneWay = $this->waitForElement(\WebDriverBy::xpath('//fcom-modal/descendant::input[@id="tripType-oneway"]/following-sibling::label'), 10);
        $this->someSleep();
        $oneWay->click();

        $btn_from = $this->waitForElement(\WebDriverBy::xpath('//fin-location-selector//span[contains(text(),"From")]/ancestor::fin-booking-widget-selector'), 10);
        $this->someSleep();
        $btn_from->click();

        $depCode = $this->waitForElement(\WebDriverBy::xpath('//input[@id="locationSearch-input"]'), 10);
        $this->someSleep();
        $depCode->click();
        $depCode->sendKeys($fields['DepCode']);
        $this->someSleep();
        $this->saveResponse();

        $depCity = $this->waitForElement(\WebDriverBy::xpath('//ul[@id="to-location-origin"]//button[@aria-selected="true"]'), 10);
        $this->someSleep();
        $depCity->click();

        $btn_to = $this->waitForElement(\WebDriverBy::xpath('//fin-location-selector//span[contains(text(),"To")]/ancestor::fin-booking-widget-selector[@data-testid="location-selector-destination"]'), 0);
        $btn_to->click();
        $this->saveResponse();

        $arrCode = $this->waitForElement(\WebDriverBy::xpath('//input[@id="locationSearch-input"]'), 0);

        $arrCode->click();
        $arrCode->sendKeys($fields['ArrCode']);
        $this->someSleep();

        $arrCity = $this->waitForElement(\WebDriverBy::xpath('//ul[@id="to-location-destination"]//button[@tabindex="0"]'), 10);
        $arrCity->click();

        $btn_date = $this->waitForElement(\WebDriverBy::xpath('//fin-travel-dates-selector//span[contains(text(),"Depart")]//ancestor::fin-booking-widget-selector'), 0);
        $btn_date->click();

        $beautyDate = date("d.m.Y", $fields['DepDate']);

        $depDate = $this->waitForElement(\WebDriverBy::xpath('//fcom-datepicker//input[@name="startDate"]'), 0);
        $depDate->click();
        $depDate->sendKeys($beautyDate);

        $btn_done = $this->waitForElement(\WebDriverBy::xpath('//fcom-button//span[contains(text(),"Done")]/ancestor::button'), 0);

        $btn_done->click();

        /*
            // неверно работает - увеличивает количество adults до 9 вместо заданного
        $passengersAndClass = $this->waitForElement(\WebDriverBy::xpath('//fin-pax-amount-selector//button/parent::*'), 0);
        $this->someSleep();
        $passengersAndClass->click();
        //$this->someSleep();

        $this->savePageToLogs($this);
        $this->saveResponse();


        $adults_to_click = (int) $fields['Adults'] - 1; // у нас уже есть 1 Adult по умолчанию на форме
        $this->waitForElement(\WebDriverBy::xpath('//fcom-modal//div[contains(text(),"adult")]//ancestor::li//button[@aria-label="Add an adult"]'), 10);

        for ($i = 0; $adults_to_click; $i++) {
            $adults = $this->driver->findElement(\WebDriverBy::xpath('//fcom-modal//div[contains(text(),"adult")]//ancestor::li//button[@aria-label="Add an adult"]'));
            $adults->click();
            //$this->someSleep();
        }
        */

        $btn_search = $this->waitForElement(\WebDriverBy::xpath('//fcom-button//span[contains(text(),"Search")]/ancestor::button'), 10);
        $this->someSleep();
        $btn_search->click();

        $cellPrice = $this->waitForElement(\WebDriverBy::xpath('//fin-price-matrix//table//td[contains(@class,"selected")]'), 10);
        $price = $cellPrice->getText();

        $found_results = $this->http->FindPreg('/[\d]/', false, $price);

        if (is_null($found_results)) {
            $this->SetWarning("No results found for given search term");
        }

        $btn_search_details = $this->waitForElement(\WebDriverBy::xpath('//fcom-button[@data-testid="aircalendar-continue-button"]//span[contains(text(),"Search")]/ancestor::button'), 10);
        $btn_search_details->click();

        $this->waitForElement(\WebDriverBy::xpath('//fin-bound-selection-list//ul/li[contains(@class,"flight-item")]'), 10);

        $btnsViewDetails = $this->driver->findElements(\WebDriverBy::xpath('//fcom-ufo//button'), 10);

        $routes = [];   // выхлоп

        $depFullDate = gmdate('Y-m-d', $fields['DepDate']);

        foreach ($btnsViewDetails as $k => $el) {
            $el->click();
            $this->savePageToLogs($this);
            $this->saveResponse();

            $this->http->FilterHTML = false;
            $this->http->SetBody($this->http->Response['body'] = preg_replace("#\b(ns|xmlns)\b=\"[^\"]+\"#i", '', $this->http->Response['body']));

            $numStops = 0;

            $root = $this->http->XPath->query('//fin-itinerary-timeline-flight | //fin-itinerary-timeline-layover');

            if (isset($connections)) {
                unset($connections);
            }

            $connections = [];
            $previous_flight = null;    // контроль смены борта
            $fl_layover = false;    // флаг остановки между сегментами;

            foreach ($root as $n) {
                $curr = $n->tagName;

                if ($curr == 'fin-itinerary-timeline-layover') { // если нашли остановку то флаг и  идем к след сегменту
                    $numStops++;
                    $fl_layover = true;

                    continue;
                }

                $depTime = $this->http->FindSingleNode('.//fin-itinerary-timeline-leg-endpoint//span[contains(@class,"itinerary-endpoint-time")]', $n, true, '/[0-9]{2}:[0-9]{2}/', 0);
                $arrTime = $this->http->FindSingleNode('.//fin-itinerary-timeline-leg-endpoint//span[contains(@class,"itinerary-endpoint-time")]', $n, true, '/[0-9]{2}:[0-9]{2}/', 1);
                $depLocation = $this->http->FindSingleNode('.//fin-itinerary-timeline-leg-endpoint//span[contains(@class,"itinerary-endpoint-location")]', $n, true, '/\((.+)\)/', 0);
                $arrLocation = $this->http->FindSingleNode('.//fin-itinerary-timeline-leg-endpoint//span[contains(@class,"itinerary-endpoint-location")]', $n, true, '/\((.+)\)/', 1);

                $depTerminal = $this->http->FindSingleNode('.//span[contains(text(),"Terminal")]/parent::*/span[2]', $n, true, '/[0-9A-Za-z]{1,4}/'); // terminal Departure number if exists
                $arrTerminal = $this->http->FindSingleNode('.//p[contains(text(),"Terminal")]', $n, true, '/Terminal.*?([0-9A-Za-z]{1,4}).*?$/'); // terminal Arrival number if exists

                $flightDetails = $this->http->XPath->query('.//p[@data-test-flight-details]', $n)[0];
                $flight = $this->http->FindSingleNode('.//span[1]', $flightDetails);

                if (($fl_layover) && ($previous_flight == $flight)) { // остановка без смены борта
                    $connections[array_key_last($connections)]["num_stops"] = 1; // поменяем значение в предыдущем сегменте

                    $fl_layover = false; // сбросим флаг
                }

                $previous_flight = $flight;

                $airline = $this->http->FindSingleNode('.//span[1]', $flightDetails, true, '/^[A-Za-z]{2,4}/');

                $operatedBy = $this->http->FindSingleNode('.//span[2]', $flightDetails);
                $aircraft = $this->http->FindSingleNode('.//span[3]', $flightDetails);

                $connections[] = [
                    "num_stops" => 0,
                    "departure" => [
                        "date"     => $depFullDate . " " . $depTime,
                        "airport"  => $depLocation,
                        "terminal" => (!is_null($depTerminal)) ? $depTerminal : "",
                    ],
                    "arrival" => [
                        "date"     => $depFullDate . " " . $arrTime,
                        "airport"  => $arrLocation,
                        "terminal" => (!is_null($arrTerminal)) ? $arrTerminal : '',
                    ],
                    "cabin"    => "", // тут не знаю что ставить
                    "flight"   => [$flight],
                    "airline"  => $airline,
                    "operator" => $operatedBy,
                    "aircraft" => $aircraft,
                ];
            }
            $routes[] = [
                "num_stops"   => $numStops,
                "redemptions" => $this->AccountFields['ProviderCode'],
                "connections" => $connections,
            ];

            $btnClose = $this->waitForElement(\WebDriverBy::xpath('//fin-booking-ticket-selection-view//button[contains(@class,"close-button")]'), 10);

            $btnClose->click();
            $this->someSleep();
        }
        $this->logger->debug(var_export($routes, true), ['pre' => true]);

        return $routes;
    }

    private function someSleep()
    {
        usleep(random_int(12, 25) * 100000);
    }
}
