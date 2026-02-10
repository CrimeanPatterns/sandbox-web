<?php

use AwardWallet\Common\Selenium\Puppeteer\Executor;
use AwardWallet\Common\Selenium\SeleniumDriverFactory;
use AwardWallet\Engine\Settings;


/**
 * Class SeleniumCheckerHelper
 * @property HttpBrowser $http
 * @property \Symfony\Component\DependencyInjection\ServiceLocator $services
 */
trait SeleniumCheckerHelper
{

    use \AwardWallet\Common\OneTimeCode\OtcHelper;

	/**
	 * @var RemoteWebDriver
	 */
	protected $driver;

    protected $googleIMG = "//img[contains(@class, 'rc-image-tile')]";

    protected $noMatchingImages = "No_matching_images";

    // images change when you click on them
    protected $newCaptchaType = false;

    /**
     * @var SeleniumFinderRequest
     */
    private $seleniumRequest;
    /**
     * @var SeleniumOptions
     */
    private $seleniumOptions;
    /**
     * @var bool
     */
    private $useCache = false;
    private $usePacFile = true;
    private $keepProfile = false;
    private $filterAds = true;
    private $directImages = true;

    /** @var AccountCheckerLogger */
    public $logger;

    protected function construct_SeleniumCheckerHelper()
    {
        $this->seleniumRequest = new SeleniumFinderRequest();
        $this->seleniumOptions = new SeleniumOptions();
        $this->logger = new AccountCheckerLogger($this);
    }

    protected function UseSelenium() {
        $logger = new \Monolog\Logger('main');
        $logger->pushHandler(new \Monolog\Handler\PsrHandler($this->logger));
        if ($this->globalLogger !== null) {
            $logger->pushHandler(new \Monolog\Handler\PsrHandler($this->globalLogger));
        }
        $this->KeepState = true;
        $this->useLastHostAsProxy = false;
        $this->seleniumOptions->startupText = ( $this->AccountFields["ProviderCode"] ?? "" ) . " | " . ($this->AccountFields["Login"] ?? "") . " | " . ($this->AccountFields["Partner"] ?? "") . " | " . ($this->AccountFields["RequestAccountID"] ?? ($this->AccountFields["AccountID"]?? "")) . " | " . date("Y-m-d H:i:s");
        $this->seleniumOptions->loggingContext = [
            "provider" => $this->AccountFields["ProviderCode"] ?? "",
            "accountId" => $this->AccountFields["RequestAccountID"] ?? $this->AccountFields["AccountID"] ?? "",
            "partner" => $this->AccountFields["Partner"] ?? "",
            "requestId" => $this->AccountFields["RequestID"] ?? "",
        ];
        $driver = $this->services->get(SeleniumDriverFactory::class)->getDriver(
            $this->seleniumRequest,
            $this->seleniumOptions,
            $logger
        );
        if (isset($this->AccountFields["Priority"], $this->AccountFields["ThrottleBelowPriority"])
            && $this->AccountFields["Priority"] < $this->AccountFields["ThrottleBelowPriority"]
        ) {
            $this->seleniumRequest->setIsBackround();
        }
        $driver->setKeepProfile($this->keepProfile);

        $driver->onStart = function(SeleniumOptions $seleniumOptions)
        {
            if ($this->usePacFile) {
                $params = [];
                if ($this->useCache) {
                    $params["cache"] = "cache.awardwallet.com:3128";
                    if (defined('CACHE_HOST')) {
                        $params["cache"] = CACHE_HOST . ":3128";
                    }
                }
                if ($this->http->GetProxy() !== null) {
                    $params["proxy"] = $this->http->GetProxy();
                }
                if ($this->filterAds) {
                    $params["filterAds"] = "1";
                }
                if ($this->directImages) {
                    $params["directImages"] = "1";
                }

                $seleniumOptions->pacFile = Settings::getPacFile();
                if(!empty($params))
                    $seleniumOptions->pacFile .= "?" . http_build_query($params);
                $this->http->Log('set selenium pac File: ' . $seleniumOptions->pacFile);
            }
            else{
                $seleniumOptions->pacFile = null;
            }
            if ($this->http->GetProxy() !== null) {
                $params = $this->http->getProxyParams();
                $seleniumOptions->proxyHost = $params['proxyHost'];
                $seleniumOptions->proxyPort = $params['proxyPort'];
                $seleniumOptions->proxyUser = $params['proxyLogin'];
                $seleniumOptions->proxyPassword = $params['proxyPassword'];
                $this->http->Log("set selenium proxy: {$seleniumOptions->proxyUser}@{$seleniumOptions->proxyHost}:{$seleniumOptions->proxyPort}");
            }
            else{
                $seleniumOptions->proxyHost = null;
                $seleniumOptions->proxyPort = null;
                $seleniumOptions->proxyUser = null;
                $seleniumOptions->proxyPassword = null;
            }
        };

        if ($this->http !== null) {
            $oldOnLog = $this->http->OnLog;
            $oldProxyParams = $this->http->getProxyParams();
            $oldResponseNumber = $this->http->ResponseNumber;
            $oldLogBrother = $this->http->LogBrother;
            $oldUserAgent = $this->http->userAgent;
        }
        $this->http = new HttpBrowser($this->LogMode, $driver, $this->httpLogDir);
        if (isset($oldUserAgent)) {
            $this->http->setUserAgent($oldUserAgent);
        }
        if (isset($oldProxyParams)) {
            $this->http->setProxyParams($oldProxyParams);
        }
        $this->initBrowserSettings();
        if (!empty($oldOnLog)) {
            $this->http->OnLog = $oldOnLog;
        }
        if (!empty($oldResponseNumber)) {
            $this->http->ResponseNumber = $oldResponseNumber;
        }
        if (!empty($oldLogBrother)) {
            $this->http->LogBrother = $oldLogBrother;
        }
   	}

