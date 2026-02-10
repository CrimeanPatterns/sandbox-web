<?php

// Is extended in "tapportugal"
use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Common\Selenium\FingerprintFactory;
use AwardWallet\Common\Selenium\FingerprintRequest;
use AwardWallet\Engine\ProxyList;

class TAccountCheckerAviancataca extends TAccountChecker
{
    use SeleniumCheckerHelper;
    use ProxyList;
    public $membershipNumber = null;
    /**
     * @var CaptchaRecognizer
     */
    private $recognizer;

    private $headers = [
        "Accept"        => "application/json",
        "Content-Type"  => "application/json",
        "Origin"        => "https://www.lifemiles.com",
        "Referer"       => "https://www.lifemiles.com/",
        "realm"         => "lifemiles",
    ];
    private $selenium = true;
    private $seleniumURL = null;

    public static function GetAccountChecker($accountInfo)
    {
        if (!empty($accountInfo['ConfNo']) && !empty($accountInfo['Code'])) {
            return new static();
        }

        if (!empty($accountInfo['ProviderCode']) && $accountInfo['ProviderCode'] != 'aviancataca') {
            return new static();
        }

        require_once __DIR__ . "/TAccountCheckerAviancatacaSelenium.php";

        return new TAccountCheckerAviancatacaSelenium();
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->setHttp2(true);

        if ($this->AccountFields['ProviderCode'] != 'tapportugal') {
            if ($this->attempt > 0) {
                $this->setProxyGoProxies();
            } else {
                $this->http->SetProxy($this->proxyDOP());
            }
        }
    }

    public function IsLoggedIn()
    {
        if (!isset($this->State['membershipNumber'])) {
            return false;
        }

        $this->http->RetryCount = 0;
        $this->membershipNumber = $this->State['membershipNumber'];
        $this->http->RetryCount = 2;

        if ($this->loginSuccessful($this->State['access_token'], 30)) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        if (strstr($this->AccountFields['Login'], '@')) {
            throw new CheckException("Your username cannot include special characters, try using your LifeMiles number. For assistance contact our Call Center (option 3).", ACCOUNT_INVALID_PASSWORD);
        }

        $this->http->removeCookies();
        //$this->http->setCookie("defConf", "%7B%22language%22%3A%22en%22%2C%22country%22%3A%22us%22%2C%22currency%22%3A%22usd%22%7D", "www.lifemiles.com");
//        $this->http->PostURL("https://www.lifemiles.com/lifemiles/home", '{"internationalization":{"language":"en","country":"us"}}');
        //$this->http->GetURL("https://www.lifemiles.com/");

//        $this->botDetectionWorkaround();

        /*if ($this->http->Response['code'] != 200) {
            return $this->checkErrors();
        }*/

        return $this->selenium();
//        $this->http->GetURL("https://www.lifemiles.com/integrator/v1/authentication/oauth/authorize?client_id=lm_website&redirect_uri=https%3A%2F%2Fwww.lifemiles.com%2Foauth-signin&scope=read&response_type=token&tab_id=V4YmbsJA6DM&state=%7B%27Access-Level%27%3A%20%270%27%2C%20%27Redirect-Uri%27%3A%20%27%27%7D");
//        if (!$this->http->ParseForm(null, "//form[contains(@action, 'authentication/login')]"))
//            return $this->checkErrors();
        $this->http->SetInputValue("username", $this->AccountFields['Login']);
        $this->http->SetInputValue("password", $this->AccountFields['Pass']);
        $this->http->SetInputValue("remember_me", "true");
        $this->http->SetInputValue("ot", "");
        $this->http->SetInputValue("fingerprint", date("UB"));
        $this->http->FormURL = 'https://www.lifemiles.com/integrator/v1/authentication/login';

        return true;
    }

    public function Login()
    {
        if ($this->selenium === false && !$this->http->PostForm()) {
            return $this->checkErrors();
        }

        $authorization =
            $this->http->getCookieByName("dra3j", "www.lifemiles.com")
            ?? $this->http->FindPreg("/access_token=([^&]+)/", false, $this->http->currentUrl())
            ?? $this->http->FindPreg("/access_token=([^&]+)/", false, $this->seleniumURL)
        ;
        $this->logger->debug("[authorization]: '{$authorization}'");
        $authorizationParts = explode('.', $authorization);

        foreach ($authorizationParts as $str) {
            $str = base64_decode($str);
            $this->logger->debug($str);

            if ($this->membershipNumber = $this->http->FindPreg("/\"lm-id\":\"([^\"]+)/", false, $str)) {
                break;
            }
        }// foreach ($authorizationParts as $str)

        if (isset($authorization, $this->membershipNumber)) {
            $this->State['access_token'] = $authorization;
            $this->State['membershipNumber'] = $this->membershipNumber;

            if ($this->loginSuccessful($this->State['access_token'])) {
                return true;
            }

            if (
                strstr($this->http->Error, 'Network error 28 - Operation timed out after')
                || ($this->http->Response['code'] == 403 && $this->http->FindPreg("/^nil$/"))
            ) {
                throw new CheckRetryNeededException(3, 0);
            }

            if ($this->http->FindSingleNode('//h1[contains(text(), "502 - Bad Gateway")]')) {
                throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
            }

            // it helps in selenium auth
            $this->botDetectionWorkaround();

            if ($this->loginSuccessful($this->State['access_token'])) {
                return true;
            }

            return false;
        }// if (isset($authorization, $this->membershipNumber))

        if ($this->checkCredentials()) {
            return false;
        }

        return $this->checkErrors();
    }

