<?php

use AwardWallet\Common\Selenium\FingerprintFactory;
use AwardWallet\Common\Selenium\FingerprintRequest;
use AwardWallet\Engine\ProxyList;

class TAccountCheckerAviancatacaSelenium extends TAccountChecker
{
    use SeleniumCheckerHelper;
    use ProxyList;
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
    private $membershipNumber = null;
    private $selenium = true;
    private $seleniumURL = null;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->useSelenium();
        $this->KeepState = true;
        $this->http->setHttp2(true);
        $this->setProxyGoProxies();

        $this->http->saveScreenshots = true;
        $this->disableImages();

        if ($this->attempt == 1) {
            $this->useChromium();
        } elseif ($this->attempt == 2) {
            $this->useGoogleChrome(\SeleniumFinderRequest::CHROME_99);
        } else {
            $this->useFirefox();
            $this->setKeepProfile(true);

            $request = FingerprintRequest::firefox();
            $request->browserVersionMin = \HttpBrowser::BROWSER_VERSION_MIN;
            $fingerprint = $this->services->get(FingerprintFactory::class)->getOne([$request]);

            if ($fingerprint !== null) {
                $this->seleniumOptions->fingerprint = $fingerprint->getFingerprint();
                $this->seleniumOptions->setResolution([$fingerprint->getScreenWidth(), $fingerprint->getScreenHeight()]);
                $this->http->setUserAgent($fingerprint->getUseragent());
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

        $aviancataca = $this->getAviancataca();

        if ($aviancataca->loginSuccessfulSelenium($this->State['access_token'], 30)) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL('https://www.lifemiles.com/account/overview');

        $resXpath = "
            //input[@id = 'username']
            | //a[@id = 'social-Lifemiles']
            | //p[contains(text(), 'activity and behavior on this site made us think that you are a bot')]
            | //p[contains(text(), 'activity and behavior on our site made us think you could be a robot')]
            | //*[self::h1 or self::span][contains(text(), 'This site can’t be reached')]
            | //h1[contains(text(), 'Vive tus millas,')]
        ";
        $this->waitForElement(WebDriverBy::xpath($resXpath), 10);
        $this->saveResponse();

        if ($this->waitForElement(WebDriverBy::xpath("//h1[contains(text(), 'Vive tus millas,')]"), 0, false)) {
            $this->logger->notice("try to load login form one more time");
            $this->http->GetURL("https://www.lifemiles.com/integrator/v1/authentication/oauth/authorize?client_id=lm_website&redirect_uri=https%3A%2F%2Fwww.lifemiles.com%2Foauth-signin&response_type=token&state=%7B%27Access-Level%27%3A%20%270%27%2C%20%27Redirect-Uri%27%3A%20%27%27%7D&scope=read");
            $this->waitForElement(WebDriverBy::xpath($resXpath), 10);
            $this->saveResponse();
        }

        if ($loginWithUsername = $this->waitForElement(WebDriverBy::xpath("//a[@id = 'social-Lifemiles']"), 0)) {
            try {
                $loginWithUsername->click();
            } catch (TimeOutException | \Facebook\WebDriver\Exception\TimeoutException $e) {
                $this->logger->error('caught ' . get_class($e) . ' on line ' . $e->getLine());
                $this->saveResponse();

                if ($loginWithUsername = $this->waitForElement(WebDriverBy::xpath("//a[@id = 'social-Lifemiles']"), 0)) {
                    $loginWithUsername->click();
                }
            }
            $this->waitForElement(WebDriverBy::xpath($resXpath), 10);

            $this->saveResponse();
        }

        $this->driver->executeScript('let btn = document.querySelector("button[class*=CookiesBrowserAlert_acceptButton]"); if (btn) btn.click();');

        // waiting for full form loading
        $this->waitForElement(WebDriverBy::xpath('//button[contains(@class, "Button_button__") and span[contains(text(), "Log in") or contains(text(), "Ingresar")]]'), 10);

        $login = $this->waitForElement(WebDriverBy::xpath("//input[@id = 'username']"), 0);

        // may be too long loading
        if (!$login && $this->waitForElement(WebDriverBy::xpath('//img[@alt="loading..."]'), 0)) {
            $login = $this->waitForElement(WebDriverBy::xpath("//input[@id = 'username']"), 20);
            $this->saveResponse();
        }

        $pass = $this->waitForElement(WebDriverBy::xpath("//input[@id = 'password']"), 0);
        /*
        $rememberMe = $selenium->waitForElement(WebDriverBy::xpath("//label[@for = 'Keep-me-login-confirm']"), 0);
        */
        $this->saveResponse();

        if (!$login || !$pass) {
            $this->logger->error("something went wrong");
            $this->logger->debug("[Current URL]: {$this->http->currentUrl()}");

            if (!$this->waitForElement(WebDriverBy::xpath("//p[contains(text(), 'our activity and behavior on this site made us think that you are a bot')] | //p[contains(text(), 'activity and behavior on our site made us think you could be a robot')]"), 0)) {
                $this->http->GetURL("https://www.lifemiles.com");
            }
            $this->saveResponse();
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
                || $this->http->currentUrl() == 'https://www.lifemiles.com/integrator/v1/authentication/oauth/authorize?client_id=lm_website&redirect_uri=https%3A%2F%2Fwww.lifemiles.com%2Foauth-signin&response_type=token&state=%7B%27Access-Level%27%3A%20%270%27%2C%20%27Redirect-Uri%27%3A%20%27%27%7D&scope=read'
                || $this->http->currentUrl() == 'https://www.lifemiles.com/sign-in'
            ) {
                $this->callRetries();

                if ($this->http->FindSingleNode('//a[contains(text(), "Iniciar sesión")]')) {
                    throw new CheckRetryNeededException(3);
                }
            }

            return false;
        }

        try {
            $this->logger->debug("click by login");
            $login->click();
            // selenium trace workaround
        } catch (Facebook\WebDriver\Exception\StaleElementReferenceException | StaleElementReferenceException $e) {
            $this->logger->error("Exception: " . $e->getMessage());
            $this->saveResponse();

            sleep(5);
            $login = $this->waitForElement(WebDriverBy::xpath("//input[@id = 'username']"), 0);
            $pass = $this->waitForElement(WebDriverBy::xpath("//input[@id = 'password']"), 0);
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
        $btn = $this->waitForElement(WebDriverBy::xpath('//button[contains(@class, "Button_button__") and not(@disabled) and span[contains(text(), "Log in") or contains(text(), "Ingresar")]] | //button[not(@disabled) and @id = "Login-confirm"]'), 5);
        $this->saveResponse();

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
        $loginSuccess = $this->waitForElement(WebDriverBy::xpath($loginSuccessXpath), 15);

        if ($this->cancel2faSetup($loginSuccess)) {
            $loginSuccess = $this->waitForElement(WebDriverBy::xpath($loginSuccessXpath), 15);
        }

        try {
            $conditions = !$loginSuccess && $this->waitForElement(WebDriverBy::xpath('//img[@alt="loading..."]'), 0);
        } catch (
            Facebook\WebDriver\Exception\StaleElementReferenceException
            | StaleElementReferenceException
            $e
        ) {
            $this->logger->error("Exception: " . $e->getMessage());
            $this->saveResponse();
            $conditions = $this->waitForElement(WebDriverBy::xpath('//img[@alt="loading..."]'), 0);
        }

        // may be too long loading
        if ($conditions) {
            $loginSuccess = $this->waitForElement(WebDriverBy::xpath($loginSuccessXpath), 20);
        }
        $this->saveResponse();

        if (!$loginSuccess) {
            $this->http->GetURL("https://www.lifemiles.com/");
            $this->saveResponse();

            if ($message = $this->http->FindSingleNode("//p[contains(text(), 'Currently this service is not available due to maintenance work.')]")) {
                throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
            }
        }// if (!$loginSuccess && strstr($selenium->http->currentUrl(), 'https://oauth.lifemiles.com/terms?consent_challenge='))

        $this->saveResponse();

        if ($this->http->FindSingleNode("
                //p[contains(text(), 'our activity and behavior on this site made us think that you are a bot')]
                | //p[contains(text(), 'activity and behavior on our site made us think you could be a robot')]
                | //*[self::h1 or self::span][contains(text(), 'This site can’t be reached')]
            ")
        ) {
            $this->callRetries();
        }

        return true;
    }

    public function Login()
    {
        if ($this->processSecurityCheckpoint()) {
            return false;
        }

        $aviancataca = $this->getAviancataca();

        if ($aviancataca->membershipNumber && $aviancataca->loginSuccessfulSelenium($this->State['access_token'], 30)) {
            return true;
        }

        if ($this->checkCredentials()) {
            return false;
        }

        // AccountID: 7061968
        if (
            $aviancataca->http->Response['body'] == 'nil'
            && $aviancataca->http->Response['code'] == 403
            && $this->AccountFields['Login'] == '00434234216'
        ) {
            throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
        }

        return $this->checkErrors();
    }

    public function ProcessStep($step)
    {
        $this->logger->notice(__METHOD__);
        $this->saveResponse();

        if ($this->isNewSession()) {
            return $this->LoadLoginForm() && $this->Login();
        }

        if ($step == 'Question') {
            $this->saveResponse();

            if ($this->processSecurityCheckpoint()) {
                return true;
            }
        }

        return false;
    }

    public function Parse()
    {
        $aviancataca = $this->getAviancataca();
        $aviancataca->Parse();
        $this->SetBalance($aviancataca->Balance);
        $this->Properties = $aviancataca->Properties;
        $this->ErrorCode = $aviancataca->ErrorCode;

        if ($this->ErrorCode != ACCOUNT_CHECKED) {
            $this->ErrorMessage = $aviancataca->ErrorMessage;
            $this->DebugInfo = $aviancataca->DebugInfo;
        }
    }

    protected function getAviancataca()
    {
        $this->logger->notice(__METHOD__);

        if (!isset($this->aviancataca)) {
            $this->aviancataca = new TAccountCheckerAviancataca();
            $this->aviancataca->http = new HttpBrowser("none", new CurlDriver());
            $this->aviancataca->http->setProxyParams($this->http->getProxyParams());

            $this->http->brotherBrowser($this->aviancataca->http);
            $this->aviancataca->AccountFields = $this->AccountFields;
            $this->aviancataca->itinerariesMaster = $this->itinerariesMaster;
            $this->aviancataca->HistoryStartDate = $this->HistoryStartDate;
            $this->aviancataca->historyStartDates = $this->historyStartDates;
            $this->aviancataca->http->LogHeaders = $this->http->LogHeaders;
            $this->aviancataca->ParseIts = $this->ParseIts;
            $this->aviancataca->ParsePastIts = $this->ParsePastIts;
            $this->aviancataca->WantHistory = $this->WantHistory;
            $this->aviancataca->WantFiles = $this->WantFiles;
            $this->aviancataca->strictHistoryStartDate = $this->strictHistoryStartDate;
//            $this->logger->debug(var_export($this->http->getDefaultHeaders(), true), ['pre' => true]);
            $this->logger->debug("set headers");
            $defaultHeaders = $this->http->getDefaultHeaders();

            foreach ($defaultHeaders as $header => $value) {
                $this->aviancataca->http->setDefaultHeader($header, $value);
            }

            $this->aviancataca->globalLogger = $this->globalLogger;
            $this->aviancataca->logger = $this->logger;
            $this->aviancataca->onTimeLimitIncreased = $this->onTimeLimitIncreased;
        }

        $cookies = $this->driver->manage()->getCookies();
        $this->logger->debug("set cookies");

        foreach ($cookies as $cookie) {
            $this->aviancataca->http->setCookie($cookie['name'], $cookie['value'], $cookie['domain'], $cookie['path'], $cookie['expiry'] ?? null);
        }

        if (!isset($this->aviancataca->membershipNumber)) {
            $authorization =
                $this->aviancataca->http->getCookieByName("dra3j", "www.lifemiles.com")
                ?? $this->http->FindPreg("/access_token=([^&]+)/", false, $this->http->currentUrl())
            ;
            $this->logger->debug("[authorization]: '{$authorization}'");
            $authorizationParts = explode('.', $authorization);

            foreach ($authorizationParts as $str) {
                $str = base64_decode($str);
                $this->logger->debug($str);

                if ($this->aviancataca->membershipNumber = $this->http->FindPreg("/\"lm-id\":\"([^\"]+)/", false, $str)) {
                    break;
                }
            }// foreach ($authorizationParts as $str)

            if (isset($authorization, $this->aviancataca->membershipNumber)) {
                $this->State['access_token'] = $authorization;
                $this->State['membershipNumber'] = $this->aviancataca->membershipNumber;
            }
        }

        return $this->aviancataca;
    }

    private function checkCredentials()
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

    private function processSecurityCheckpoint(): bool
    {
        $this->logger->notice(__METHOD__);
        $questionElem = $this->waitForElement(WebDriverBy::xpath("//label[contains(@class, 'authentication-ui-ConfirmIdentity_label') and not(b)]"), 0);
        $codeInput = $this->waitForElement(WebDriverBy::xpath("//input[contains(@class, 'authentication-ui-Code_') and @id = '0']"), 0);
        $this->saveResponse();

        if (!$questionElem || !$codeInput) {
            $this->logger->error("question not found");

            return false;
        }

        $question = $questionElem->getText();

        if (!isset($this->Answers[$question])) {
            $this->holdSession();
            $this->AskQuestion($question, null, "Question");

            return true;
        }

        $answer = $this->Answers[$question];
        unset($this->Answers[$question]);
        $this->logger->debug("Question to -> {$question}");

        for ($i = 0; $i < strlen($answer); $i++) {
            $codeInput = $this->waitForElement(WebDriverBy::xpath("//input[contains(@class, 'authentication-ui-Code_') and @id = '{$i}']"), 0);

            if (!$codeInput) {
                $this->logger->error("input not found");

                break;
            }

            $codeInput->clear();
            $codeInput->sendKeys($answer[$i]);
        }
        $this->saveResponse();

        $button = $this->waitForElement(WebDriverBy::xpath('//button[contains(@class, "authentication-ui-ConfirmIdentity_confirmIdentityButton") and not(@disabled)]'), 2);

        if (!$button) {
            $this->logger->error("Button not found");
            $this->saveResponse();

            return false;
        }

        $button->click();

        sleep(5);
        $message = $this->waitForElement(WebDriverBy::xpath("//h1[contains(text(), 'El código ingresado es incorrecto.')]"), 10);

        if ($message) {
            $this->holdSession();
            $this->AskQuestion($question, $message->getText(), "Question");

            return false;
        }

        $this->saveResponse();

        $aviancataca = $this->getAviancataca();

        if ($aviancataca->membershipNumber && $aviancataca->loginSuccessfulSelenium($this->State['access_token'], 30)) {
            return true;
        }

        return true;
    }

    private function callRetries()
    {
        $this->logger->notice(__METHOD__);

        if (ConfigValue(CONFIG_SITE_STATE) != SITE_STATE_DEBUG) {
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

    private function cancel2faSetup($button)
    {
        $this->logger->notice(__METHOD__);

        if (!$button || !str_contains($button->getAttribute('class'), 'authentication-ui-InitialPage_buttonDontShow')) {
            return false;
        }

        $button->click();
        $label = $this->waitForElement(WebDriverBy::xpath('//label[contains(@class, "authentication-ui-MfaTerms_labelCheckbox")]'), 1);
        $this->saveResponse();

        if (!$label) {
            $this->logger->error('label for 2fa terms not found');

            return false;
        }

        $label->click();
        $button = $this->waitForElement(WebDriverBy::xpath('//button[@class="authentication-ui-Button_button authentication-ui-Button_buttonBlue"]'), 0);

        if (!$button) {
            $this->logger->error('button for 2fa cancel not found');

            return false;
        }
        $button->click();
        $button = $this->waitForElement(WebDriverBy::xpath('//button[@class="authentication-ui-Button_button authentication-ui-Button_buttonBlue authentication-ui-VerificationMfaModal_buttonModal"]'), 5);
        $this->saveResponse();

        if (!$button) {
            $this->logger->error('button in modal for 2fa cancel not found');

            return false;
        }
        $button->click();

        return true;
    }
}