   	/**
	 * @return RemoteWebDriver
	 */
	protected function getWebDriver()
	{
		return $this->http->driver->webDriver;
	}

	/**
	 * @param Callable $whileCallback
	 * @param int $timeoutSeconds
	 * @return bool
	 */
	public function waitFor($whileCallback, $timeoutSeconds = 60) {
		$start = time();
		do {
			try {
				if (call_user_func($whileCallback)){
					return true;
				}
			} catch (Exception $e) {
                $this->reconnectFirefox($e);
            }
			sleep(1);
		} while((time() - $start) < $timeoutSeconds);
		return false;
	}

	private function reconnectFirefox(\Throwable $e) : bool
    {
        if (stripos($e->getMessage(), "can't access dead object") !== false) {
            // https://stackoverflow.com/questions/44005034/cant-access-dead-object-in-geckodriver
            $this->logger->debug("firefox bug, reconnecting");
            $this->driver->switchTo()->defaultContent();
            return true;
        }

        return false;
    }

	/**
	 * @param WebDriverBy $by
	 * @param int $timeout
	 * @param bool $visible
	 * @return RemoteWebElement|null
	 */
	protected function waitForElement(WebDriverBy $by, $timeout = 60, $visible = true){
		/** @var RemoteWebElement $element */
		$element = null;
        $start = time();
		$this->waitFor(
            function () use ($by, &$element, $visible) {
                try {
				    $elements = $this->driver->findElements($by);
                } catch (Facebook\WebDriver\Exception\StaleElementReferenceException | StaleElementReferenceException $e) {
                    //$this->logger->error("[waitForElement exception on findElements]: " . $e->getMessage(), ['HtmlEncode' => true]);
                    sleep(1);
                    $elements = $this->driver->findElements($by);
                }

                foreach ($elements as $element) {
                    try {
                        if ($visible && !$element->isDisplayed())
                            $element = null;
                    } catch (Facebook\WebDriver\Exception\StaleElementReferenceException | StaleElementReferenceException $e) {
                        //$this->logger->error("[waitForElement StaleElementReferenceException on isDisplayed]: " . $e->getMessage(), ['HtmlEncode' => true]);
                        // isDisplayed throws this if element already disappeared from page
                        $element = null;
                    }

                    return !empty($element);
                }
				return false;
			},
			$timeout
		);
        $timeSpent = time() - $start;
        if (!empty($element))
            try {
                $this->http->Log("found element {$by->getValue()}, displayed: {$element->isDisplayed()}, text: '".trim($element->getText())."', spent time: $timeSpent", LOG_LEVEL_NOTICE);
            } catch (Facebook\WebDriver\Exception\StaleElementReferenceException | StaleElementReferenceException $e) {
                // final fallback for element disappearance, getText throws this too
			    $this->http->Log("element {$by->getValue()} found and disappeared, spent time: $timeSpent");
                $element = null;
                $timeLeft = $timeout - $timeSpent;

                if ($timeLeft > 0) {
                    $this->http->Log("restarting search, time left: $timeLeft");
                    return $this->waitForElement($by, $timeLeft, $visible);
                }
            }
		else
			$this->http->Log("element {$by->getValue()} not found, spent time: $timeSpent");

		return $element;
	}