    public function checkCredentials()
    {
        $this->logger->notice(__METHOD__);

        if ($message = $this->http->FindSingleNode('//p[contains(@class, "GeneralErrorModal_description")]')) {
            $this->logger->error("[Error]: {$message}");

            if (
                strstr($message, "The information you provided does not match our records. ")
                || strstr($message, "To log in, send an email to seguridaddecuentas@lifemiles.com. Error code: 10990")
                || strstr($message, "Para acceder a tu cuenta, restablece tu contraseña. Código de error")
                || strstr($message, "La información proporcionada no coincide con nuestros registros.")
                || strstr($message, "Para acceder a tu cuenta, escribe a seguridaddecuentas@lifemiles.com.")
                || strstr($message, "To log in, send an email to seguridaddecuentas@lifemiles.com.")
                || strstr($message, "Los datos proporcionados no coinciden con nuestros registros.")
            ) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            if (
                strstr($message, "To log in, reset your password. Error code: 10980")
                || strstr($message, "Para acceder a tu cuenta, contacta nuestro Call Center")
                || $message == 'Necesitas restablecer tu contraseña para acceder a tu cuenta.'
                || $message == 'En este momento no hemos podido acceder a tu cuenta. Para obtener asistencia, te invitamos a visitar nuestro Centro de Ayuda.'
            ) {
                throw new CheckException($message, ACCOUNT_LOCKOUT);
            }

            if (strstr($message, "To log in, contact our Call Center (option 3) or send an email to support@lifemiles.com.")) {
                throw new CheckException("To log in, contact our Call Center (option 3) or send an email to support@lifemiles.com. Error code: 11040", ACCOUNT_PROVIDER_ERROR);
            }

            $this->DebugInfo = $message;

            return true;
        }

        // Tu información es incorrecta. Intenta de nuevo. Código:1732
        if ($message = $this->http->FindSingleNode("
                //p[contains(text(), 'Tu información es incorrecta. Intenta de nuevo. Código:')]
                | //h1[contains(text(), 'Tu contraseña ha expirado.')]
                | //p[contains(text(), 'Tu contraseña ha expirado, restablécela aquí. Código:')]
                | //p[contains(text(), 'Necesitas') and a[contains(., 'reestablecer tu contraseña')] and contains(., ', pues ésta ha expirado. Código:1733')]
            ")
        ) {
            throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }
        // An error has occurred.Code:999
        if ($message = $this->http->FindSingleNode("
                //p[contains(text(), 'An error has occurred.Code:999') or contains(text(), 'Ha ocurrido un error. Código:999')]
                | //p[contains(text(), 'This application has no explicit mapping for /error, so you are seeing this as a fallback.')]
            ")
        ) {
            throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }
        // La cuenta ha sido bloqueada por superar el número de intentos. Código:1098
        if ($message = $this->http->FindSingleNode("
                //p[contains(text(), 'La cuenta ha sido bloqueada por superar el número de intentos')]
                | //p[contains(text(), 'La cuenta ha sido bloqueada. Código:1099')]
                | //p[contains(text(), 'Cuenta deshabilitada. Código:1104')]
            ")
        ) {
            throw new CheckException($message, ACCOUNT_LOCKOUT);
        }

        // hard code    -> https://d296xu67oj0g2g.cloudfront.net/webpack/prd/app-2ac9734ec28eae0734a8.js
        if ($this->seleniumURL == 'https://www.lifemiles.com/sign-in/1732') {
            throw new CheckException('Tu información es incorrecta. Intenta de nuevo. Código:1732', ACCOUNT_INVALID_PASSWORD);
        }

        if ($this->seleniumURL == 'https://www.lifemiles.com/sign-in/1097') {
            throw new CheckException('Tu información es incorrecta. Intenta de nuevo. Código:1097', ACCOUNT_INVALID_PASSWORD);
        }

        if ($this->seleniumURL == 'https://www.lifemiles.com/sign-in/1098') {
            throw new CheckException('La cuenta ha sido bloqueada por superar el número de intentos. Código:1098', ACCOUNT_INVALID_PASSWORD);
        }

        if ($this->seleniumURL == 'https://www.lifemiles.com/sign-in/1104') {
            throw new CheckException('Cuenta deshabilitada. Código:1104', ACCOUNT_LOCKOUT);
        }

        if ($this->seleniumURL == 'https://www.lifemiles.com/sign-in/1099') {
            throw new CheckException('La cuenta ha sido bloqueada. Código:1099', ACCOUNT_LOCKOUT);
        }

        // AccountID: 6508550
        if (strstr($this->seleniumURL, 'https://sso.lifemiles.com/auth/realms/lifemiles/broker/after-first-broker-login?session_code=')) {
            throw new CheckException('We are sorry... An internal server error has occurred', ACCOUNT_PROVIDER_ERROR);
        }
        /*
        if ($this->seleniumURL == 'https://www.lifemiles.com/sign-in/1106') {
            throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
        }
        */

        if ($this->seleniumURL == 'https://www.lifemiles.com/sign-in') {
            throw new CheckRetryNeededException(2, 0);
        }

        return false;
    }

    public function Parse()
    {
        $response = $this->http->JsonLog(null, 0);
        // Balance - You have ... miles
        $this->SetBalance($response->user->lifeMiles ?? null);
        // Name
        if (isset($response->user->firstName, $response->user->lastName)) {
            $this->SetProperty("Name", beautifulName($response->user->firstName . " " . $response->user->lastName));
        }
        // Elite qualifying segments
        $this->SetProperty("EliteQualifyingSegments", $response->user->miles->qualifiedMiles->segments ?? null);
        // Elite qualifying miles
        $this->SetProperty("EliteQualifyingMiles", $response->user->miles->qualifiedMiles->totalRegularMilesAVSTAR ?? null);
        // Qualifying miles with Avianca (For Elite Level Tab)  // refs #11713
        $this->SetProperty('QualifyingMilesAviancaTaca', $response->user->miles->qualifiedMiles->avianca ?? null);
        // Status expiration
        $expirationDate = $response->user->expirationDate ?? null;

        if (!empty($expirationDate)) {
            $this->SetProperty("StatusExpiration", date("j M Y", strtotime($expirationDate)));
        }
        // Miles expiration date
        $accountPointExpiryDate = $response->user->accountPointExpiryDate ?? null;

        if ($accountPointExpiryDate && ($expDate = strtotime($accountPointExpiryDate))) {
            if ($expDate !== false) {
                $this->SetExpirationDate($expDate);
            }
        }

        $data = [
            "membershipNumber"     => $this->membershipNumber,
            "internationalization" => [
                "countryCode"  => "us",
                "language"     => "en",
                "currencyCode" => "usd",
            ],
            "currencyInfo" => [
                "code"      => "USD",
                "decimals"  => 0,
                "rateToUsd" => 1,
            ],
        ];
        $this->http->PostURL("https://api.lifemiles.com/svc/account-overview", json_encode($data));
        $response = $this->http->JsonLog(null, 0);
        $profileInfo = $response->profileInfo ?? [];

        foreach ($profileInfo as $property) {
            switch ($property->type) {
                case 'memberNo':
                    // LM Number
                    $this->SetProperty("Number", $property->value);

                    break;

                case 'eliteStatus':
                    // Elite status
                    $this->SetProperty("EliteStatus", $property->value);

                    break;

                case 'memberSince':
                    // Member since
                    $this->SetProperty("MemberSince", $property->value);

                    break;

                case 'lifetimeEarnings':
                    // Lifetime earnings
                    $this->SetProperty("HistoricEarnedMiles", $property->value);

                    break;

                case 'lifetimeRedemptions':
                    // Lifetime redemptions
                    $this->SetProperty("HistoricRedeemedMiles", $property->value);

                    break;
            }
        }

        // Expiration date  // refs #17653
        if ($this->Balance > 0 && !isset($this->Properties['AccountExpirationDate'])) {
            $this->parseExpirationDate();
        }// if ($this->Balance > 0)
    }

    // refs #17653
    public function parseExpirationDate()
    {
        $this->logger->info('Expiration date', ['Header' => 3]);
        $this->http->SetProxy($this->proxyReCaptchaIt7());
        $browser = clone $this;
        $this->http->brotherBrowser($browser->http);
        $browser->http->GetURL("https://www.avianca.com/eu/en/");

        if (!$browser->http->ParseForm(null, '//form[contains(@class, "form-login")]')) {
            $this->sendNotification("aviancataca - refs #17653. Expiration date not found");

            return;
        }
        $headers = [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept'           => 'application/json, text/javascript, */*; q=0.01',
            'Content-Type'     => 'application/x-www-form-urlencoded; charset=UTF-8',
            'CSRF-Token'       => 'undefined',
        ];
        $data = [
            'user'          => $this->membershipNumber,
            'password'      => $this->AccountFields['Pass'],
            'createSession' => '/create_session',
        ];
        $browser->http->RetryCount = 0;
        $browser->http->PostURL("https://www.avianca.com/bin/myaccount", $data, $headers);
        $browser->http->RetryCount = 2;
        $response = $browser->http->JsonLog();
        $siteExpirationDate = $response->milesExpirationDate ?? null;

        if (!$siteExpirationDate) {
            $this->logger->error("milesExpirationDate not found");

            return;
        }
        $expiryDate = $this->http->FindPreg("/(.+)000$/", false, $siteExpirationDate);

        if ($expiryDate) {
            $this->SetExpirationDate($expiryDate);
        }
    }

    public function GetConfirmationFields()
    {
        return [
            "ConfNo" => [
                "Caption"  => "Confirmation #",
                "Type"     => "string",
                "Size"     => 20,
                "Required" => true,
            ],
            "LastName" => [
                "Type"     => "string",
                "Size"     => 40,
                "Value"    => $this->GetUserField('LastName'),
                "Required" => true,
            ],
        ];
    }

    public function confNotification($arFields, $mess = "failed to retrieve itinerary by conf #")
    {
        $this->logger->notice(__METHOD__);
        $confNo = ArrayVal($arFields, 'ConfNo');
        $lastName = ArrayVal($arFields, 'LastName');
        $accountId = ArrayVal($this->AccountFields, 'AccountID');

        $body = [
            "Conf #: <a target='_blank' href='https://awardwallet.com/manager/loyalty/logs?ConfNo={$confNo}'>{$confNo}</a>",
            "Name: {$lastName}",
            "AccountID: <a target='_blank' href='https://awardwallet.com/manager/loyalty/logs?AccountID={$accountId}'>{$accountId}</a>",
        ];
        $body = implode('<br/>', $body);
        $this->sendNotification($mess, 'all', true, $body);

        return null;
    }

    // Is extended in "tapportugal"
    public function ParseItineraryConfirmation($arFields, $provider = 'aviancataca')
    {
        $this->logger->notice(__METHOD__);
        $config = $this->http->FindPreg('/config\s*:\s*(.+?)\n/');
        $data = $this->http->JsonLog($config, 0, true);

        if (!isset($data['pageDefinitionConfig']['pageData']['business']['RESERVATION_INFO'])) {
            if (isset($data['pageDefinitionConfig']['pageData']['errorList']['globalErrors']['E'])) {
                $this->logger->debug(var_export($data['pageDefinitionConfig']['pageData']['errorList']['globalErrors']['E'], true));
                $globalErrors = $data['pageDefinitionConfig']['pageData']['errorList']['globalErrors']['E'];

                if (is_array($globalErrors) && count($globalErrors) > 0 && isset($globalErrors[0]['message'])) {
                    $this->logger->error(var_export($globalErrors[0]['message'], true));

                    return $globalErrors[0]['message'];
                }
                $this->confNotification($arFields, "{$provider} - not empty errorList from site"); //for debug

                return null;
            }
            $timeout = $this->http->FindPreg('/Operation timed out/', false, $this->http->Response['errorMessage']);

            if ($timeout) {
                $this->confNotification($arFields, "failed to retrieve itinerary by conf # // MI");
            }

            return null;
        }

        // from /css/IMPLibDxCustomAV.js logic
        $hasTickets = isset($data['pageDefinitionConfig']['pageData']['business']['RESERVATION_INFO']['listAirTicket']) && count($data['pageDefinitionConfig']['pageData']['business']['RESERVATION_INFO']['listAirTicket']) > 0;

        if ($hasTickets === false) {
            return "Your reservation has not been confirmed. For more information please call our Call center";
        }

        $reservationInfo = $data['pageDefinitionConfig']['pageData']['business']['RESERVATION_INFO'];
        $f = $this->itinerariesMaster->add()->flight();
        $confNo = ArrayVal($reservationInfo, 'locator', null);
        $this->logger->info(sprintf('Parse Air #%s', $confNo), ['Header' => 3]);

        // watchdog workaround
        if ($this->http->Response['code'] == 200 && $confNo) {
            $this->increaseTimeLimit();
        }

        $f->general()
            ->confirmation($confNo, 'Booking reference')
            ->date2(ArrayVal($reservationInfo, 'creationDate'));

        $status = $this->arrayVal($data, ['pageDefinitionConfig', 'pageData', 'business', 'FLIGHT_TIMELINE', 'itineraryStatusName']);

        if ($status === 'Confirmed') {
            $f->general()->status($status);
        } else {
            $this->sendNotification("{$provider} - New itinerary Status {$status}");
        }
//        if (in_array($f->getStatus(), ['Canceled'])) {
//            $f->general()->cancelled();
//        }

        $passengers = [];
        $liTravellerInfo = ArrayVal($reservationInfo, 'liTravellerInfo', []);

        foreach ($liTravellerInfo as $traveller) {
            $identity = ArrayVal($traveller, 'identity');

            if (!$identity) {
                continue;
            }
            $passengers[] = trim(sprintf('%s %s %s',
                ArrayVal($identity, 'titleName'),
                ArrayVal($identity, 'firstName'),
                ArrayVal($identity, 'lastName')
            ));
        }
        $f->general()->travellers($passengers, true);

        $travellers = ArrayVal($data['pageDefinitionConfig']['pageData']['business']['TravellerList'], 'Travellers', []);
        $this->logger->debug("count of travelers: " . count($travellers));
        $numbers = [];

        foreach ($travellers as $traveller) {
            $frequentFlyer = ArrayVal($traveller, 'FrequentFlyer', []);
            $this->logger->debug("count of FrequentFlyer: " . count($frequentFlyer));

            if (!isset($frequentFlyer[0]['FREQ_Airline'])) {
                continue;
            }
            $numbers[] = ArrayVal($frequentFlyer[0], 'FREQ_Airline') . " " . ArrayVal($frequentFlyer[0], 'FREQ_Number');
        }
        $f->setAccountNumbers(array_unique(array_filter($numbers)), false);

        $listEMD = ArrayVal($reservationInfo, 'listEMD', []);

        foreach ($listEMD as $EMD) {
            $f->addTicketNumber(ArrayVal(ArrayVal($EMD, 'faFh'), 'documentNumber'), false);
        }

        if (isset($data['pageDefinitionConfig']['pageData']['business']['Price'])) {
            $price = $data['pageDefinitionConfig']['pageData']['business']['Price'];

            if (isset($price['currency']['code'])) {
                $f->price()->currency($price['currency']['code']);
            }

            if (isset($price['totalAmount']['amount'])) {
                $f->price()->total(PriceHelper::cost(round($price['totalAmount']['amount'], 2)));
            }

            if (isset($price['totalTaxes']['amount'])) {
                $f->price()->tax(PriceHelper::cost(round($price['totalTaxes']['amount'], 2)));
            }

            if (isset($price['totalAirlineCharges']['amount'])) {
                $cost = round($price['totalAirlineCharges']['amount'], 2);
                $this->logger->notice("[Cost]: {$cost}");

                if ($cost > 0) {
                    $f->price()->cost(PriceHelper::cost($cost));
                }
            }
        }

        $listItinerary = $this->arrayVal($data, ['pageDefinitionConfig', 'pageData', 'business', 'ItineraryList', 'listItinerary'], []);
        $this->logger->info(sprintf('count listItinerary: %s', count($listItinerary)));

        foreach ($listItinerary as $itinerary) {
            $listSegment = $this->arrayVal($itinerary, ['listSegment'], []);
            $this->logger->debug("Total " . count($listSegment) . " segments were found");

            foreach ($listSegment as $segment) {
                $s = $f->addSegment();

                $s->departure()
                    ->code($segment['beginLocation']['locationCode'])
                    ->terminal(ArrayVal($segment, 'beginTerminal'), true)
                    ->date2(ArrayVal($segment, 'beginDate'));

                $s->arrival()
                    ->code($segment['endLocation']['locationCode'])
                    ->terminal(ArrayVal($segment, 'endTerminal'), true)
                    ->date2(ArrayVal($segment, 'endDate'));

                $s->airline()
                    ->name($segment['airline']['code'])
                    ->number(ArrayVal($segment, 'flightNumber'));

                $s->extra()
                    ->cabin($segment['listCabin'][0]['name'])
                    ->aircraft($segment['equipment']['name'], true);

                if (isset($segment['segmentTime'])) {
                    $duration = $segment['segmentTime'] / 1000 / 3600;
                    // 03h05m
                    $s->extra()->duration(sprintf('%02dh%02dm', (int) $duration, fmod($duration, 1) * 60));
                }

                $s->setSeats($this->getSeatsBySegment($data, $segment['id'] ?? null));
            }// foreach ($listSegment as $segment)
        }// foreach ($listItinerary as $itinerary)

        $this->logger->debug('Parsed Itinerary:');
        $this->logger->debug(var_export($f->toArray(), true), ['pre' => true]);

        return null;
    }

    public function ConfirmationNumberURL($arFields)
    {
        return 'https://www.avianca.com/eu/en/';
    }

    public function CheckConfirmationNumberInternal($arFields, &$it)
    {
        $this->logger->notice(__METHOD__);
        $this->http->LogHeaders = true;
        $this->setProxyGoProxies(null, 'ar');
        //$this->http->setRandomUserAgent();
        $result = $this->seleniumRetrieve("https://cambiatuitinerario.avianca.com/ATC/redirectServlet?pais=EU&lan=en&pnr={$arFields['ConfNo']}&apellido={$arFields['LastName']}", $arFields);

        if ($result === false) {
            throw new CheckRetryNeededException(2, 3);
        }

        if (is_string($result) && !str_starts_with($result, 'http')) {
            return $result;
        }

        if ($result === null) {
            return null;
        }

        $this->http->GetURL($result);
        $error = $this->ParseItineraryConfirmation($arFields);

        if (is_string($error)) {
            return $error;
        }

        if ($error === null) {
            $urlFlightSearch = $this->http->FindSingleNode("//input[@id='urlFlightSearch']/@value");
            $data = [
                'reservationNumber' => $arFields['ConfNo'],
                'passengerName'     => $arFields['LastName'],
                'lan'               => 'en',
                'pais'              => 'EU',
                'app'               => null,
            ];
            $this->http->PostURL("https://cambiatuitinerario.avianca.com/ATC/{$urlFlightSearch}", json_encode($data));
            $response = $this->http->JsonLog();

            if (isset($response->message) && strstr($response->message,
                    'ERROR Número de reserva inválido. Por favor verifícalo e ingrésalo nuevamente')) {
                return 'Invalid reservation code. Please verify and enter your code again.';
            }
        }

        return null;
    }

    public function loginSuccessfulSelenium($access_token, $timeout = 60)
    {
        return $this->loginSuccessful($access_token, $timeout);
    }

    public function checkErrorsSelenium()
    {
        return $this->checkErrors();
    }

    protected function parseReCaptcha()
    {
        $this->logger->notice(__METHOD__);
        $key =
            $this->http->FindSingleNode('//form[div[contains(@class, "g-recaptcha")]]/@data-sitekey')
            ?? $this->http->FindSingleNode('//div[@class = "captcha-mid"]/form/div[@class = "g-recaptcha"]/@data-sitekey')
        ;

        $method = "googlekey";

        if (!$key && ($key = $this->http->FindSingleNode('//div[@class = "captcha-mid"]/form/div[@class = "h-captcha"]/@data-sitekey'))) {
            $method = "hcaptcha";
        }

        if (!$key) {
            return false;
        }

        $this->recognizer = $this->getCaptchaRecognizer(self::CAPTCHA_RECOGNIZER_RUCAPTCHA);
        $this->recognizer->RecognizeTimeout = 120;
        $parameters = [
            "method"  => $method,
            "pageurl" => $this->http->currentUrl(),
            "proxy"   => $this->http->GetProxy(),
        ];

        return $this->recognizeByRuCaptcha($this->recognizer, $key, $parameters);
    }

    private function loginSuccessful($access_token, $timeout = 60)
    {
        $this->logger->notice(__METHOD__);
        $data = [
            "internationalization" => [
                "language"     => "en",
                "countryCode"  => "us",
                "currencyCode" => "usd",
            ],
            "memberInformation" => [
                "membershipNumber" => $this->membershipNumber,
            ],
        ];
        $headers = array_merge($this->headers, ["authorization" => "Bearer {$access_token}"]);
        $this->http->RetryCount = 0;
        $this->http->PostURL("https://api.lifemiles.com/svc/account-user-login", json_encode($data), $headers, $timeout);
        $this->http->RetryCount = 2;

        $this->botDetectionWorkaround();

        if (
            $this->http->FindSingleNode('//b[contains(text(), "Please solve this CAPTCHA to request unblock to the website")]')
            && $this->http->ParseForm(null, '//form[div[contains(@class, "g-recaptcha")]]', false)
        ) {
            $this->DebugInfo = "request has been blocked";
            $this->ErrorReason = self::ERROR_REASON_BLOCK;
        }

        $response = $this->http->JsonLog();

        if (isset($response->user)) {
            foreach ($this->headers as $header => $value) {
                $this->http->setDefaultHeader($header, $value);
            }
            $this->http->setDefaultHeader("authorization", "Bearer {$access_token}");

            return true;
        } elseif (isset($response->title, $response->message)) {
            $this->logger->error($response->title);
            $this->logger->error($response->message);

            if ($response->title == 'An error has occurred.') {
                throw new CheckException($response->title . " " . $response->message, ACCOUNT_PROVIDER_ERROR);
            }
        }// elseif (isset($response->title, $response->message))

        return false;
    }

    private function seleniumRetrieve($url, $arFields)
    {
        $this->logger->notice(__METHOD__);
        $selenium = clone $this;
        $this->http->brotherBrowser($selenium->http);

        try {
            $this->logger->notice("Running Selenium...");
            $selenium->UseSelenium();
            $selenium->useGoogleChrome(SeleniumFinderRequest::CHROME_100);
            $selenium->seleniumOptions->addAntiCaptchaExtension = true;
            $selenium->seleniumOptions->antiCaptchaProxyParams = $this->getCaptchaProxy();
            $selenium->seleniumOptions->addHideSeleniumExtension = false;
            //$selenium->http->removeCookies();
            //$selenium->disableImages();

            $request = FingerprintRequest::chrome();
            $request->browserVersionMin = \HttpBrowser::BROWSER_VERSION_MIN;
            $fingerprint = $selenium->services->get(FingerprintFactory::class)->getOne([$request]);

            if ($fingerprint !== null) {
                $selenium->seleniumOptions->fingerprint = $fingerprint->getFingerprint();
                $selenium->http->setUserAgent($fingerprint->getUseragent());
            }

            $selenium->http->saveScreenshots = true;
            $resolutions = [
                [1280, 800],
                [1366, 768],
                //[1920, 1080],
            ];
            $chosenResolution = $resolutions[array_rand($resolutions)];
            $selenium->setScreenResolution($chosenResolution);
            $selenium->http->start();
            $selenium->Start();
            $selenium->http->GetURL('https://www.avianca.com/eu/en/');
            sleep(3);
            $selenium->http->GetURL($url);

            if ($selenium->waitForElement(WebDriverBy::xpath("//h3[contains(.,'Ticket information search')]"), 10)) {
                $confNo = $selenium->waitForElement(WebDriverBy::xpath("//input[@name = 'reservationNumber']"), 0);
                $lastName = $selenium->waitForElement(WebDriverBy::xpath("//input[@name = 'passengerName']"), 0);
                $btn = $selenium->waitForElement(WebDriverBy::id("contador-button"), 0);
                $this->saveToLogs($selenium);

                if (isset($confNo, $lastName, $btn)) {
                    $confNo->sendKeys($arFields['ConfNo']);
                    $confNo->sendKeys($arFields['LastName']);
                    $btn->click();
                    $error = $selenium->waitForElement(WebDriverBy::xpath("//li[contains(@class,'globalError ng-scope')]/a"), 10);

                    if ($error) {
                        return $error->getText();
                    }
                }

                return null;
            }

            if (!$selenium->waitForElement(WebDriverBy::xpath("//h2[contains(.,'Manage my booking')]"), 110)) {
                return false;
            }
            $this->saveToLogs($selenium);
            $cookies = $selenium->driver->manage()->getCookies();

            foreach ($cookies as $cookie) {
                $this->http->setCookie($cookie['name'], $cookie['value'], $cookie['domain'], $cookie['path'], $cookie['expiry'] ?? null);
            }

            return $selenium->http->currentUrl();
        } catch (StaleElementReferenceException $e) {
            $this->logger->error("StaleElementReferenceException exception: " . $e->getMessage());
        } finally {
            // close Selenium browser
            $selenium->http->cleanup();
        }

        return false;
    }

    private function saveToLogs($selenium)
    {
        // save page to logs
        $selenium->http->SaveResponse();
        // save page to logs
        $this->http->SetBody($selenium->driver->executeScript('return document.documentElement.innerHTML'));
        $this->http->SaveResponse();
    }

    private function selenium()
    {
        $this->logger->notice(__METHOD__);
        $startTimer = $this->getTime();
        $selenium = clone $this;
        $this->http->brotherBrowser($selenium->http);
        $retry = false;

        try {
            $this->http->brotherBrowser($selenium->http);
            $this->logger->notice("Running Selenium...");
            $selenium->UseSelenium();

            if ($this->attempt == 1) {
                $selenium->useChromium();
            } elseif ($this->attempt == 2) {
                $selenium->useGoogleChrome(\SeleniumFinderRequest::CHROME_99);
            } else {
                $selenium->useFirefox();
                $selenium->setKeepProfile(true);

                $request = FingerprintRequest::firefox();
                $request->browserVersionMin = \HttpBrowser::BROWSER_VERSION_MIN;
                $fingerprint = $this->services->get(FingerprintFactory::class)->getOne([$request]);

                if ($fingerprint !== null) {
                    $selenium->seleniumOptions->fingerprint = $fingerprint->getFingerprint();
                    $selenium->seleniumOptions->setResolution([$fingerprint->getScreenWidth(), $fingerprint->getScreenHeight()]);
                    $selenium->http->setUserAgent($fingerprint->getUseragent());
                }
            }

            $selenium->disableImages();
//            $selenium->useCache();
            $selenium->http->saveScreenshots = true;
            $selenium->http->start();
            $selenium->Start();
            $selenium->http->GetURL('https://www.lifemiles.com/account/overview');
            $this->getTime($startTimer);

            $resXpath = "
                //input[@id = 'username']
                | //a[@id = 'social-Lifemiles']
                | //p[contains(text(), 'activity and behavior on this site made us think that you are a bot')]
                | //p[contains(text(), 'activity and behavior on our site made us think you could be a robot')]
                | //*[self::h1 or self::span][contains(text(), 'This site can’t be reached')]
                | //h1[contains(text(), 'Vive tus millas,')]
            ";

            try {
                $selenium->waitForElement(WebDriverBy::xpath($resXpath), 10);
            } catch (Facebook\WebDriver\Exception\StaleElementReferenceException $e) {
                $this->logger->error("Exception: " . $e->getMessage());
                sleep(3);
                $selenium->waitForElement(WebDriverBy::xpath($resXpath), 7);
            }

            $this->savePageToLogs($selenium);

            if ($selenium->waitForElement(WebDriverBy::xpath("//h1[contains(text(), 'Vive tus millas,')]"), 0, false)) {
                $this->logger->notice("try to load login form one more time");
                $selenium->http->GetURL("https://www.lifemiles.com/integrator/v1/authentication/oauth/authorize?client_id=lm_website&redirect_uri=https%3A%2F%2Fwww.lifemiles.com%2Foauth-signin&response_type=token&state=%7B%27Access-Level%27%3A%20%270%27%2C%20%27Redirect-Uri%27%3A%20%27%27%7D&scope=read");
                $selenium->waitForElement(WebDriverBy::xpath($resXpath), 10);

                $this->savePageToLogs($selenium);
            }

            $acceptButton = $selenium->waitForElement(WebDriverBy::xpath("
               //button[contains(@class, 'CookiesBrowserAlert_acceptButton')]
            "), 0);

            if ($loginWithUsername = $selenium->waitForElement(WebDriverBy::xpath("//a[@id = 'social-Lifemiles']"), 0)) {
                $loginWithUsername->click();
                $selenium->waitForElement(WebDriverBy::xpath($resXpath), 10);
                $this->savePageToLogs($selenium);
            }

            if ($acceptButton) {
                $this->savePageToLogs($selenium);

                try {
                    $acceptButton->click();
                } catch (UnrecognizedExceptionException $e) {
                    $this->logger->error("Exception: " . $e->getMessage());
                }
            }

            // waiting for full form loading
            $selenium->waitForElement(WebDriverBy::xpath('//button[contains(@class, "Button_button__") and span[contains(text(), "Log in") or contains(text(), "Ingresar")]]'), 10);

            $login = $selenium->waitForElement(WebDriverBy::xpath("//input[@id = 'username']"), 0);

            // may be too long loading
            if (!$login && $selenium->waitForElement(WebDriverBy::xpath('//img[@alt="loading..."]'), 0)) {
                $login = $selenium->waitForElement(WebDriverBy::xpath("//input[@id = 'username']"), 20);
                $this->savePageToLogs($selenium);
            }

            $pass = $selenium->waitForElement(WebDriverBy::xpath("//input[@id = 'password']"), 0);
            /*
            $rememberMe = $selenium->waitForElement(WebDriverBy::xpath("//label[@for = 'Keep-me-login-confirm']"), 0);
            */
            $this->savePageToLogs($selenium);

            if (!$login || !$pass) {
                $this->logger->error("something went wrong");
                $this->logger->debug("[Current URL]: {$selenium->http->currentUrl()}");

                if (!$selenium->waitForElement(WebDriverBy::xpath("//p[contains(text(), 'our activity and behavior on this site made us think that you are a bot')] | //p[contains(text(), 'activity and behavior on our site made us think you could be a robot')]"), 0)) {
                    $selenium->http->GetURL("https://www.lifemiles.com");
                }
                $this->savePageToLogs($selenium);
                // Currently this service is not available due to maintenance work.
                if ($message = $this->http->FindSingleNode("//p[contains(text(), 'Currently this service is not available due to maintenance work.')]")) {
                    throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
                }

                if (
                    $this->http->FindSingleNode('//div[@id = "homepage-ui-app"]//img[@alt="loading..."]/@alt')
                    || $this->http->FindSingleNode('//div[@id = "root"]//img[@alt="loading..."]/@alt')
                    || $this->http->FindSingleNode('//p[contains(text(), "Lo sentimos, no pudimos realizar tu solicitud, por favor intenta nuevamente. Si el problema persiste contacta a nuestro Call Center")]')
                    || $this->http->FindSingleNode('//p[contains(text(), "Sorry, we could not process your request, please try again.")]')
                    || $this->http->FindSingleNode("//*[self::h1 or self::span][contains(text(), 'This site can’t be reached')]")
                    || $selenium->http->currentUrl() == 'https://www.lifemiles.com/integrator/v1/authentication/oauth/authorize?client_id=lm_website&redirect_uri=https%3A%2F%2Fwww.lifemiles.com%2Foauth-signin&response_type=token&state=%7B%27Access-Level%27%3A%20%270%27%2C%20%27Redirect-Uri%27%3A%20%27%27%7D&scope=read'
                    || $selenium->http->currentUrl() == 'https://www.lifemiles.com/sign-in'
                ) {
                    $retry = true;
                }

                return false;
            }

            try {
                $this->logger->debug("click by login");
                $login->click();
                // selenium trace workaround
            } catch (Facebook\WebDriver\Exception\StaleElementReferenceException | StaleElementReferenceException $e) {
                $this->logger->error("Exception: " . $e->getMessage());
                $this->savePageToLogs($selenium);

                sleep(5);
                $login = $selenium->waitForElement(WebDriverBy::xpath("//input[@id = 'username']"), 0);
                $pass = $selenium->waitForElement(WebDriverBy::xpath("//input[@id = 'password']"), 0);
            }

            $this->logger->debug("clear login");
            $login->clear();
            $this->logger->debug("set login");
            $login->sendKeys($this->AccountFields['Login']);

            $this->logger->debug("click by pass");
            $pass->click();
            $pass->clear();
            $this->logger->debug("set pass");
            $pass->sendKeys($this->AccountFields['Pass']);

            /*
            if ($rememberMe) {
                $rememberMe->click();
            }
            */
            $btn = $selenium->waitForElement(WebDriverBy::xpath('//button[contains(@class, "Button_button__") and not(@disabled) and span[contains(text(), "Log in") or contains(text(), "Ingresar")]] | //button[not(@disabled) and @id = "Login-confirm"]'), 5);
            $this->savePageToLogs($selenium);

            if (!$btn) {
                $this->logger->error("something went wrong");

                if ($this->http->FindSingleNode("//input[@id = 'username']/following-sibling::p[contains(@class, 'authentication-ui-Input_imageInvalid')]/@class")) {
                    throw new CheckException("Your User ID or Password is incorrect. Please try again.", ACCOUNT_INVALID_PASSWORD);
                }

                return false;
            }
            $btn->click();
            $loginSuccessXpath = "
                //div[contains(@class, 'AccountActivityCard_userId')]
                | //p[contains(text(), 'our activity and behavior on this site made us think that you are a bot')]
                | //p[contains(text(), 'activity and behavior on our site made us think you could be a robot')]
                | //p[contains(@class, 'GeneralErrorModal_description')]
                | //button[contains(@class, 'authentication-ui-InitialPage_buttonDontShow')]
                | //h1[contains(text(), 'Confirma tu identidad')]
            ";
            $loginSuccess = $selenium->waitForElement(WebDriverBy::xpath($loginSuccessXpath), 15);

            if ($this->cancel2faSetup($loginSuccess, $selenium)) {
                $loginSuccess = $selenium->waitForElement(WebDriverBy::xpath($loginSuccessXpath), 15);
            }

            try {
                $conditions = !$loginSuccess && $selenium->waitForElement(WebDriverBy::xpath('//img[@alt="loading..."]'), 0);
            } catch (
                Facebook\WebDriver\Exception\StaleElementReferenceException
                | StaleElementReferenceException
                $e
            ) {
                $this->logger->error("Exception: " . $e->getMessage());
                $this->savePageToLogs($selenium);
                $conditions = $selenium->waitForElement(WebDriverBy::xpath('//img[@alt="loading..."]'), 0);
            }

            // may be too long loading
            if ($conditions) {
                $loginSuccess = $selenium->waitForElement(WebDriverBy::xpath($loginSuccessXpath), 20);
            }
            $this->savePageToLogs($selenium);

            if (!$loginSuccess) {
                $selenium->http->GetURL("https://www.lifemiles.com/");
                $this->savePageToLogs($selenium);

                if ($message = $this->http->FindSingleNode("//p[contains(text(), 'Currently this service is not available due to maintenance work.')]")) {
                    throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
                }
            }// if (!$loginSuccess && strstr($selenium->http->currentUrl(), 'https://oauth.lifemiles.com/terms?consent_challenge='))

            $this->savePageToLogs($selenium);

            $cookies = $selenium->driver->manage()->getCookies();

            foreach ($cookies as $cookie) {
                $this->http->setCookie($cookie['name'], $cookie['value'], $cookie['domain'], $cookie['path'], $cookie['expiry'] ?? null);
            }
            $this->seleniumURL = $selenium->http->currentUrl();
            $this->logger->debug("[Current URL]: {$this->seleniumURL}");

            // 500
            if (
                $this->seleniumURL == 'https://www.lifemiles.com/integrator/v1/authentication/login'
                && !$this->http->getCookieByName("dra3j", "www.lifemiles.com")
            ) {
                throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
            }
//        } catch (UnexpectedJavascriptException $e) {
//            $this->logger->error("UnexpectedJavascriptException exception: " . $e->getMessage());
//            $retry = true;
        } catch (
            TimeOutException
            | NoSuchWindowException
            | Facebook\WebDriver\Exception\UnknownErrorException
            | Facebook\WebDriver\Exception\WebDriverCurlException
            $e
        ) {
            $this->logger->error("Exception: " . $e->getMessage());
            $retry = true;
        } finally {
            // close Selenium browser
            $selenium->http->cleanup(); // todo

            if ($this->http->FindSingleNode("
                    //p[contains(text(), 'our activity and behavior on this site made us think that you are a bot')]
                    | //p[contains(text(), 'activity and behavior on our site made us think you could be a robot')]
                    | //*[self::h1 or self::span][contains(text(), 'This site can’t be reached')]
                ")
            ) {
                $retry = true;
            }

            if ($retry && ConfigValue(CONFIG_SITE_STATE) != SITE_STATE_DEBUG) {
                $this->logger->debug("[attempt]: {$this->attempt}");

                if (
                    $this->attempt == 2
                    && ($message = $this->http->FindSingleNode('//p[contains(text(), "Lo sentimos, no pudimos realizar tu solicitud, por favor intenta nuevamente. Si el problema persiste contacta a nuestro Call Center")] | //p[contains(text(), "Sorry, we could not process your request, please try again.")]'))
                ) {
                    throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
                }

                throw new CheckRetryNeededException(3);
            }
        }

        $this->getTime($startTimer);

        return true;
    }

    private function checkErrors()
    {
        $this->logger->notice(__METHOD__);
        // 503 Service Temporarily Unavailable
        if (
            $this->http->FindSingleNode("//h1[contains(text(), '503 Service Temporarily Unavailable')]")
            || $this->http->FindSingleNode("//h2[contains(text(), 'The requested URL could not be retrieved')]")
        ) {
            throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
        }

        return false;
    }

    private function arrayVal($ar, $indices, $default = null)
    {
        if (is_string($indices)) {
            $indices = [$indices];
        }

        $res = $ar;

        foreach ($indices as $index) {
            if (isset($res[$index])) {
                $res = $res[$index];
            } else {
                $this->logger->debug('Invalid indices:');
                $this->logger->debug(var_export($indices, true));

                return $default;
            }
        }

        if (is_string($res)) {
            $res = trim($res);
        }

        return $res;
    }

    private function getSeatsBySegment($data, $id)
    {
        $this->logger->notice(__METHOD__);

        if (!$id) {
            return [];
        }

        $res = [];
        $services = $this->arrayVal($data, ['pageDefinitionConfig', 'pageData', 'business', 'ServiceSelectionBreakdown', 'servicesBySegment', "$id"], []);

        foreach ($services as $service) {
            $code = $service['code'] ?? null;

            if ($code !== 'SIT') {
                continue;
            }
            $listServices = $service["listServices"] ?? [];

            foreach ($listServices as $listService) {
                $seat = $listService['seat'] ?? null;

                if ($seat) {
                    $res[] = $seat;
                }
            }
        }

        return $res;
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

    private function botDetectionWorkaround()
    {
        $this->logger->notice(__METHOD__);

        if (!$this->http->ParseForm(null, '//div[@class = "captcha-mid"]/form')) {
            return false;
        }
        $captcha = $this->parseReCaptcha();

        if ($captcha === false) {
            return false;
        }

//        $this->http->SetInputValue("recaptcha_response", $captcha);
        $this->http->SetInputValue("g-recaptcha-response", $captcha);
        $this->http->SetInputValue("h-captcha-response", $captcha);
        $headers = [
            'Accept'          => '*/*',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Content-Type'    => 'application/x-www-form-urlencoded',
            'Origin'          => 'https://validate.perfdrive.com',
            'Referer'         => $this->http->currentUrl(),
        ];

        if (!$this->http->PostForm($headers)) {
            return false;
        }

        if ($this->http->FindSingleNode('//p[contains(text(), "However, your activity and behavior still make us think that you are a bot. We request you to try accessing the site/app after sometime.")]')) {
            $this->State['botDetectionWorkaround'] = true;
            $this->markProxyAsInvalid();

            throw new CheckRetryNeededException(3, 0);
        }

        return true;
    }

    private function cancel2faSetup($button, $selenium)
    {
        if (!$button || !str_contains($button->getAttribute('class'), 'authentication-ui-InitialPage_buttonDontShow')) {
            return false;
        }
        $button->click();
        $label = $selenium->waitForElement(WebDriverBy::xpath('//label[contains(@class, "authentication-ui-MfaTerms_labelCheckbox")]'), 1);

        if (!$label) {
            $this->logger->error('label for 2fa terms not found');

            return false;
        }
        $label->click();
        $button = $selenium->waitForElement(WebDriverBy::xpath('//button[@class="authentication-ui-Button_button authentication-ui-Button_buttonBlue"]'), 0);
        $this->saveToLogs($selenium);

        if (!$button) {
            $this->logger->error('button for 2fa cancel not found');

            return false;
        }
        $button->click();
        $button = $selenium->waitForElement(WebDriverBy::xpath('//button[@class="authentication-ui-Button_button authentication-ui-Button_buttonBlue authentication-ui-VerificationMfaModal_buttonModal"]'), 5);
        $this->saveToLogs($selenium);

        if (!$button) {
            $this->logger->error('button in modal for 2fa cancel not found');

            return false;
        }
        $button->click();

        return true;
    }
}
