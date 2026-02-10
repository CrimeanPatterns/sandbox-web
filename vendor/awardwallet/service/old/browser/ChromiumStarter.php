<?php

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\WebDriverCapabilityType;

class ChromiumStarter
{

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    /**
     * @var string
     */
    private $startupUrl;

    public function __construct(\Psr\Log\LoggerInterface $logger, string $startupUrl)
    {
        $this->logger = $logger;
        $this->startupUrl = $startupUrl;
    }

    public function prepareSession(SeleniumOptions $seleniumOptions, string $downloadFolder, SeleniumFinderRequest $request) : SeleniumSessionRequest
    {
        $this->logger->debug("starting " . $request->getBrowserName());
        $capabilities = DesiredCapabilities::chrome();

        if ($request->getBrowser() === \SeleniumFinderRequest::BROWSER_CHROME_PUPPETEER) {
            // should match https://github.com/AwardWallet/webdriver-cluster/blob/8cdf5adc597e86b68b9069e8205527be061400b7/selenoid/browsers.json#L23-L23
            $capabilities->setCapability(WebDriverCapabilityType::BROWSER_NAME, 'puppeteer-chrome');
            $capabilities->setCapability(WebDriverCapabilityType::VERSION, $request->getVersion());
        }

        if (
            $request->getBrowser() === \SeleniumFinderRequest::BROWSER_FIREFOX_PLAYWRIGHT 
            || $request->getBrowser() === \SeleniumFinderRequest::BROWSER_CHROME_EXTENSION
            || $request->getBrowser() === \SeleniumFinderRequest::BROWSER_CHROME_PLAYWRIGHT
            || $request->getBrowser() === \SeleniumFinderRequest::BROWSER_BRAVE_PLAYWRIGHT
        ) {
            // should match https://github.com/AwardWallet/webdriver-cluster/blob/8cdf5adc597e86b68b9069e8205527be061400b7/selenoid/browsers.json#L23-L23
            $capabilities->setCapability(WebDriverCapabilityType::BROWSER_NAME, $request->getBrowser());
            $capabilities->setCapability(WebDriverCapabilityType::VERSION, $request->getVersion());
        }

        $chromeOptions = new ChromeOptions();
        if ($request->getVersion() >= 80 && $request->getBrowser() === SeleniumFinderRequest::BROWSER_CHROMIUM) {
            $chromeOptions->setBinary("/chrome/chrome");
        }
        
        if ($seleniumOptions->timezone !== null) {
            if ($request->getVersion() >= 100 
                && $request->getBrowser() !== SeleniumFinderRequest::BROWSER_CHROME_PUPPETEER 
                && $request->getBrowser() !== SeleniumFinderRequest::BROWSER_FIREFOX_PLAYWRIGHT
                && $request->getBrowser() !== SeleniumFinderRequest::BROWSER_CHROME_PLAYWRIGHT
                && $request->getBrowser() !== SeleniumFinderRequest::BROWSER_BRAVE_PLAYWRIGHT
                && $request->getBrowser() !== SeleniumFinderRequest::BROWSER_CHROME_EXTENSION
            ) {
                // selenoid
                $capabilities->setCapability('timeZone', $seleniumOptions->timezone);
            }
            else {
                $chromeOptions->setBinary('/opt/selenium/scripts/browser-starter.sh');
                $chromeOptions->addArguments(["--TZ=" . $seleniumOptions->timezone]); // will be stripped out in /chromium-starter script
            }
        }

        $startupUrl = $this->startupUrl . "?text=" . urlencode($seleniumOptions->startupText);
		$prefs = [
			'download.prompt_for_download' => false,
			'intl.accept_languages' 	   => 'en',
//			'disable-popup-blocking' 	   => true,
			'profile.default_content_settings.popups' => 0,
			'credentials_enable_service' => false,
			'profile.password_manager_enabled' => false,
            // @TODO: startup url not working, don't know how to fix
			"session" => ["restore_on_startup" => 4, "startup_urls" => [$startupUrl]], // chromedriver actualy ignores this setting, will navigate later
            "webrtc.ip_handling_policy" => "disable_non_proxied_udp",
            "webrtc.multiple_routes_enabled" => false,
            "webrtc.nonproxied_udp_enabled"  => false,
		];
        $prefs['download.default_directory'] = $downloadFolder;
		// TODO: bug, should not create profile here, remove, test cleanup scripts ???
		//$chromeOptions->addArguments(array("user-data-dir=/tmp/chromium" . basename($downloadFolder)));

        if(!empty($seleniumOptions->proxyUser)) {
            $file = $this->createChromeProxyAuthExtension($seleniumOptions);
            $chromeOptions->addExtensions([$file]);
            unlink($file);
        }

        if ($seleniumOptions->addHideSeleniumExtension) {
            $file = BrowserExtensions::createHideSeleniumExtension($request->getBrowser(), $request->getVersion(),
                $seleniumOptions->userAgent, $seleniumOptions->fingerprint, $seleniumOptions->fingerprintParams);
            $chromeOptions->addExtensions([$file]);
            unlink($file);
        }

        [$file, $requestElementId, $responseElementId] = BrowserExtensions::createBridgeExtension();
        $chromeOptions->addExtensions([$file]);
        unlink($file);

        if ($seleniumOptions->recordRequests) {
            $this->logger->info("creating request recorder extension");
            $file = BrowserExtensions::createRequestRecorderExtensionZip();
            $chromeOptions->addExtensions([$file]);
            unlink($file);
        }

        if ($seleniumOptions->addPuppeteerStealthExtension) {
            $this->logger->info("adding puppeteer stealth extension");
            $file = BrowserExtensions::createPuppeteerStealthExtension();
            $chromeOptions->addExtensions([$file]);
            unlink($file);
        }

        if ($seleniumOptions->addAntiCaptchaExtension) {
            $this->logger->info("adding anti captcha extension");
            $file = BrowserExtensions::createAntiCaptchaExtensionZip($seleniumOptions->antiCaptchaProxyParams);
            $chromeOptions->addExtensions([$file]);
            unlink($file);
        }

        if ($request->getBrowser() === \SeleniumFinderRequest::BROWSER_CHROME_PUPPETEER && !empty($seleniumOptions->pacFile)) {
            $chromeOptions->addArguments(array("--proxy-pac-url={$seleniumOptions->pacFile}"));
        }
        elseif(!empty($seleniumOptions->pacFile)){
            $this->logger->info("setting chrome pac file: " . $seleniumOptions->pacFile);
            $capabilities->setCapability('proxy', [
                'proxyType' => 'pac',
                'proxyAutoconfigUrl' => $seleniumOptions->pacFile
            ]);
        }
        elseif (!empty($seleniumOptions->proxyHost)) {
            $this->logger->info("set chrome proxy: {$seleniumOptions->proxyHost}:{$seleniumOptions->proxyPort}");
            $chromeOptions->addArguments(array("--proxy-server=http://{$seleniumOptions->proxyHost}:{$seleniumOptions->proxyPort}"));
        }

		if(!empty($seleniumOptions->userAgent)) {
			$this->logger->info("set user agent: " . $seleniumOptions->userAgent);
			$chromeOptions->addArguments(['--user-agent=' . $seleniumOptions->userAgent]);
            // for puppeteer-playwright
            $capabilities->setCapability('awUserAgent', $seleniumOptions->userAgent);
		}

        if (!empty($seleniumOptions->proxyHost)) {
            $capabilities->setCapability('awProxyHost', $seleniumOptions->proxyHost);
            $capabilities->setCapability('awProxyPort', $seleniumOptions->proxyPort);
        }

        if(!empty($seleniumOptions->proxyUser)) {
            $capabilities->setCapability('awProxyUser', $seleniumOptions->proxyUser);
            $capabilities->setCapability('awProxyPassword', $seleniumOptions->proxyPassword);
        }
        
		$chromeOptions->addArguments([/*"--reduce-security-for-testing", "--allow-running-insecure-content", */"--ignore-certificate-errors"]);

        // http://www.4byte.cn/question/499977/disable-images-in-selenium-google-chromedriver.html

		if(!$seleniumOptions->showImages)
			$prefs["profile.managed_default_content_settings.images"] = 2;

		$chromeOptions->addArguments(["--disable-print-preview"]);
		$chromeOptions->addArguments(["--dns-prefetch-disable"]);
		$chromeOptions->addArguments(["--no-sandbox"]);
		$chromeOptions->addArguments(["--unsafe-pac-url"]);
		$chromeOptions->addArguments(["--disk-cache-size=32000000"]);
		$chromeOptions->addArguments(["--disable-component-update"]);
		$chromeOptions->addArguments(["--translate-ranker-model-url=none"]);
		$chromeOptions->addArguments(["--disable-sync"]); // not working, want to disable request to https://accounts.google.com/ListAccounts?gpsia=1&source=ChromiumBrowser&json=standard
        //$chromeOptions->addArguments(["--disable-blink-features=AutomationControlled"]);

 		$chromeOptions->setExperimentalOption('prefs', $prefs);
//        $chromeOptions->setExperimentalOption("excludeSwitches", ["enable-automation"]);

        if(!empty($seleniumOptions->resolution)) {
            $this->logger->info("setting screen resolution: " . implode("x", $seleniumOptions->resolution));
            $chromeOptions->setExperimentalOption('mobileEmulation', [
                "deviceMetrics" => ["width" => $seleniumOptions->resolution[0], "height" => $seleniumOptions->resolution[1], "pixelRatio" => 1.0],
            ]);
        }

        $navigateToStartPage = true;
        if ($request->getBrowser() === SeleniumFinderRequest::BROWSER_CHROME_EXTENSION) {
            $capabilities->setCapability('awBrowserController', 'extension-v3-chrome');
            $this->logger->info("adding extension v3");
            $file = BrowserExtensions::createExtensionV3();
            $chromeOptions->addExtensions([$file]);
            unlink($file);
            $chromeOptions->addArguments([
                "--allow-pre-commit-input",
                "--disable-background-networking",
                "--disable-background-timer-throttling",
                "--disable-backgrounding-occluded-windows",
                "--disable-breakpad",
                "--disable-client-side-phishing-detection",
                "--disable-dev-shm-usage",
                "--disable-features=Translate,BackForwardCache,AcceptCHFrame,MediaRouter,OptimizationHints",
                "--disable-hang-monitor",
                "--disable-ipc-flooding-protection",
                "--disable-popup-blocking",
                "--disable-prompt-on-repost",
                "--disable-renderer-backgrounding",
                "--enable-blink-features=IdleDetection",
                "--enable-features=NetworkServiceInProcess2",
                "--export-tagged-pdf",
                "--force-color-profile=srgb",
                "--metrics-recording-only",
                "--no-first-run",
                "--password-store=basic",
                "--use-mock-keychain",
                //"--disable-extensions-except=",
                //"--no-sandbox",
                //"--disable-setuid-sandbox",
                "--disable-blink-features=AutomationControlled,AutomationControlled",
            ]);
            
            $startupUrl = str_replace("start?", "start.html?", $startupUrl);
            $startupUrl .= "&sessionId=blah";
            $chromeOptions->addArguments([$startupUrl]);
            $navigateToStartPage = false;
        }


        $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);
        $capabilities->setCapability(WebDriverCapabilityType::ACCEPT_SSL_CERTS, true);
        $capabilities->setCapability('loggingPrefs', [
            "browser" => "WARNING",
            "driver" => "WARNING",
            "performance" => "WARNING",
        ]);