	/**
     * Return true if we're trying to use window second time else false
     *
     * @return bool
	 */
	protected function isNewSession(){
		return $this->http->driver->isNewSession();
	}

	/**
	 * @param bool $keep
	 */
	protected function keepSession($keep){
		$this->http->driver->keepSession = $keep;
	}

	/*
	 * Smart keepSession
	 */
    protected function holdSession() {
        $this->logger->notice(__METHOD__);
        if (!$this->isBackgroundCheck() || method_exists($this, 'getWaitForOtc') && $this->getWaitForOtc())
            $this->keepSession(true);
    }

	/**
	 * @return \AwardWallet\Common\Selenium\DownloadedFile|null - last downloaded filename
	 */
	protected function getLastDownloadedFile($timeout = 20, $completeTimeout = 3)
    {
        $file = null;
        $this->waitFor(function() use (&$file) {
            $file = $this->http->driver->getLastDownloadedFile();
            return $file !== null;
        }, $timeout);
        return $file;
	}

	protected function clearDownloads(){
		$this->http->driver->clearDownloads();
	}

	protected function InitSeleniumBrowser($proxy = null){
		$this->AccountFields['ProviderEngine'] = PROVIDER_ENGINE_SELENIUM;
		$this->UseSelenium();
	}

	protected function startNewSession(){
		$this->http->driver->stop();
		$this->http->driver->setState([]);
		$this->http->driver->start();
	}

    public function saveResponse(): ?string
    {
        if ($this->driver !== null) {
            try {
                $this->http->SetBody($this->driver->executeScript('return document.documentElement.innerHTML;'));
                $this->http->SaveResponse();
            } catch (Exception $e) {
                if ($this->reconnectFirefox($e)) {
                    $this->http->SetBody($this->driver->executeScript('return document.documentElement.innerHTML;'));
                    $this->http->SaveResponse();
                }
                else {
                    $this->logger->warning("failed to save response: " . $e->getMessage());
                    return $e->getMessage();
                }
            }
        }
        return null;
    }

	/**
	 * @param bool $keep
	 */
	protected function keepCookies($keep){
		$this->http->driver->keepCookies = $keep;
	}

    protected function useChromium($version = null)
    {
        if(empty($version))
            $version = SeleniumFinderRequest::CHROMIUM_DEFAULT;
        $this->logger->debug("Selenium browser: Chromium v. {$version}");
        $this->seleniumRequest->request(SeleniumFinderRequest::BROWSER_CHROMIUM, $version);
	}

    protected function useGoogleChrome($version = null)
    {
        if(empty($version))
            $version = SeleniumFinderRequest::CHROME_DEFAULT;
        $this->logger->debug("Selenium browser: Google Chrome v. {$version}");
        $this->seleniumRequest->request(SeleniumFinderRequest::BROWSER_CHROME, $version);
	}

    protected function useChromePuppeteer($version = null)
    {
        if(empty($version))
            $version = SeleniumFinderRequest::CHROME_PUPPETEER_DEFAULT;
        $this->logger->debug("Selenium browser: Chrome Puppeteer v. {$version}");
        $this->seleniumRequest->request(SeleniumFinderRequest::BROWSER_CHROME_PUPPETEER, $version);
	}

    protected function useChromeExtension($version = null)
    {
        if(empty($version))
            $version = SeleniumFinderRequest::CHROME_EXTENSION_DEFAULT;
        $this->logger->debug("Selenium browser: Chrome Extension v. {$version}");
        $this->seleniumRequest->request(SeleniumFinderRequest::BROWSER_CHROME_EXTENSION, $version);
	}

    protected function useFirefoxPlaywright($version = null)
    {
        if(empty($version))
            $version = SeleniumFinderRequest::FIREFOX_PLAYWRIGHT_DEFAULT;
        $this->logger->debug("Selenium browser: Firefox Playwright v. {$version}");
        $this->seleniumRequest->request(SeleniumFinderRequest::BROWSER_FIREFOX_PLAYWRIGHT, $version);
	}

    protected function useChromePlaywright($version = null)
    {
        if(empty($version))
            $version = SeleniumFinderRequest::CHROME_PLAYWRIGHT_DEFAULT;
        $this->logger->debug("Selenium browser: Chrome Playwright v. {$version}");
        $this->seleniumRequest->request(SeleniumFinderRequest::BROWSER_CHROME_PLAYWRIGHT, $version);
	}

