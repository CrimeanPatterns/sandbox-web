<?php

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Firefox\FirefoxProfile;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\WebDriverBrowserType;
use Facebook\WebDriver\Remote\WebDriverCapabilityType;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverPlatform;

class FirefoxStarter
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

    public function prepareSession(
        SeleniumOptions $seleniumOptions,
        string $downloadFolder,
        SeleniumFinderRequest $request
    ): SeleniumSessionRequest {
        $this->logger->debug("selenium browser: Firefox");

        $sessionContext = [];

        $capabilities = DesiredCapabilities::firefox();

        $firefoxOptions = [
//            'log' => ['level' => 'trace'],
            // does not work for new seleniums            'service-args' => ["--log", "trace"],
        ];

        if ($seleniumOptions->timezone !== null) {
            throw new \Exception("Timezone support not implemented in firefox");
            $firefoxOptions = array_merge($firefoxOptions, [
                'binary' => '/opt/selenium/scripts/browser-starter.sh',
                'args' => ["--TZ=" . $seleniumOptions->timezone] // will be stripped out in /chromium-starter script
            ]);
        }

        $prefs = [
            "accessibility.support.url" => "http://localhost:4448",
            "app.feedback.baseURL" => "http://localhost:4448",
            "app.normandy.shieldLearnMoreUrl" => "http://localhost:4448",
            "app.normandy.api_url" => "http://localhost:4448",
            "app.releaseNotesURL" => "http://localhost:4448",
            "app.releaseNotesURL.aboutDialog" => "http://localhost:4448",
            "app.support.baseURL" => "http://localhost:4448",
            "app.update.url" => "http://localhost:4448",
            "app.update.url.details" => "http://localhost:4448",
            "app.update.url.manual" => "http://localhost:4448",
            "browser.chrome.errorReporter.infoURL" => "http://localhost:4448",
            "browser.contentblocking.report.cookie.url" => "http://localhost:4448",
            "browser.dictionaries.download.url" => "http://localhost:4448",
            "browser.partnerlink.attributionURL" => "http://localhost:4448",
            "browser.region.network.url" => "http://localhost:4448",
            "browser.safebrowsing.downloads.remote.url" => "http://localhost:4448",
            "browser.safebrowsing.provider.google.advisoryURL" => "http://localhost:4448",
            "browser.safebrowsing.provider.google.gethashURL" => "http://localhost:4448",
            "browser.safebrowsing.provider.google.updateURL" => "http://localhost:4448",
            "browser.safebrowsing.provider.google4.advisoryURL" => "http://localhost:4448",
            "browser.safebrowsing.provider.google4.dataSharingURL" => "http://localhost:4448",
            "browser.safebrowsing.provider.google4.gethashURL" => "http://localhost:4448",
            "browser.safebrowsing.provider.google4.reportMalwareMistakeURL" => "http://localhost:4448",
            "browser.safebrowsing.provider.google4.reportPhishMistakeURL" => "http://localhost:4448",
            "browser.safebrowsing.provider.google4.reportURL" => "http://localhost:4448",
            "browser.safebrowsing.provider.google4.updateURL" => "http://localhost:4448",
            "browser.safebrowsing.provider.mozilla.gethashURL" => "http://localhost:4448",
            "browser.safebrowsing.provider.mozilla.updateURL" => "http://localhost:4448",
            "media.gmp-manager.url" => "http://localhost:4448",
            "media.gmp-gmpopenh264.enabled" => false,
            "media.gmp-manager.certs.1.commonName" => "localhost",
            "media.gmp-manager.certs.2.commonName" => "localhost",
            "extensions.systemAddon.update.enabled" => false,
            "extensions.systemAddon.update.url" => "http://localhost:4448",
            "browser.safebrowsing.reportPhishURL" => "http://localhost:4448",
            "services.sync.prefs.sync.browser.newtabpage.activity-stream.feeds.snippets" => false,
            "browser.newtabpage.activity-stream.feeds.snippets" => false,
            "media.gmp-provider.enabled" => false,
            "dom.push.enabled" => false,
            "browser.download.dir" => $downloadFolder,
            "browser.download.folderList" => 2,
            "browser.helperApps.neverAsk.saveToDisk" => "application/pdf, application/octet-stream, application/zip",
            "pdfjs.disabled" => true,
            "browser.startup.homepage" => $this->startupUrl . "?text=" . urlencode($seleniumOptions->startupText),
            "browser.sessionstore.resume_session_once" => true,

            // disable multi-process to reduce resources consumption
            "browser.tabs.remote.autostart" => false,
            "browser.tabs.remote.autostart.2" => false,
            "dom.ipc.processCount" => 1,
            "dom.webdriver.enabled" => false,
            "browser.tabs.remote.separatePrivilegedContentProcess" => false,

            "image.animation_mode" => 'once',
            "security.insecure_field_warning.contextual.enabled" => false,
            "browser.newtabpage.enhanced" => false,
            "browser.newtabpage.preload" => false,
            "browser.newtabpage.directory.ping" => "",
            "browser.newtabpage.directory.source" => "http://localhost:4448",
            "geo.enabled" => false,
            "browser.search.geoip.url" => "data:application/json,{}",
            "browser.safebrowsing.provider.mozilla.gethashURL" => "http://localhost:4448",
            "browser.safebrowsing.provider.mozilla.updateURL" => "http://localhost:4448",
            "app.update.auto" => false,
            "app.update.download.attempts" => 0,
            "app.update.elevate.attempts" => 0,
            "browser.safebrowsing.malware.enabled" => false,
            "browser.safebrowsing.phishing.enabled" => false,
            "browser.cache.disk.enable" => false,
            "browser.cache.memory.capacity" => "64000",
            "xpinstall.signatures.required" => false,
            "media.peerconnection.enabled" => false,
            "devtools.jsonview.enabled" => false,
            "devtools.netmonitor.persistlog" => true,
            "devtools.theme" => "light",
//            "dom.webdriver.enabled" => false,
//            "useAutomationExtension" => false,
        ];

        if (!empty($seleniumOptions->userAgent)) {
            $this->logger->info("setting firefox useragent to {$seleniumOptions->userAgent}");
            $prefs['general.useragent.override'] = $seleniumOptions->userAgent;
        }

        $extensions = [];

        $setProxyAuth = false;

        if (!empty($seleniumOptions->proxyUser)) {
            if ((int)$request->getVersion() >= 57) {
                $setProxyAuth = true;
            } else {
                // https://eveningsamurai.wordpress.com/2013/11/21/changing-http-headers-for-a-selenium-webdriver-request/
                $this->logger->info("set modify_headers firefox proxy auth: " . $seleniumOptions->proxyUser);
                $extensions[] = __DIR__ . '/extensions/modify_headers.xpi';
                $prefs = array_merge($prefs, [
                    "modifyheaders.config.active" => true,
                    "modifyheaders.config.alwaysOn" => true,
                    "modifyheaders.headers.count" => 1,
                    "modifyheaders.headers.action0" => "Add",
                    "modifyheaders.headers.name0" => "Proxy-Authorization",
                    "modifyheaders.headers.value0" => "Basic " . base64_encode($seleniumOptions->proxyUser . ":" . $seleniumOptions->proxyPassword),
                    "modifyheaders.headers.enabled0" => true,
                ]);
            }
        }

        $deleteFiles = [];

        if (!empty($seleniumOptions->pacFile)) {
            $this->logger->info("setting firefox pac file: " . $seleniumOptions->pacFile);
            // old versions
            $capabilities->setCapability('proxy', [
                'proxyType' => 'pac',
                'proxyAutoconfigUrl' => $seleniumOptions->pacFile
            ]);
            // new versions, starting with 53
            $prefs = array_merge($prefs, [
                "network.proxy.autoconfig_url.include_path" => true,
                "network.proxy.type" => 2,
                "network.proxy.autoconfig_url" => $seleniumOptions->pacFile,
            ]);
        } elseif (!empty($seleniumOptions->proxyHost)) {
            $this->logger->info("setting firefox proxy {$seleniumOptions->proxyHost}:{$seleniumOptions->proxyPort}");
            $prefs = array_merge($prefs, [
                "network.proxy.type" => 1,
                "network.proxy.http" => $seleniumOptions->proxyHost,
                "network.proxy.http_port" => (int)$seleniumOptions->proxyPort,
                "network.proxy.ssl" => $seleniumOptions->proxyHost,
                "network.proxy.ssl_port" => (int)$seleniumOptions->proxyPort,
                "network.proxy.no_proxies_on" => "localhost:4448, .mozilla.org",
            ]);
        } else {
            // // firefox does not respect pac file with proxy, it will use direct connection
        }

        if (!$seleniumOptions->showImages) {
            $prefs["permissions.default.image"] = 2;
        }

        $extensionsDir = sys_get_temp_dir() . "/hide-selenium-" . bin2hex(random_bytes(10)) . '/extensions';
        $sessionContext = array_merge($sessionContext, $this->createExtensionsToDir(
            $extensionsDir,
            SeleniumFinderRequest::BROWSER_FIREFOX, $request->getVersion(),
            $seleniumOptions
        ));
        $deleteFiles[] = $extensionsDir;

        if ($seleniumOptions->profile !== null) {
            $profile = base64_encode($this->updateProfile($seleniumOptions->profile, $prefs, $extensionsDir, $request->getVersion()));
            if ($request->getVersion() >= 59) {
                $this->logger->info("loading browser profile, ff >= 59");
//                $capabilities->setCapability('marionette', true);
                $firefoxOptions = array_merge($firefoxOptions, [
//                    'args' => ["-profile", "/tmp/rust_mozprofile.fQyoD9iiyzob"],
//                    'service-args' => ["--marionette-port", "12345"],
                    'profile' => $profile,
                ]);
            } else {
                $this->logger->info("loading browser profile, ff < 59");
                $capabilities->setCapability('firefox_profile', $profile);
            }
            if (!empty($seleniumOptions->connectionContext)) {
                $sessionContext = $seleniumOptions->connectionContext;
            }
        } else {
            $profile = new FirefoxProfile();

            $this->logger->debug("setting preferences");
            foreach ($prefs as $key => $value) {
                $profile->setPreference($key, $value);
            }

            $this->logger->debug("adding extensions");
            foreach ($extensions as $extension) {
                $profile->addExtension($extension);
            }

            // unsigned extensions require firefox developer edition
            // we have developer edition only for recent firefox releases:
            // https://download-origin.cdn.mozilla.net/pub/devedition/releases/
            if ((int)$request->getVersion() >= 59) {
                $this->logger->debug("add unsigned extensions");
                $profile->addExtensionDatas($extensionsDir);
            }

            $capabilities->setCapability('firefox_profile', $profile);
        }

        $capabilities->setCapability('acceptInsecureCerts', true);
        $capabilities->setCapability(WebDriverCapabilityType::ACCEPT_SSL_CERTS, true);
        $capabilities->setCapability('loggingPrefs', [
            "browser" => "WARNING",
            "client" => "WARNING",
            "driver" => "WARNING",
            "performance" => "WARNING",
            "server" => "WARNING",
        ]);

        if (count($firefoxOptions) > 0) {
            $capabilities->setCapability('moz:firefoxOptions', $firefoxOptions);
        }

        if ($request->getVersion() >= 100) {
            $capabilities->setCapability('enableVNC', true);
        }

        $onDriverCreated = function ($webDriver) use (
            $setProxyAuth,
            $seleniumOptions,
            $deleteFiles,
            $request
        ) {
            /** @var \RemoteWebDriver $webDriver */
            if ($setProxyAuth) {
                // generally this hack is not required on versions greater than 59,
                // auth should be done through proxy auth extension
                // but sometimes it does not work. extension is not starting
                // so, we will check proxy anyway for auth popup

                // hack, not reliable, but other options are equally bad:
                //  - switch to geckodriver, and use POST /session/{session id}/moz/addon/install
                //      https://github.com/mozilla/geckodriver/issues/473#issuecomment-312094179
                //  - sign addon
                //  - use signed modify_headers addon, and hack into addon localStorage
                $this->logger->info("setting proxy auth through dialog");
                $this->logger->info("requesting any url to trigger http proxy auth");
                $body = "unknown";
                try {
                    $webDriver->get("https://s3.amazonaws.com/awardwallet-public/healthcheck.html?proxy");
                }
                catch(\UnexpectedAlertOpenException | \Facebook\WebDriver\Exception\UnexpectedAlertOpenException $exception) {
                    $this->logger->debug("ignoring auth request, will be handled by chrome-proxy-auth extension: " . $exception->getMessage());
                }
                // second time auth should be handler by chrome-proxy-auth extension
                try {
                    $webDriver->get("https://s3.amazonaws.com/awardwallet-public/healthcheck.html?proxy");
                    $body = $webDriver->getPageSource();
                }
                catch(\UnexpectedAlertOpenException | \Facebook\WebDriver\Exception\UnexpectedAlertOpenException $exception) {
                    $this->logger->info("still have auth request: " . $exception->getMessage());
                }
                if (stripos($body, 'function clicked') === false) {
                    $startTime = microtime(true);
                    while ((microtime(true) - $startTime) < 5) {
                        try {
                            $alert = $webDriver->switchTo()->alert();
                            $alert->sendKeys($seleniumOptions->proxyUser);
                            $alert->sendKeys(WebDriverKeys::TAB . $seleniumOptions->proxyPassword);
                            $alert->accept();
                            break;
                        } catch (NoAlertOpenException|\Facebook\WebDriver\Exception\NoSuchAlertException $e) {
                            $this->logger->info("no auth alert open, will wait");
                            try {
                                $button = $webDriver->findElement(WebDriverBy::id('button'));
                                $this->logger->info("button found, no auth required");
                                break;
                            } catch (WebDriverException|\Facebook\WebDriver\Exception\WebDriverException $exception) {
                            }
                            usleep(random_int(400000, 800000));
                        }
                    }
                }

                foreach ($deleteFiles as $file) {
                    self::delTree($file);
                }
            }
        };

        if ($request->getVersion() >= 85) {
            $sessionContext[SeleniumStarter::CONTEXT_NEW_WEBDRIVER] = true;
        }

        return new SeleniumSessionRequest(
            $capabilities,
            $onDriverCreated,
            $sessionContext
        );
    }

    private static function delTree($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? self::delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    private function updateProfile(string $profileZipData, array $prefs, string $extensionsDir, int $browserVersion): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), "firefox-profile") . ".zip";
        if (file_put_contents($tempFile, $profileZipData) === false) {
            throw new \Exception("failed to save profile data to $tempFile");
        }
        $tempDir = sys_get_temp_dir() . "/firefox-profile-" . bin2hex(random_bytes(6));
        mkdir($tempDir);
        try {
            $zip = new \ZipArchive();
            $zip->open($tempFile);
            $zip->extractTo($tempDir);
            $zip->close();

            $this->updatePrefs($tempDir, $prefs);
            if ($browserVersion >= 59) {
                $this->replaceExtensions($tempDir, $extensionsDir);
            }

            unlink($tempFile);
            $tempFile = \AwardWallet\Common\Zip::zipDir($tempDir, []);

            return file_get_contents($tempFile);
        } finally {
            unlink($tempFile);
            try {
                self::delTree($tempDir);
            } catch (ErrorException $e) {
                // scandir(/tmp/firefox-profile-8fa708f8d670/extensions): failed to open dir: No such file or directory trace:
                $this->logger->warning("alert: " . $e->getMessage());
            }
        }
    }

    private function updatePrefs(string $dir, array $prefs) : void
    {
        try {
            $prefsContent = file_get_contents($dir.'/user.js');
        } catch (ErrorException $e) {
            $this->logger->info("alert: " . $e->getMessage());
            $prefsContent = false;
        }
        if ($prefsContent === false) {
            $this->logger->info("missing user.js");
            return;
        }

        $newPrefsContent = $this->applyPrefsToUserJs($prefsContent, $prefs);
        if ($newPrefsContent === null) {
            return;
        }

        file_put_contents("user.js", $newPrefsContent);
    }

    private function replaceExtensions(string $profileDir, string $extensionsDir) : void
    {
        self::delTree($profileDir . "/extensions");
        \AwardWallet\Common\FileSystem::recursiveCopy($extensionsDir, $profileDir . "/extensions");
    }

    private function applyPrefsToUserJs(string $prefsContent, array $prefs): ?string
    {
        $lines = explode("\n", $prefsContent);
        if (count($lines) < 10) {
            $this->logger->warning("too few lines in prefs.js");
            return null;
        }

        $applied = 0;
        foreach ($prefs as $key => $value) {
            $found = false;
            foreach ($lines as &$line) {
                if (strpos($line, "user_pref(\"{$key}\"") === 0) {
//                    $this->logger->debug("applying {$key}: {$value}");
                    $line = "user_pref(\"{$key}\", " . json_encode($value, JSON_UNESCAPED_SLASHES) . ");";
                    $found = true;
                    $applied++;
                    break;
                }
            }
            if (!$found) {
                $this->logger->notice("pref $key not found, creating");
                $lines[] = "user_pref(\"{$key}\", " . json_encode($value, JSON_UNESCAPED_SLASHES) . ");";
            }
        }

        if ($applied === 0) {
            $this->logger->notice("no prefs applied");
            return null;
        }

        return implode("\n", $lines);
    }

    /**
     * @return array - SessionContext to merge
     */
    private function createExtensionsToDir(
        string $extensionsDir,
        string $browser,
        int $version,
        SeleniumOptions $seleniumOptions
    ) : array
    {
        if ($seleniumOptions->addHideSeleniumExtension) {
            $dir = $extensionsDir . '/hide-selenium@awardwallet.com';
            mkdir($dir, 0777, true);
            copy(__DIR__ . '/extensions/chrome-hide-selenium/manifest.json', $dir . '/manifest.json');
            $this->logger->info("firefox fingerprintParams: " . json_encode(isset($seleniumOptions->fingerprintParams)) . ", fingerPrint: " . json_encode(isset($seleniumOptions->fingerprint)));
            file_put_contents($dir . '/injected-javascript.js', BrowserExtensions::replaceFingerprintParams(
                file_get_contents(__DIR__ . '/extensions/chrome-hide-selenium/injected-javascript.js'),
                $seleniumOptions->fingerprintParams ?? new FingerprintParams($browser, $version, $seleniumOptions->userAgent, $seleniumOptions->fingerprint)
            ));
        }

        if ($seleniumOptions->addPuppeteerStealthExtension) {
            $this->logger->info("adding puppeteer stealth extension");
            $dir = $extensionsDir . '/pup-stealth-selenium@awardwallet.com';
            mkdir($dir, 0777, true);
            copy(__DIR__ . '/extensions/puppeteer-stealth/manifest.json', $dir . '/manifest.json');
            copy(__DIR__ . '/extensions/puppeteer-stealth/background.js', $dir . '/background.js');
            copy(__DIR__ . '/extensions/puppeteer-stealth/dist/content.js', $dir . '/dist/content.js');
        }

        if ($seleniumOptions->proxyUser !== null && $version > 59) {
            $this->createProxyAuthExtension($extensionsDir . '/proxy-auth@awardwallet.com', $seleniumOptions->proxyUser, $seleniumOptions->proxyPassword);
        }

        [$file, $requestElementId, $responseElementId] = BrowserExtensions::createBridgeExtension();

        if ($seleniumOptions->recordRequests) {
            $this->logger->info("creating request recorder extension");
            BrowserExtensions::createRequestRecorderExtension($extensionsDir . '/request-recorder@awardwallet.com');
        }

        if ($seleniumOptions->addAntiCaptchaExtension) {
            $this->logger->info("creating anticaptcha extension");
            BrowserExtensions::createAntiCaptchaExtension($extensionsDir . '/anti-captcha@awardwallet.com', $seleniumOptions->antiCaptchaProxyParams);
        }

        $sessionContext = [
            \AwardWallet\Common\Selenium\BrowserCommunicator::ATTR_REQUEST_ELEMENT_ID => $requestElementId,
            \AwardWallet\Common\Selenium\BrowserCommunicator::ATTR_RESPONSE_ELEMENT_ID => $responseElementId,
        ];

        $zip = new \ZipArchive();
        $zip->open($file);
        $zip->extractTo($extensionsDir . '/bridge@awardwallet.com');
        $zip->close();
        unlink($file);

        return $sessionContext;
    }

    // https://github.com/RobinDev/Selenium-Chrome-HTTP-Private-Proxy
    // https://developer.mozilla.org/en-US/docs/Mozilla/Add-ons/WebExtensions/API/webRequest/onAuthRequired
    private function createProxyAuthExtension(string $dir, string $proxyUser, string $proxyPassword) : void
    {
        mkdir($dir, 0777, true);
        copy(__DIR__ . '/extensions/chrome-proxy-auth/manifest.json', $dir . '/manifest.json');
        $background = file_get_contents(__DIR__ . '/extensions/chrome-proxy-auth/background.js');
        $this->logger->info("creating proxy auth extension with username {$proxyUser}");
        $background = str_replace(
            [
                '%username%',
                '%password%',
                '%proxy-auth-base64%',
            ],
            [
                $proxyUser,
                $proxyPassword,
                base64_encode($proxyUser . ':' . $proxyPassword)
            ],
            $background
        );
        file_put_contents($dir . '/background.js', $background);
    }

}