        $sessionContext = [
            \AwardWallet\Common\Selenium\BrowserCommunicator::ATTR_REQUEST_ELEMENT_ID => $requestElementId,
            \AwardWallet\Common\Selenium\BrowserCommunicator::ATTR_RESPONSE_ELEMENT_ID => $responseElementId,
        ];

        if ($request->getVersion() >= 99 
            && $request->getBrowser() !== SeleniumFinderRequest::BROWSER_CHROME_PUPPETEER 
            && $request->getBrowser() !== SeleniumFinderRequest::BROWSER_FIREFOX_PLAYWRIGHT
            && $request->getBrowser() !== SeleniumFinderRequest::BROWSER_CHROME_PLAYWRIGHT
            && $request->getBrowser() !== SeleniumFinderRequest::BROWSER_BRAVE_PLAYWRIGHT
            && $request->getBrowser() !== SeleniumFinderRequest::BROWSER_CHROME_EXTENSION
        ) {
            $sessionContext[SeleniumStarter::CONTEXT_NEW_WEBDRIVER] = true;
        }

        if ($request->getVersion() == 99) {
            $sessionContext[SeleniumStarter::CONTEXT_WEBDRIVER_PATH] = '';
        }

        if ($request->getVersion() >= 100) {
            $capabilities->setCapability('enableVNC', true);
        }