    protected function useBravePlaywright($version = null)
    {
        if(empty($version))
            $version = SeleniumFinderRequest::BRAVE_PLAYWRIGHT_DEFAULT;
        $this->logger->debug("Selenium browser: Brave Playwright v. {$version}");
        $this->seleniumRequest->request(SeleniumFinderRequest::BROWSER_BRAVE_PLAYWRIGHT, $version);
	}

    protected function useFirefox($version = null)
    {
        if(empty($version))
            $version = SeleniumFinderRequest::FIREFOX_DEFAULT;
        $this->logger->debug("Selenium browser: Firefox v. {$version}");
        $this->seleniumRequest->request(SeleniumFinderRequest::BROWSER_FIREFOX, $version);
	}

    /**
     * This method disables the hovering scripts
     *
     * @param bool $use
     */
    protected function usePacFile(bool $use = true) {
        $this->logger->debug("usePacFile: ". json_encode($use));
        $this->usePacFile = $use;
	}

    /**
     * emulated screen resolution, expected format [800, 600]
     * @var array
     */
    protected function setScreenResolution($resolution)
    {
        $this->logger->debug("set screen resolution: ".implode('x', $resolution));
        $this->seleniumOptions->resolution = $resolution;
    }

	protected function useCache(){
        $this->logger->debug('using cache');
        $this->useCache = true;
	}

	protected function waitAjax(){
		sleep(1);
		$this->waitFor(function(){ return $this->driver->executeScript('return jQuery.active') == 0; });
	}

	public function Start(){
		if($this->http->driver instanceof SeleniumDriver) {

		    if(!$this->http->driver->isStarted())
		        $this->http->start();
            $this->driver = $this->http->driver->webDriver;
            if ($this instanceof TAccountChecker) {
                /** @var SeleniumDriver $seleniumDriver */
                $seleniumDriver = $this->http->driver;
                
                $this->http->setSeleniumServer($seleniumDriver->getServerAddress());
                
                $browserInfo = $seleniumDriver->getBrowserInfo();
                $this->http->setSeleniumBrowserFamily($browserInfo[\SeleniumStarter::CONTEXT_BROWSER_FAMILY]);
                $this->http->setSeleniumBrowserVersion($browserInfo[\SeleniumStarter::CONTEXT_BROWSER_VERSION]);
            }
        }
	}

	protected function disableImages(){
        $this->logger->debug('images have been disabled');
        $this->seleniumOptions->showImages = false;
	}

	/**
	 * Take screenshot of selected element and return path to it on success or false otherwise
	 *
	 * @param RemoteWebElement | Facebook\WebDriver\Remote\RemoteWebElement $elem Element which should be screenshoted
	 * @return string|false
	 */
    protected function takeScreenshotOfElement($elem, $selenium = null)
    {
        $this->logger->notice(__METHOD__);
        if (!$elem)
            return false;
        if (!$selenium)
            $selenium = $this;
        $time = getmypid()."-".microtime(true);
        $path = '/tmp/seleniumPageScreenshot-'.$time.'.png';
        $selenium->driver->takeScreenshot($path);
        $img = imagecreatefrompng($path);
        unlink($path);
        if (!$img)
            return false;
        $rect = [
            'x' => $elem->getLocation()->getX(),
            'y' => $elem->getLocation()->getY(),
            'width' => $elem->getSize()->getWidth(),
            'height' => $elem->getSize()->getHeight(),
        ];
        $cropped = imagecrop($img, $rect);
        if (!$cropped)
            return false;
        $path = '/tmp/seleniumElemScreenshot-'.$time.'.png';
        $status = imagejpeg($cropped, $path);
        if (!$status)
            return false;
        $this->logger->info('screenshot taken');
        return $path;
    }

