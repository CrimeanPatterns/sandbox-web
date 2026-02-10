<?php

class SeleniumDriver implements HttpDriverInterface
{

    protected $started = false;
    /**
     * @var bool
     * @@ TODO: make private, deprecate direct usage
     */
    public $keepCookies = true;
    /**
     * @var bool
     * @@ TODO: make private, deprecate direct usage
     */
    public $keepSession = false;
    /**
     * @TODO: remove, deprecated
     * @var RemoteWebDriver
     */
    public $webDriver;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    /**
     * @var SeleniumConnector
     */
    private $connector;
    /**
     * @var SeleniumFinderRequest
     */
    private $finderRequest;
    /**
     * @var SeleniumOptions
     */
    private $seleniumOptions;
    /**
     * @var SeleniumConnection
     */
    private $session;
    /**
     * @var SeleniumOptions
     */
    private $startOptions;
    /**
     * @var SeleniumConnection
     */
    private $connection;
    /**
     * @var array
     */
    private $state;
    /**
     * @var bool
     */
    private $newSession;
    /**
     * function (SeleniumOptions $startOptions)
     * @var Callable
     */
    public $onStart;
    /**
     * keep entire browser profile in state
     * @var bool
     */
    private $keepProfile = false;
    /**
     * @var \AwardWallet\Common\Selenium\FirefoxProfileManager
     */
    private $firefoxProfileManager;
    /**
     * @var \AwardWallet\Common\Selenium\BrowserCommunicator
     */
    public $browserCommunicator;
    /**
     * @var string
     */
    private $browserControlUrl;
    /**
     * @var \AwardWallet\Common\Selenium\DownloadManager
     */
    private $downloadManager;
    /**
     * @var \AwardWallet\Common\Selenium\HotPoolManager
     */
    private $hotPoolManager;
    /**
     * @var \AwardWallet\Common\Selenium\HotSession\HotPoolManager
     */
    private $hotSessionPoolManager;
    /**
     * @var array
     */
    private $stateOnStop;
    /**
     * @var bool
     */
    private $getStateCalled = false;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        SeleniumConnector $connector,
        SeleniumFinderRequest $finderRequest,
        SeleniumOptions $seleniumOptions,
        \AwardWallet\Common\Selenium\FirefoxProfileManager $firefoxProfileManager,
        \AwardWallet\Common\Selenium\DownloadManager $downloadManager,
        string $browserControlUrl,
        \AwardWallet\Common\Selenium\HotPoolManager $hotPoolManager,
        \AwardWallet\Common\Selenium\HotSession\HotPoolManagerInterface $hotSessionPoolManager
    ) {
        $this->logger = $logger;
        $this->connector = $connector;
        $this->finderRequest = $finderRequest;
        $this->seleniumOptions = $seleniumOptions;
        $this->firefoxProfileManager = $firefoxProfileManager;
        $this->browserControlUrl = $browserControlUrl;
        $this->downloadManager = $downloadManager;
        $this->hotPoolManager = $hotPoolManager;
        $this->hotSessionPoolManager = $hotSessionPoolManager;
    }

    public function start($proxy = null, $proxyLogin = null, $proxyPassword = null, $userAgent = null)
    {
        if ($userAgent === HttpBrowser::PUBLIC_USER_AGENT || $userAgent === HttpBrowser::PROXY_USER_AGENT) {
            // keep browser default user agent
            $userAgent = null;
        }

        if ($userAgent === HttpBrowser::PROXY_USER_AGENT && $this->finderRequest->getBrowser() === SeleniumFinderRequest::BROWSER_FIREFOX && stripos($userAgent, 'firefox') === false) {
            // keep browser default user agent
            $userAgent = HttpBrowser::FIREFOX_USER_AGENT;
        }

        if ($userAgent === HttpBrowser::PROXY_USER_AGENT && $this->finderRequest->getBrowser() !== SeleniumFinderRequest::BROWSER_FIREFOX && stripos($userAgent, 'chrome') === false) {
            // keep browser default user agent
            $userAgent = HttpBrowser::PROXY_USER_AGENT;
        }

        if ($this->started) {
            return;
        }

        if ($this->state !== null && is_array($this->state)) {
            $this->logger->info("selenium driver state keys: " . implode(", ", array_keys($this->state)));
        }

        if ($this->connectToHotSession()) {
            $this->started = true;

            return;
        }

        if (
            !empty($this->state['SessionID'])
            && !empty($this->state['Host'])
            && !empty($this->state['Port'])
            && !empty($this->state['Share'])
            && $this->restoreSession()
        ) {
            $this->logger->debug("session restored by SessionID");
            $this->started = true;

            return;
        }

        $this->startOptions = clone $this->seleniumOptions;

        if($this->onStart !== null){
            call_user_func($this->onStart, $this->startOptions);
        }

        if (!empty($proxy)) {
            [$this->startOptions->proxyHost, $this->startOptions->proxyPort] = explode(":", $proxy);
            $this->startOptions->proxyPort = (int)$this->startOptions->proxyPort;
        }

        $this->startOptions->proxyUser = $proxyLogin;
        $this->startOptions->proxyPassword = $proxyPassword;
        $this->startOptions->userAgent = $userAgent;

        if ($this->keepProfile && isset($this->state['Profile'])) {
            if ($this->state['Browser'] === $this->finderRequest->getBrowserName()) {
                $this->startOptions->profile = $this->state['Profile'];
                if (!empty($this->state['ConnectionContext'])) {
                    $this->startOptions->connectionContext = $this->state['ConnectionContext']; 
                }
            } else {
                $this->logger->notice("will not restore browser profile, state has profile of {$this->state["Browser"]}, we are starting {$this->finderRequest->getBrowserName()}");
            }
        }

        $this->connection = $this->connector->createSession($this->finderRequest, $this->startOptions);
        $this->webDriver = $this->connection->getWebDriver();
        $this->createBrowserCommunicator();

        if (!empty($this->state)) {
            $this->restoreState();
        }
        $this->newSession = true;
        $this->started = true;
    }

    /* for keep active hot session */
    public function startWithConnection(SeleniumConnection $connection)
    {
        if ($this->started) {
            return;
        }

        if ($this->state !== null && is_array($this->state)) {
            $this->logger->info("selenium driver state keys: " . implode(", ", array_keys($this->state)));
        }

        $this->webDriver = $connection->getWebDriver();
        $this->setConnection($connection);
        $this->setKeepSession(false);
        $this->setKeepProfile(false);
        $this->setKeepCookies(false);
        $this->started = true;
    }

    public function stop()
    {
        $this->started = false;
        $this->logger->info("close Selenium browser");

        if ($this->stateOnStop === null && !$this->getStateCalled) {
            try {
                $this->logger->info("stop called, saving stateOnStop");
            } catch (WebDriverException | Facebook\WebDriver\Exception\WebDriverException $e) {
                $this->logger->info("WebDriverException while saving saving stateOnStop: " . $e->getMessage());
            } catch (WebDriverCurlException | Facebook\WebDriver\Exception\WebDriverCurlException $e) {
                $this->logger->info("WebDriverCurlException while saving saving stateOnStop: " . $e->getMessage());
            }
            $this->stateOnStop = $this->getState();
        }

        if ($this->finderRequest->getHotPoolPrefix() && $this->connection) {
            if ($this->finderRequest->IsBackground() && $this->keepSession) {
                $this->logger->info("reset keepSession for background");
                $this->keepSession = false;
            }
            $this->stopHotPoolConnection();
        }

        if ($this->connection !== null && !$this->keepSession) {
            $this->connector->closeConnection($this->connection);
            $this->clearConnection();
        }

        if (
            !$this->keepSession
            && $this->connection !== null
        ) {
            $this->downloadManager->cleanup($this->connection);
        }
    }

    public function dontSaveStateOnStop() : void
    {
        $this->getStateCalled = true;
    }

    public function getServerAddress() : string
    {
        return $this->connection->getHost() . ':' . $this->connection->getPort();
    }

    /**
     * @return boolean
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * @param $url
     * @param string $method
     * @param mixed $postData
     * @param array $headers
     * @return HttpDriverResponse
     */
    public function request(HttpDriverRequest $request)
    {
        try {
            $this->webDriver->get($request->url);
        } catch (ScriptTimeoutException | TimeOutException | Facebook\WebDriver\Exception\TimeoutException $e) {
            $this->logger->notice("TimeoutException: " . $e->getMessage(), ['HtmlEncode' => true]);
            $this->webDriver->executeScript('window.stop();');
        }

        $result = new HttpDriverResponse();
        $result->request = $request;

        $try = 0;
        while ($try < 2) {
            try {
                $result->body = $this->webDriver->executeScript('return document.body.innerHTML');
                $result->httpCode = 200;
                break;
            } catch (
                UnexpectedJavascriptException
                | Facebook\WebDriver\Exception\JavascriptErrorException
                | Facebook\WebDriver\Exception\WebDriverException
                $e
            ) {
                $this->logger->notice("exception while getting body: " . $e->getMessage(), ['HtmlEncode' => true]);
                $result->body = $e->getMessage();
                $result->httpCode = 500;
                sleep(2);
            }
            $try++;
        }

        return $result;
    }

    public function setLogger(HttpLoggerInterface $logger)
    {
        // @TODO: rewrite logging
    }

    public function setConnection(SeleniumConnection $connection)
    {
        if ($connection && !$this->started) {
            $this->connection = $connection;
        }
    }

    private function restoreSession(): bool
    {
        $options = new SeleniumOptions();

        $session = new SeleniumSession(
            $this->state['SessionID'], 
            $this->state['Host'], 
            $this->state['Port'], 
            $this->state['Path'] ?? '/wd/hub',
            $this->state['Share'],
            $this->state["ConnectionContext"],
            $options);

        $webDriver = $this->connector->restoreSession($session);
        if ($webDriver === null) {
            unset($this->state['SessionID']);
            return false;
        }

        $this->connection = new SeleniumConnection($webDriver, $session->getSessionId(), $session->getHost(), $session->getPort(), $session->getPath(), $session->getShare(), $this->state['BrowserFamily'], $this->state['BrowserVersion'], $this->state['ConnectionContext'], $this->state['StartTime'] ?? null);
        $this->sessionRestored();

        return true;
    }

    /**
     * @return array
     */
    public function getState()
    {
        if (!$this->started && $this->stateOnStop !== null) {
            $this->logger->info("returning stateOnStop");
            return $this->stateOnStop;
        }

        $this->getStateCalled = true;

        if ($this->connection === null) {
            return [];
        }

        $result = [
            'DownloadFolder' => $this->connection->getShare(),
        ];

        if ($this->keepSession) {
            try {
                $result['SessionID'] = $this->webDriver->getSessionID();
                $result['ConnectionContext'] = $this->connection->getContext();
                $result['Host'] = $this->connection->getHost();
                $result['Port'] = $this->connection->getPort();
                $result['Path'] = $this->connection->getPath();
                $result['Share'] = $this->connection->getShare();
                $result['BrowserFamily'] = $this->connection->getBrowserFamily();
                $result['BrowserVersion'] = $this->connection->getBrowserVersion();
                $result['StartTime'] = $this->connection->getStartTime();
            } catch (
                WebDriverCurlException
                | Facebook\WebDriver\Exception\WebDriverCurlException
                | WebDriverException
                | Facebook\WebDriver\Exception\WebDriverException
                $e
            ) {
                $this->logger->notice("sorry, failed to get session id: " . $e->getMessage(), ['HtmlEncode' => true]);
                $this->clearConnection();
            }
            $this->logger->info("keep selenium session, id: " . $result['SessionID']);

            return $result;
        }

        if ($this->keepCookies && !$this->keepProfile && $this->connection !== null) {
            try {
                $result['Cookies'] = $this->webDriver->manage()->getCookies();
                $result['URL'] = $this->webDriver->getCurrentURL();
                $this->logger->info("saved selenium cookies, count: " . count($result['Cookies']));
                if ($this->browserCommunicator !== null && $this->browserCommunicator->isSupportedBrowser()) {
                    try {
                        $this->logger->info("getting cookies through browser communicator");
                        $result['BrowserCookies'] = $this->browserCommunicator->getCookies();
                        $this->logger->info("got " . count($result['BrowserCookies']) . " cookies from browser communicator");
                    }
                    catch (\Throwable $exception) {
                        $this->logger->warning("failed to get cookies through communicator: " . $exception->getMessage() . " at " . $exception->getFile() . ":" . $exception->getLine());
                    }
                }
            }
            catch (WebDriverException | Facebook\WebDriver\Exception\WebDriverException $e) {
                $this->logger->notice("sorry, failed to get cookies: " . $e->getMessage());
                $this->clearConnection();
            }
            catch (InvalidArgumentException $e) {
                if (stripos($e->getMessage(), 'Cookie name should be non-empty') === false) {
                    throw $e;
                }

                $this->logger->notice("sorry, failed to get cookies: " . $e->getMessage());
                $this->clearConnection();
            }
            catch (TypeError $e) {
                if (strpos($e->getMessage(), 'Cookie::createFromArray() must be of the type array, string given') != false
                ) {
                    $this->logger->notice("sorry, failed to get cookies: " . $e->getMessage(), ['HtmlEncode' => true]);
                    $this->clearConnection();
                } else {
                    throw $e;
                }
            }
            catch (WebDriverCurlException | Facebook\WebDriver\Exception\WebDriverCurlException $e) {
                $this->logger->notice("curl error getting cookies/url: " . $e->getMessage(), ['HtmlEncode' => true]);
                $this->clearConnection();
            }
        }

        if ($this->keepProfile && $this->connection !== null) {
            if ($this->connection->getBrowserFamily() !== \SeleniumFinderRequest::BROWSER_FIREFOX) {
                throw new \Exception("KeepProfile supported only on firefox");
            }
            // for communcating with browser bridge extension
            $result['ConnectionContext'] = $this->connection->getContext();
            $profile = $this->firefoxProfileManager->getProfileAndStop($this->connection);
            if ($profile !== null) {
                $result['Profile'] = $profile;
                $profileSize = strlen($result['Profile']);
                if ($profileSize > 5000) {
                    $file = sys_get_temp_dir() . "/last-big-profile.zip";
                    file_put_contents($file, $result['Profile']);
                    $this->logger->notice("browser profile is too long {$profileSize}, saved to: $file");
                }
                $result['Browser'] = $this->finderRequest->getBrowserName();
                $this->logger->info("got browser profile, size: " . strlen($result['Profile']));
                if ($result['Profile'] === null) {
                    unset($result['Profile']);
                }
            }
            $this->stop();
            return $result;
        }

        $this->logger->info(__METHOD__ . " done");

        return $result;
    }

    public function setState(array $state)
    {
        $this->state = $state;
    }

    private function restoreState()
    {
        $this->logger->info(__METHOD__);
        if (!empty($this->state['Cookies']) && !empty($this->state['URL']) && $this->keepCookies) {

            if ($this->startOptions->profile !== null && $this->keepProfile) {
                // requires browser.sessionstore.resume_session_once = true in firefox
                $this->logger->info("[keepProfile]: cookies should be restored through profile, do not restore them");
                $this->logger->info("[keepProfile]: navigating to last known URL: {$this->state['URL']}");

                try {
                    $this->webDriver->get($this->state['URL']);
                } catch (Facebook\WebDriver\Exception\UnknownErrorException $exception) {
                    $this->logger->error("Exception: " . (strlen($exception->getMessage()) > 40 ? substr($exception->getMessage(), 0, 37) . '...' : $exception->getMessage()));
                } catch (Facebook\WebDriver\Exception\TimeoutException | TimeoutException $exception) {
                    $this->logger->error("Exception: " . (strlen($exception->getMessage()) > 40 ? substr($exception->getMessage(), 0, 37) . '...' : $exception->getMessage()));
                    $this->webDriver->executeScript('window.stop();');
                }
                return;
            }

            if ($this->browserCommunicator !== null && $this->browserCommunicator->isSupportedBrowser() && isset($this->state['BrowserCookies'])) {
                $this->logger->info("restoring cookies through extension, current url: " . $this->webDriver->getCurrentURL());
                try {
                    $this->browserCommunicator->setCookies($this->state['BrowserCookies']);
                }
                catch (\AwardWallet\Common\Selenium\BrowserCommunicatorException $exception) {
                }
                $this->logger->info("navigating to last known URL: {$this->state['URL']}");
                try {
                    $this->webDriver->get($this->state['URL']);
                } catch (Facebook\WebDriver\Exception\UnknownErrorException $exception) {
                    $this->logger->error("Exception: " . (strlen($exception->getMessage()) > 40 ? substr($exception->getMessage(), 0, 37) . '...' : $exception->getMessage()));
                } catch (Facebook\WebDriver\Exception\TimeoutException | TimeoutException $exception) {
                    $this->logger->error("Exception: " . (strlen($exception->getMessage()) > 40 ? substr($exception->getMessage(), 0, 37) . '...' : $exception->getMessage()));
                    $this->webDriver->executeScript('window.stop();');
                }
                return;
            }

            $this->logger->info("restoring cookies through selenium, last known URL: {$this->state['URL']}");
            $this->webDriver->get($this->state['URL']);
            $currentHost = parse_url($this->webDriver->getCurrentURL(), PHP_URL_HOST);
            $navigatedToRoot = false;
            foreach ($this->state['Cookies'] as $cookie) {
                if(
                    $cookie['domain'] === $currentHost
                    ||
                    (substr($cookie['domain'], 0, 1) === '.' && preg_match("#(^|\.)" . preg_quote(substr($cookie['domain'], 1), '#') . '$#ims', $currentHost))
                ) {

                    // TypeError: Argument 1 passed to SeleniumDriver::addCookie() must be of the type array, object given
                    if (is_object($cookie)) {
                        /*
                         Facebook\WebDriver\Cookie::__set_state(array(
                           'cookie' =>
                              array (
                                'name' => '',
                                'value' => '',
                                'path' => '/',
                                'domain' => 'www.site.com',
                                'expiry' => 1704614693, // 08:04 07 Jan 2024
                                'secure' => true,
                                'httpOnly' => false,
                                'sameSite' => 'None',
                              ),
                            ))
                         */
                        /** @var \Facebook\WebDriver\Cookie $cookieObj */
                        $cookieObj = $cookie;
                        $cookie = $cookieObj->toArray();
                    }// if (is_object($cookie))

                    try {
                        if (!$this->addCookie($cookie)) {
                            if (!$navigatedToRoot) {
                                $root = parse_url($this->state['URL'], PHP_URL_SCHEME) . '://' . $currentHost;
                                $this->logger->info("try to navigate to root: $root");
                                $navigatedToRoot = true;
                                $this->webDriver->get($root);
                                $this->addCookie($cookie);
                            }
                        }
                    }
                    catch (\Facebook\WebDriver\Exception\InvalidCookieDomainException $exception) {
                        $this->logger->warning("failed to restore cookie for domain {$cookie["domain"]}: " . $exception->getMessage());
                    }
                }
            }
        }
    }

    private function addCookie(array $cookie) : bool
    {
        $result = true;
        try {
            $this->webDriver->manage()->addCookie($cookie);
        }
        catch(\UnableToSetCookieException $exception) {
            $this->logger->notice($exception->getMessage());
            $result = false;
        } catch (WebDriverException | Facebook\WebDriver\Exception\WebDriverException $exception) {
            // debug
            if (strpos($exception->getMessage(), 'Invalid cookie fields') !== false) {
                $this->logger->notice($exception->getMessage());
                $this->logger->info(var_export($cookie, true), ['pre' => true]);
                return true;
            }
            throw $exception;
        }

        return $result;
    }

    /**
     * example:
     *        getCookies(["https://google.com"])
     * @param array $domains
     * @return array
     */
    public function getCookies(array $domains)
    {
        $result = [];
        // we do not want to process same domain twice
        $processedHosts = [];
        foreach ($domains as $domain) {
            if (!in_array($domain, $processedHosts)) {
                $processedHosts[] = $domain;
                $this->webDriver->get($domain . '/missingUrl' . rand());
                // sometimes we will be redirected to another domain, add this domain to processed list
                $processedHosts[] = $this->protoAndHost();
                $result = array_merge($result, $this->webDriver->manage()->getCookies());
            }
        }
        // remove duplicates
        $uniques = [];
        foreach ($result as $cookie) {
            $uniques[$cookie['secure'] . $cookie['domain'] . $cookie['path'] . $cookie['name']] = $cookie;
        }

        $this->logger->info("got " . count($uniques) . " cookies from domains: " . implode(", ",
                array_unique($processedHosts)));
        return array_values($uniques);
    }

    private function protoAndHost()
    {
        $url = $this->webDriver->getCurrentURL();
        $parts = parse_url($url);
        return $parts['scheme'] . '://' . $parts['host'];
    }

    /**
     * @param bool $keepSession
     * @return SeleniumDriver
     */
    public function setKeepSession(bool $keepSession): SeleniumDriver
    {
        $this->keepSession = $keepSession;
        return $this;
    }

    public function getBrowserInfo(): ?array
    {
        if ($this->connection === null) {
            return null;
        }
        return array_intersect_key($this->connection->getContext(),
            [SeleniumStarter::CONTEXT_BROWSER_FAMILY => true, SeleniumStarter::CONTEXT_BROWSER_VERSION => true]);
    }
    
    private function setCookies(array $cookies)
    {
        // in selenium, You may only set cookies for the current domain
        // we will navigate to each host, 404 url, and set cookies
        $hosts = array_unique(array_map(function ($cookie) {
            return (empty($cookie['secure']) ? 'http' : 'https') . '://' . preg_replace('#^\.#ims', '',
                    $cookie['domain']);
        }, $cookies));
        $hosts = array_filter($hosts, function ($host) use ($hosts) {
            return stripos($host, 'https:') === 0 || !in_array(preg_replace('#^http:#ims', 'https:', $host), $hosts);
        });
        $processedHosts = [];
        $this->logger->info("hosts' count: " . count($hosts));
        $this->logger->info("cookies' count: " . count($cookies));
        foreach ($hosts as $host) {
            if ($host === "http://google.com") {
                $host = "http://www.google.com";
            } // we want to restore wildcard .google.com cookies
            $this->logger->info("restore cookies for host: {$host}");

            $this->webDriver->get($host . '/missingUrl' . rand());
            $domain = $this->protoAndHost();
            if (in_array($domain, $processedHosts)) {
                continue;
            }
            $processedHosts[] = $domain;
            $url = $this->webDriver->getCurrentURL();
            $urlParts = parse_url($url);
            foreach ($cookies as $cookie) {
                if (CookieManager::domainMatch($urlParts['host'],
                        $cookie['domain']) && (!$cookie['secure'] || $urlParts['scheme'] === 'https')) {
                    if (!empty($cookie['domain']) && stripos($cookie['domain'], '.www.') === 0) {
                        $cookie['domain'] = trim($cookie['domain'], '.');
                    }
                    try {
                        $this->webDriver->manage()->addCookie($cookie);
                    } catch (WebDriverException | Facebook\WebDriver\Exception\WebDriverException $e) {
                        $this->logger->Log("failed to restore cookie: " . $e->getMessage() . " on " . $url,
                            ", cookie data: " . var_export($cookie, true));
                    }
                }
            }
        }
    }

    /**
     * @param bool $keepCookies
     * @return SeleniumDriver
     */
    public function setKeepCookies(bool $keepCookies): SeleniumDriver
    {
        $this->keepCookies = $keepCookies;
        return $this;
    }

    /**
     * @param bool $keepProfile
     * @return SeleniumDriver
     */
    public function setKeepProfile(bool $keepProfile): SeleniumDriver
    {
        $this->keepProfile = $keepProfile;
        return $this;
    }

    /**
     * @return bool
     */
    public function isNewSession(): bool
    {
        return $this->newSession;
    }

    private function clearConnection() : void
    {
        $this->webDriver = null;
        $this->connection = null;
    }

    public function getLastDownloadedFile() : ?\AwardWallet\Common\Selenium\DownloadedFile
    {
        if ($this->connection === null) {
            return null;
        }

        return $this->downloadManager->getLastDownloadedFile($this->connection);
    }

    public function clearDownloads() : void
    {
        if ($this->connection === null) {
            return;
        }

        $this->downloadManager->clearDownloads($this->connection);
    }

    private function connectToHotSession() : bool
    {
        if (!$this->finderRequest->getHotPoolPrefix() || $this->finderRequest->IsBackground()) {
            return false;
        }

        if ($this->finderRequest->getHotProvider()) {
            $this->connection = $this->hotSessionPoolManager->getConnection(
                $this->finderRequest->getHotPoolPrefix(),
                $this->finderRequest->getHotProvider(),
                $this->finderRequest->getHotAccountKey()
            );
        } else {
            $this->connection = $this->hotPoolManager->getConnection(
                $this->finderRequest->getHotPoolPrefix(),
                $this->finderRequest->getHotPoolSize()
            );
        }

        if ($this->connection !== null) {
            $this->sessionRestored();
        }

        return $this->connection !== null;
    }

    private function sessionRestored()
    {
        $this->webDriver = $this->connection->getWebDriver();
        $this->startOptions = new SeleniumOptions();
        $this->newSession = false;

        if ($this->keepProfile && $this->state['BrowserFamily'] !== SeleniumFinderRequest::BROWSER_FIREFOX) {
            $this->logger->info("disabling keepProfile on non-firefox browser after restoring the session");
            $this->keepProfile = false;
        }

        $this->createBrowserCommunicator();
    }

    private function stopHotPoolConnection() : void
    {
        if ($this->finderRequest->getHotProvider()) {
            $this->stopHotSessionPoolConnection();
            return;
        }
        if ($this->keepSession) {
            $this->hotPoolManager->saveConnection($this->connection, $this->finderRequest->getHotPoolPrefix(), $this->finderRequest->getHotPoolSize());
        } else {
            $this->hotPoolManager->deleteConnection($this->connection, $this->finderRequest->getHotPoolPrefix());
        }
    }

    private function stopHotSessionPoolConnection() : void
    {
        if ($this->keepSession) {
            $this->hotSessionPoolManager->saveConnection($this->connection, $this->finderRequest->getHotPoolPrefix(), $this->finderRequest->getHotProvider(), $this->finderRequest->getHotAccountKey());
        } else {
            $this->hotSessionPoolManager->deleteConnection($this->connection);
        }
    }

    private function createBrowserCommunicator() : void
    {
        $context = $this->connection->getContext();
        if (array_key_exists(\AwardWallet\Common\Selenium\BrowserCommunicator::ATTR_REQUEST_ELEMENT_ID, $context)) {
            $this->logger->info("created browser communicator, browser {$this->connection->getBrowserFamily()}:{$this->connection->getBrowserVersion()}");
            $this->browserCommunicator = new \AwardWallet\Common\Selenium\BrowserCommunicator(
                $this->webDriver,
                $context[\AwardWallet\Common\Selenium\BrowserCommunicator::ATTR_REQUEST_ELEMENT_ID],
                $context[\AwardWallet\Common\Selenium\BrowserCommunicator::ATTR_RESPONSE_ELEMENT_ID],
                $this->connection->getBrowserFamily(), $this->connection->getBrowserVersion(),
                $this->logger);
        }
    }

}