        if (
            $request->getBrowser() === SeleniumFinderRequest::BROWSER_FIREFOX_PLAYWRIGHT
            || $request->getBrowser() === SeleniumFinderRequest::BROWSER_CHROME_PLAYWRIGHT
            || $request->getBrowser() === SeleniumFinderRequest::BROWSER_BRAVE_PLAYWRIGHT
        ) {
            $capabilities->setCapability('awBrowserController', 'playwright');
        }

        if ($seleniumOptions->fingerprintOptions) {
            $capabilities->setCapability('fingerprintOptions', $seleniumOptions->fingerprintOptions);
        }

        return new SeleniumSessionRequest(
            $capabilities,
            function($webDriver) use ($startupUrl, $navigateToStartPage) {
                // no strict types because we are migrating to new php-webdriver
                /** @var RemoteWebDriver $webDriver */
                if ($navigateToStartPage) {
                    $webDriver->get($startupUrl);
                }
            },
            $sessionContext
        );
    }

	// https://github.com/RobinDev/Selenium-Chrome-HTTP-Private-Proxy
	private function createChromeProxyAuthExtension(SeleniumOptions $seleniumOptions) : string
    {
		$file = sys_get_temp_dir() . "/chromeproxy-" . bin2hex(random_bytes(10)) . '.zip';
		$zip = new ZipArchive();
		$res = $zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE);
		if($res !== true)
			throw new \Exception("Can't create chrome extension at $file");
		$zip->addFile(__DIR__ . '/extensions/chrome-proxy-auth/manifest.json', 'manifest.json');
        $background = file_get_contents(__DIR__ . '/extensions/chrome-proxy-auth/background.js');
        $this->logger->info("creating chrome auth extension with username {$seleniumOptions->proxyUser}");
		$background = str_replace(
		    [
		        '%username%',
                '%password%',
                '%proxy-auth-base64%',
            ],
            [
                $seleniumOptions->proxyUser,
                $seleniumOptions->proxyPassword,
                base64_encode($seleniumOptions->proxyUser . ':' . $seleniumOptions->proxyPassword)
            ],
            $background
        );
		$zip->addFromString('background.js', $background);
		$zip->close();

		return $file;
	}

}