    /* *
     * Pass "Choose Image" Captcha (this method should replace passChooseImageReCaptcha)
     * @link https://rucaptcha.com/api-recaptcha
     *
     * @param RemoteWebElement $elem iFrame with captcha
     * @param int $attemptsCount attempts to solve captcha
     * @param int $recognizeTimeout - max time recognition
     * @return true|false
     *
     * @deprecated - Don't use this method! It's too expensive because too many images needed solve
     * /
    /*
    protected function clickCaptcha ($elem, $attemptsCount = 10, $recognizeTimeout = 180) {
        $this->http->Log(__METHOD__);
        $startTimer = microtime(true);
        if (!$elem) {
            $this->http->Log('iFrame for captcha is not defined', LOG_LEVEL_ERROR);
            return false;
        }
        $recognizer = $this->getCaptchaRecognizer(self::CAPTCHA_RECOGNIZER_RUCAPTCHA);
        $recognizer->RecognizeTimeout = $recognizeTimeout;

        $passed = false;

        for ($attempt = 0; $attempt < $attemptsCount; $attempt++) {
            $this->http->Log("Attempt # {$attempt}", LOG_LEVEL_ERROR);
            $this->driver->switchTo()->frame($elem);
            $this->saveResponse();
            $image = $this->waitForElement(WebDriverBy::xpath($this->googleIMG));
            $this->saveResponse();
            $this->driver->switchTo()->defaultContent();
            if (!$image) {
                $this->http->Log('Captcha images loading failed', LOG_LEVEL_ERROR);
                break;
            }
            // captcha screenshot
            $this->http->Log("take screenshot");
            $pathToScreenshot = $this->takeScreenshotOfElement($elem);
            if (!$pathToScreenshot) {
                $this->http->Log('Failed to get screenshot of iFrame with captcha', LOG_LEVEL_ERROR);
                break;
            }
//			$this->http->Log('Path to captcha screenshot '.$pathToScreenshot);
            $result = false;
            try {
                $this->driver->switchTo()->frame($elem);
                $this->http->Log("Doing captcha recognition...", LOG_LEVEL_ERROR);
                // Get images' coordinates
                try {
                    $parameters = ['coordinatescaptcha' => '1'];
                    // expanded parameters
                    if (!empty($previousID))
                        $parameters = array_merge($parameters, ["can_no_answer" => "1"]);
//                        $parameters = array_merge($parameters, ["previousID" => $previousID]);
                    unset($previousID);

//                    $this->http->Log("Set parameters: " . var_export($parameters, true));
                    $captcha = $recognizer->recognizeFile($pathToScreenshot, $parameters);
                }
                catch (CaptchaException $e) {
                    if (preg_match('#timelimit.*?hit#i', $e->getMessage()))
                        $this->http->Log('Timelimit reached');
                    throw $e;
                }// catch (CaptchaException $e)
                $this->http->Log('-----------------------------------------------------------------------------');
                $this->http->Log('[Response]: '.$captcha);
                // Report an incorrectly solved CAPTCHA.
                if (trim($captcha) == 'coordinate:') {
                    $recognizer->reportIncorrectlySolvedCAPTCHA();
                    continue;
                }
                $captcha = str_replace('coordinates:', '', $captcha);
                $this->http->Log('[Response]: '.$captcha);
                $this->http->Log('-----------------------------------------------------------------------------');
                $coordinates = explode(';', $captcha);
                if ($captcha != $this->noMatchingImages) {
                    if (!$coordinates) {
                        $this->http->Log('Could not split captcha response to image coordinates');
                        break;
                    }
                    // Click on coordinates
                    foreach ($coordinates as $coordinate) {
                        $point = explode(',', $coordinate);
                        if (count($point) != 2) {
                            $this->http->Log("Bad coordinates: {$coordinate}", LOG_LEVEL_ERROR);
                            continue;
                        }// if (count($point) != 2)
                        $x = explode('=', $point[0]);
                        $y = explode('=', $point[1]);

                        if (!isset($x[1]) || !isset($y[1])) {
                            $this->http->Log("Bad coordinates: x = {$point[0]}, y = {$point[1]}", LOG_LEVEL_ERROR);
                            continue;
                        }// if (!isset($x[1]) || !isset($y[1]))

                        $this->http->Log("Click on: x = {$x[1]}, y = {$y[1]}", LOG_LEVEL_ERROR);

                        $html = $this->driver->findElement(WebDriverBy::xpath('html'));
                        $coords = $html->getCoordinates();
                        $mouse = $this->driver->getMouse();
                        $mouse->mouseMove($coords, intval($x[1]), intval($y[1]));
                        $mouse->click();

                        usleep(rand(400000, 1300000));
                    }// foreach ($selectedIndices as $i)
                }// if ($captcha != $this->noMatchingImages)

                // get captcha ID
                $previousID = $recognizer->getCaptchaID();

                sleep(1);
                // Unselect images, if not selected all the correct images (standard captcha)
                $imageSelected = $this->driver->findElements(WebDriverBy::xpath("//*[@class = 'rc-imageselect-tileselected']"));
                // there is no needed images
                if ($captcha == $this->noMatchingImages || $imageSelected) {
                    $this->http->Log("Click 'Verify' button");
                    $this->driver->findElement(WebDriverBy::id('recaptcha-verify-button'))->click();

                    // Handle errors
                    $errorsXpath = '//*[(@class="rc-imageselect-incorrect-response" or @class="rc-imageselect-error-select-one" or @class="rc-imageselect-error-select-more" or @class = "rc-imageselect-error-dynamic-more") and not(contains(@style, "display:none"))]';
                    if ($this->waitForElement(WebDriverBy::xpath($errorsXpath), 3)) {
                        $errors = [];
                        foreach ($this->driver->findElements(WebDriverBy::xpath($errorsXpath)) as $e)
                            $errors[] = $e->getText();
                        $error = implode($errors);
                        $this->http->Log("[Returned error]: " . $error, LOG_LEVEL_ERROR);
                        $this->saveResponse();


                        if ($imageSelected
                            && (strstr($error, 'Please select all matching images') || strstr($error, 'Выбраны не все подходящие изображения'))) {
                            $this->http->Log("[Captcha type]: standard captcha, unselect images and send report an incorrectly solved CAPTCHA");
                            foreach ($coordinates as $coordinate) {
                                $point = explode(',', $coordinate);
                                if (count($point) != 2) {
                                    $this->http->Log("Bad coordinates: 'coordinates'", LOG_LEVEL_ERROR);
                                    continue;
                                }
                                $x = explode('=', $point[0]);
                                $y = explode('=', $point[1]);
                                $this->http->Log("Click on: x = {$x[1]}, y = {$y[1]}", LOG_LEVEL_ERROR);

                                $html = $this->driver->findElement(WebDriverBy::xpath('html'));
                                $coords = $html->getCoordinates();
                                $mouse = $this->driver->getMouse();
                                $mouse->mouseMove($coords, intval($x[1]), intval($y[1]));
                                $mouse->click();

                                usleep(rand(400000, 1300000));
                            }// foreach ($selectedIndices as $i)
                        }// if ($images = $this->driver->findElements(WebDriverBy::xpath($this->googleIMG)))
                        elseif (!$imageSelected
                                && (strstr($error, 'Please select all matching images')
                                    || strstr($error, 'Выбраны не все подходящие изображения'))) {
                                unset($previousID);
                                $this->http->Log("[Captcha type]: difficult captcha with changing images, do not send report");
                        }

                        // Report an incorrectly solved CAPTCHA.
                        if (strstr($error, 'Please solve more to be verified') || strstr($error, 'Пройдите проверку ещё раз')
                            // Do not send report for difficult captcha with changing images
                            || (!$imageSelected
                                && (strstr($error, 'Please select all matching images') || strstr($error, 'Выбраны не все подходящие изображения')))
                            // Thai
                            || strstr($error, 'ต้องการคำตอบที่ถูกต้องหลายรายการ')
                            // Malaysian
                            || strstr($error, 'Sila pilih semua imej yang sepadan.')
                            // Arabic
                            || strstr($error, ' يُرجى حل المزيد'))
                            $recognizer->reportIncorrectlySolvedCAPTCHA();
                        else
                            $this->http->Log("[Unknown error]: " . $error);
                    }// if ($this->waitForElement(WebDriverBy::xpath($errorsXpath), 3))
                    elseif ($captcha == $this->noMatchingImages)
                        $result = true;
                }// if ($captcha == $this->noMatchingImages)
                $this->saveResponse();
            }
            catch (\Exception $e) {
                $this->http->Log("[Captcha error]: ".$e->getMessage(), LOG_LEVEL_ERROR);
                break;
            }
            catch (\UnexpectedAlertOpenException $e) {
                $this->http->Log("[Captcha error]: ".$e->getMessage(), LOG_LEVEL_ERROR);
                break;
            }
            finally {
                $this->driver->switchTo()->defaultContent();
                unlink($pathToScreenshot);
            }

            // success
            if ($result) {
                $this->http->Log('[Result]: Captcha passing attempt succeeded');
                $passed = true;
                break;
            }// if ($result)

            // fail
            $this->http->Log('[Result]: Captcha passing attempt failed', LOG_LEVEL_ERROR);
            if ($attempt == $attemptsCount - 1) {
                $this->http->Log("Failed to pass captcha after $attemptsCount attempts", LOG_LEVEL_ERROR);
                break;
            }// if ($attempt == $attemptsCount - 1)
            else {
                $this->http->Log('Trying to pass captcha again (attempt '.($attempt + 1).' of '.$attemptsCount.')', LOG_LEVEL_ERROR);
                continue;
            }
        }// for ($attempt = 0; $attempt < $attemptsCount; $attempt++)

        if ($passed)
            $this->http->Log('Captcha passed');
        else
            $this->http->Log('Captcha passing failed', LOG_LEVEL_ERROR);

        $this->http->Log("[Time recognizing: " . (microtime(true) - $startTimer) . "]");

        return $passed;
    }
    */

    protected function parseCoordinates($text) {
        $this->logger->notice(__METHOD__);
        $x = [];
        $y = [];
        preg_match_all('/x=(\d+)/', $text, $m);
        $x = $m[1];
        preg_match_all('/y=(\d+)/', $text, $m);
        $y = $m[1];
        if (count($x) !== count($y)) {
            $this->logger->info('invalid coordinates in the text');
            return false;
        }
        $coords = [];
        for ($i = 0; $i < count($x); $i++) {
            $coords[] = ['x' => $x[$i], 'y' => $y[$i]];
        }
        $this->logger->info('parsed coords:');
        $this->logger->info(var_export($coords, true));

        return $coords;
    }

    protected function cloudFlareWorkaround($selenium = null)
    {
        $this->logger->notice(__METHOD__);

        $isSeleniumMainEngine = true;

        if (!$selenium) {
            $selenium = $this;
            $isSeleniumMainEngine = false;
        }

        $res = false;

        if ($verify = $selenium->waitForElement(WebDriverBy::xpath("//input[@value = 'Verify you are human']"), 0)) {
            $verify->click();
            $res = true;
        }

        if ($iframe = $selenium->waitForElement(WebDriverBy::xpath("//div[@id = 'turnstile-wrapper']//iframe"), 5)) {
            $selenium->driver->switchTo()->frame($iframe);
            $this->saveLogs($isSeleniumMainEngine);

            if ($captcha = $selenium->waitForElement(WebDriverBy::xpath("//label[@class = 'ctp-checkbox-label']/map/img | //label[@class = 'cb-lb' and input[@type = 'checkbox']]"), 10)) {
                $this->saveLogs($isSeleniumMainEngine);
                $captcha->click();
                // TODO: place for improvements, sleep should be deleted
                $this->logger->debug("delay -> 15 sec");
                $this->saveLogs($isSeleniumMainEngine);
                sleep(15);

                $selenium->driver->switchTo()->defaultContent();
                $this->saveLogs($isSeleniumMainEngine);
                $res = true;
            }
        }

        return $res;
    }

    protected function saveLogs($isSeleniumMainEngine = true) {
        if ($isSeleniumMainEngine) {
            $this->saveResponse();

            return;
        }

        $this->savePageToLogs($this);
    }

    protected function savePageToLogs($selenium)
    {
        $this->logger->notice(__METHOD__);

        try {
            $selenium->http->SaveResponse();
        } catch (
            ErrorException
            | NoSuchDriverException
            $e
        ) {
            $this->logger->error("Exception on SaveResponse: " . $e->getMessage(), ['HtmlEncode' => true]);
        }

        try {
            $this->http->SetBody($selenium->driver->executeScript('return document.documentElement.innerHTML'));
        } catch (
            UnexpectedJavascriptException
            | Facebook\WebDriver\Exception\JavascriptErrorException
            | TimeOutException
            $e
        ) {
            $this->logger->error("Exception: " . $e->getMessage(), ['HtmlEncode' => true]);
        } catch (UnknownServerException $e) {
            $this->logger->error("Exception on SetBody: " . $e->getMessage(), ['HtmlEncode' => true]);
            $this->http->SetBody($selenium->driver->getPageSource());
        }

        $this->http->SaveResponse();
    }

    protected function clickCaptchaCtrip($selenium = null, $attemptCount = 5, $recognizeTimeout = 180, $increaseTimeLimit = null)
    {
        $this->logger->notice(__METHOD__);
        if (!$selenium)
            $selenium = $this;

        $submit = null;
        for ($attempt = 0; $attempt < $attemptCount; $attempt++) {
            $this->logger->info(sprintf('solving attempt #%s', $attempt));
            $chooser = $selenium->waitForElement(WebDriverBy::xpath('//div[contains(@class, "cpt-choose-box")] | //div[contains(@class, "slider")]/div[contains(@class, "container")]'), 5);
            if ($chooser) {
                $pathToScreenshot = $this->takeScreenshotOfElement($chooser, $selenium);
            } else {
                $this->logger->info('chooser not found');
                return false;
            }

            $data = [
                'coordinatescaptcha' => '1',
                'textinstructions' => 'select the text from the top picture in correct order on the bottom picture / выберите текст из картинки вверху в правильном порядке на картинке внизу',
            ];

            $recognizer = $this->getCaptchaRecognizer(self::CAPTCHA_RECOGNIZER_RUCAPTCHA);
            $recognizer->RecognizeTimeout = $recognizeTimeout;
            try {
                $captcha = $recognizer->recognizeFile($pathToScreenshot, $data);
            } catch (CaptchaException $e) {
                $this->logger->warning("exception: " . $e->getMessage());
                // always solvable for ctrip
                if ($e->getMessage() == 'server returned error: ERROR_CAPTCHA_UNSOLVABLE') {
                    $recognizer->reportIncorrectlySolvedCAPTCHA();
                    continue;
                } else {
                    return false;
                }
            } finally {
                unlink($pathToScreenshot);
            }

            if ($increaseTimeLimit) {
                $this->increaseTimeLimit($increaseTimeLimit);
            }

            $letterCoords = $this->parseCoordinates($captcha);
            
            if (!$letterCoords) {
                continue;
            }

            if (count($letterCoords) == 1) {
                $recognizer->reportIncorrectlySolvedCAPTCHA();

                continue;
            }

            $html = $selenium->driver->findElement(WebDriverBy::xpath('//body'));
            $bodyCoords = $html->getCoordinates();

            $coords = $chooser->getCoordinates()->inViewPort();
            $chooserCoords = ['x' => $coords->getX(), 'y' => $coords->getY()];

            $mouse = $selenium->driver->getMouse();
            $mover = new MouseMover($selenium->driver);
            $mover->moveToCoordinates($chooserCoords);
            foreach ($letterCoords as $point) {
                $x = intval($chooserCoords['x'] + $point['x']);
                $y = intval($chooserCoords['y'] + $point['y']);
                $mover->moveToCoordinates(['x' => $x, 'y' => $y]);
                $mouse->mouseMove($bodyCoords, $x, $y);
                $mover->click();
            }

            $submit = $selenium->waitForElement(WebDriverBy::xpath('//a[contains(@class, "cpt-choose-submit")] | //span[contains(@class, "cpt-submit-text")]'), 3);
            $selenium->http->SaveResponse();
            if ($submit) {
                $submit->click();
                $this->logger->info('sleeping for a bit after submit click');
                sleep(5);
            } else {
                $this->logger->info(sprintf('could not click captcha submit'));
            }

            // If submit hasn't disappeared then captcha was not solved correctly
            $submit = $selenium->waitForElement(WebDriverBy::xpath('//a[contains(@class, "cpt-choose-submit")] | //span[contains(@class, "cpt-submit-text")]'), 3);
            $selenium->http->SaveResponse();
            if ($submit) {
                $recognizer->reportIncorrectlySolvedCAPTCHA();
            } else {
                $this->logger->info('successfully solved select captcha');
                break;
            }
        }

        if ($submit) {
            $this->logger->error('failed to solve select captcha');
            return false;
        }

        $infoBoard = $selenium->waitForElement(WebDriverBy::cssSelector('span.cpt-info-board'), 5);
        
        if ($infoBoard && $this->http->FindPreg('/Verification failed/i', $infoBoard->getText())) {
            throw new CheckRetryNeededException(3, 0);
        }

        return true;
    }

    protected function setKeepProfile(bool $keep)
    {
        $this->keepProfile = $keep;

        if ($this->seleniumRequest->getBrowser() !== SeleniumFinderRequest::BROWSER_FIREFOX) {
            $this->logger->error("{$this->seleniumRequest->getBrowser()} do not support KeepProfile method");
            return;
        }

        if ($this->http->driver !== null && $this->http->driver instanceof SeleniumDriver) {
            $this->http->driver->setKeepProfile($keep);
        }
    }

    protected function setFilterAds(bool $filterAds)
    {
        $this->filterAds = $filterAds;
        return $this;
    }

    protected function setDirectImages(bool $directImages)
    {
        $this->directImages = $directImages;
        return $this;
    }

    protected function getAllCookies() : array
    {
        if ($this->http->driver->browserCommunicator === null) {
            throw new \Exception("unsupported browser for getting cookies");
        }
        return $this->http->driver->browserCommunicator->getCookies();
    }

    // closes browser window, to save resources
    // use it if you already finished selenium parsing (grabbed cookies from it)
    protected function stopSeleniumBrowser() : void
    {
        $this->logger->info(__METHOD__);
        $this->http->driver->stop();
    }
    
    public function getPuppeteerExecutor() : \AwardWallet\Common\Selenium\Puppeteer\Executor
    {
        return new Executor($this->logger, 'ws://' . $this->http->driver->getServerAddress() . '/devtools/' . $this->getWebDriver()->getSessionID());
    }
}
