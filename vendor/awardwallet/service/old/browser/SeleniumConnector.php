<?php


use OldSound\RabbitMqBundle\RabbitMq\RpcClient;

class SeleniumConnector
{

    const MEMCACHED_STARTING_SESSION_KEY = 'selenium_start_%s_%d';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    /**
     * @var SeleniumFinderInterface
     */
    private $finder;
    /**
     * @var HttpDriverInterface
     */
    private $httpDriver;
    /**
     * @var Memcached
     */
    private $memcached;
    /**
     * @var SeleniumStarter
     */
    private $seleniumStarter;
    /**
     * @var int
     */
    private $pauseBetweenNewSessions = 2;
    /**
     * @var Callable
     */
    private $onWebDriverCreated;
    /**
     * @var string
     */
    private $myName;
    /**
     * @var \AwardWallet\WebdriverClient\NodeFinder 
     */
    private $nodeFinder;
    /**
     * @var \AwardWallet\WebdriverClient\NodeFinder
     */
    private $macNodeFinder;
    private RpcClient $macRpcClient;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        SeleniumFinderInterface $finder,
        HttpDriverInterface $httpDriver,
        \Memcached $memcached,
        SeleniumStarter $seleniumStarter,
        \AwardWallet\WebdriverClient\NodeFinder $nodeFinder,
        \AwardWallet\WebdriverClient\NodeFinder $macNodeFinder,
        RpcClient $macRpcClient
    ) {
        $this->logger = $logger;
        $this->finder = $finder;
        $this->httpDriver = $httpDriver;
        $this->memcached = $memcached;
        $this->seleniumStarter = $seleniumStarter;
        $this->myName = gethostname() . "_" . getmypid();
        $this->nodeFinder = $nodeFinder;
        $this->macNodeFinder = $macNodeFinder;
        $this->macRpcClient = $macRpcClient;
    }

    /**
     * for debug proxy
     * @internal
     */
    public function setNodeFinder(\AwardWallet\WebdriverClient\NodeFinder $nodeFinder) : void
    {
        $this->nodeFinder = $nodeFinder;
        $this->macNodeFinder = $nodeFinder;
    }

    /**
     * @return RemoteWebDriver
     */
    public function restoreSession(SeleniumSession $session)
    {
        $url = 'http://' . $session->getHost() . ':' . $session->getPort() . $session->getPath();
        $this->logger->info("trying to restore session " . $session->getSessionId() . " on " . $url . ', share: ' . $session->getShare());
        $response = $this->httpDriver->request(
            new HttpDriverRequest($url . '/session/' . $session->getSessionId() . '/url', 'GET', null, [], 20)
        );

        if(stripos($response->body, 'SessionNotFoundException') !== false || $response->httpCode === 404) {
            $this->logger->notice("session not found");
            return null;
        }

        $info = json_decode($response->body, true);

        if (isset($info['value']) && is_string($info["value"]) && !isset($info["error"])) {
            $this->logger->info("session restored, url: " . $info["value"]);

            if (isset($session->getContext()[SeleniumStarter::CONTEXT_NEW_WEBDRIVER])) {
                $result = new \AwardWallet\Common\Selenium\OldWebDriverTranslator(
                    \AwardWallet\Common\Selenium\DebugWebDriver::createBySessionID($session->getSessionId(), $url)
                );
            } else {
                $result = SeleniumDebugWebDriver::createBySessionID($session->getSessionId(), $url);
            }

            $request = new \SeleniumFinderRequest(
                $session->getContext()[\SeleniumStarter::CONTEXT_BROWSER_FAMILY] ?? \SeleniumFinderRequest::BROWSER_CHROME,
                $session->getContext()[\SeleniumStarter::CONTEXT_BROWSER_VERSION] ?? \SeleniumFinderRequest::CHROME_DEFAULT
            );
            $result->setServerInfo(new \AwardWallet\Common\Selenium\ServerInfo($request));

            if($this->onWebDriverCreated !== null) {
                call_user_func($this->onWebDriverCreated, $result);
            }

            return $result;
        }

        $this->logger->warning("failed to contact session {$session->getSessionId()} at {$url}, response ({$response->httpCode}): " . $response->body . ", errorCode: " . $response->errorCode . ", errorMessage: " . $response->errorMessage);
        return null;
    }

    public function createSession(SeleniumFinderRequest $request, SeleniumOptions $options): SeleniumConnection
    {
        $this->logger->info("searching selenium server: " . $request->getBrowserName());

        // mac
        if (
            ((int)$request->getVersion() === 94
                && $request->getBrowser() === SeleniumFinderRequest::BROWSER_CHROME)
            || $request->getOs() === \SeleniumFinderRequest::OS_MAC
        )
        {
            return $this->createMacSessionFromRabbit($request, $options) ?? $this->createSessionInWebDriverCluster($request, $options, "mac");
        }

        if ($request->getVersion() >= 100) {
            return $this->createSessionInWebDriverCluster($request, $options);
        }

        $startTime = microtime(true);
        $serversRefreshTime = $startTime - 1000;
        $try = 0;
        $lockErrors = 0;
        $lockAttempts = 0;
        $lockName = null;
        $createAttempts = 0;
        $serverNames = [];

        while ((microtime(true) - $startTime) < 5) {
            if ($try > 0) {
                usleep(random_int(300000, 2000000));
            }

            if ((microtime(true) - $serversRefreshTime) > 1) {
                $servers = $this->finder->getServers($request);
                $serverNames = array_unique(array_merge($serverNames, array_map(function(SeleniumServer $server){ return $server->host . ':' . $server->port; }, $servers)));
                $this->logger->debug("got selenium servers from finder", ["count" => count($servers)]);
                $serversRefreshTime = microtime(true);
            }

            if ($request->getServerHost() !== null) {
                $this->logger->info("filtering server list by address: " . $request->getServerHost());
                $servers = array_filter($servers, function(SeleniumServer $server) use ($request) { return $server->host === $request->getServerHost(); });
            }

            foreach ($servers as $server) {

                if ($this->pauseBetweenNewSessions > 0) {
                    $lockAttempts++;
                    $serverName = $server->host;

                    if ($server->port >= 20000) { // macs, multiple servers on single server through vpn
                        $serverName .= ":{$server->port}";
                    }

                    $lockName = $this->getServerLock($serverName, 180);

                    if ($lockName === null)
                    {
                        $lockErrors++;
                        continue;
                    }
                }

                try {
                    $createAttempts++;
                    $context = array_merge([
                        "browser" => $request->getBrowserName(),
                        "selenium_host" => $server->host,
                        "pacFile" => $options->pacFile,
                        "proxy" => $options->proxyHost,
                        "proxy_user" => $options->proxyUser,
                        "port" => $server->port,
                        "startupText" => $options->startupText,
                        "lockName" => $lockName,
                    ], $options->loggingContext);
                    $this->logger->info('creating new selenium session', $context);
                    $session = $this->seleniumStarter->createSession($server, $request, $options, $this->onWebDriverCreated);
                    $context['sessionId'] = $session->getSessionId();
                    $this->logger->info('created new selenium session', $context);
                }
                finally {
                    if ($this->pauseBetweenNewSessions > 0) {
                        $this->logger->info("releasing selenium server lock", $context);
                        $this->releaseServerLock($lockName);
                    }
                }

                return $session;
            }

            $try++;
        }

        throw new ThrottledException(random_int(5, 20), 120, null, "all selenium servers are busy, tries: $try, servers: " . count($serverNames) . ": " . implode(", ", $serverNames) . ", lock attempts: $lockAttempts, lock errors: $lockErrors, create attempts: $createAttempts, browser: {$request->getBrowserName()}", false);
    }

    private function createSessionInWebDriverCluster(SeleniumFinderRequest $request, SeleniumOptions $options, ?string $table = null, bool $throwIfNotFound = true): ?SeleniumConnection
    {
        $startTime = microtime(true);
        $try = 0;

        $nodeFinder = $this->nodeFinder;
        if ($table === 'mac') {
            $nodeFinder = $this->macNodeFinder;
        }

        while ((microtime(true) - $startTime) < 5) {
            if ($try > 0) {
                usleep(random_int(300000, 2000000));
            }

            try {
                $node = $nodeFinder->getNode($table);
            } catch (\Symfony\Component\HttpClient\Exception\TransportException $e) {
                $node = null;
                $this->logger->notice($e->getMessage());
            }
            if ($node !== null) {
                $hostAndPort = explode(":", $node);
                $host = $hostAndPort[0];
                $port = $hostAndPort[1] ?? 4444;
                $context = [
                    "browser" => $request->getBrowserName(),
                    "selenium_host" => $host,
                    "pacFile" => $options->pacFile,
                    "proxy" => $options->proxyHost,
                    "proxy_user" => $options->proxyUser,
                    "port" => $port,
                    "startupText" => $options->startupText,
                ];
                $this->logger->info('creating new selenium session', $context);

                try {
                    $session = $this->seleniumStarter->createSession(new SeleniumServer($host, $port), $request, $options, $this->onWebDriverCreated);
                } catch (
                    WebDriverCurlException
                    | Facebook\WebDriver\Exception\WebDriverCurlException
                    $e
                ) {
                    $session = null;
                }

                if ($session) {
                    $context['sessionId'] = $session->getSessionId();
                    $this->logger->info('created new selenium session', $context);

                    return $session;
                }
            }

            $try++;
        }

        if ($throwIfNotFound) {
            throw new ThrottledException(random_int(5, 20), 120, null, "all selenium servers are busy, browser: {$request->getBrowserName()}, table: {$table}", false);
        }

        return null;
    }
    
    private function createMacSessionFromRabbit(SeleniumFinderRequest $request, SeleniumOptions $options) : ?SeleniumConnection
    {
        if ($this->memcached->get('test_mac_sessions_from_rabbit') !== '1') {
            return null;
        }

        try {
            $this->logger->info('creating mac session with from rabbit');
            $requestId = bin2hex(random_bytes(4));
            $this->macRpcClient->addRequest(json_encode([
                'loggingContext' => $options->loggingContext,
            ]), "mac_session_requests", $requestId, '', 1);
            $this->logger->info('awaiting replies');
            $replies = $this->macRpcClient->getReplies();
            $this->logger->info('got reply: ' . json_encode($replies));

            if (is_array($replies)) {
                $reply = array_shift($replies);
                if (!empty($reply) && ($reply->host)) {
                    $context = [
                        "browser" => $request->getBrowserName(),
                        "selenium_host" => $reply->host,
                        "pacFile" => $options->pacFile,
                        "proxy" => $options->proxyHost,
                        "proxy_user" => $options->proxyUser,
                        "port" => $reply->port,
                        "startupText" => $options->startupText,
                    ];
                    $this->logger->info('creating new selenium session', $context);

                    try {
                        $session = $this->seleniumStarter->createSession(new SeleniumServer($reply->host, $reply->port), $request, $options, $this->onWebDriverCreated);
                    } catch (
                    WebDriverCurlException
                    | Facebook\WebDriver\Exception\WebDriverCurlException
                    $e
                    ) {
                        return null;
                    }

                    if ($session) {
                        $this->logger->info('created new selenium session', $context);

                        return $session;
                    }
                }
            }
        }
        catch (\Exception $e) {
            $this->logger->warning('exception while requesting mac session ' . get_class($e) . ': ' . $e->getMessage());
        } finally {
            $this->macRpcClient->reset();
        }

        return null;
    }

    public function closeConnection(SeleniumConnection $connection)
    {
        $this->logger->info('close connection');

        try {
            $connection->getWebDriver()->quit();
        } catch (WebDriverCurlException | Facebook\WebDriver\Exception\WebDriverCurlException $e) {
            $this->logger->notice("curl error quitting web driver: " . $e->getMessage(), ["sessionId" => $connection->getWebDriver()->getSessionID()]);
        } catch (WebDriverException | Facebook\WebDriver\Exception\WebDriverException $e) {
            $this->logger->notice("error quitting web driver: " . $e->getMessage(), ["sessionId" => $connection->getWebDriver()->getSessionID()]);
        }

        // 3600 here for closing old sessions, when there were no StartTime in state
        // you could safely remove it after deploy
        $this->logger->info("closed selenium session", ["sessionId" => $connection->getWebDriver()->getSessionID(), "duration" => time() - ($connection->getStartTime() ?? 3600), "browser" => $connection->getBrowserFamily() . ':' . $connection->getBrowserVersion()]);
    }

    private function getServerLock(string $host, int $ttl) : ?string
    {
        for ($n = 0; $n < 4; $n++) {
            $memcacheKey = sprintf(self::MEMCACHED_STARTING_SESSION_KEY, $host, $n);

            if ($this->memcached->add($memcacheKey, $this->myName, $ttl)) {
                return $memcacheKey;
            }
        }

        $this->logger->info("selenium session start was too near for {$host}, {$memcacheKey}: " . $this->memcached->getResultMessage());

        return null;
    }

    private function releaseServerLock(string $lockName)
    {
        $this->memcached->delete($lockName);
//        $info = $this->memcached->get($lockName, null, \Memcached::GET_EXTENDED);
//        if (!empty($info) && $info['value'] == $this->myName) {
//            $this->memcached->cas($info['cas'], $lockName, $this->myName . "_deleted", time() - 3600);
//        }
    }

    /**
     * @return int
     */
    public function getPauseBetweenNewSessions(): int
    {
        return $this->pauseBetweenNewSessions;
    }

    /**
     * @param int $pauseBetweenNewSessions
     * @return SeleniumConnector
     */
    public function setPauseBetweenNewSessions(int $pauseBetweenNewSessions): SeleniumConnector
    {
        $this->pauseBetweenNewSessions = $pauseBetweenNewSessions;
        return $this;
    }

    /**
     * @internal
     * callback signature: function(RemoteWebDriver $webDriver)
     * @param Callable $onWebDriverCreated
     * @return SeleniumConnector
     */
    public function setOnWebDriverCreated(Callable $onWebDriverCreated): SeleniumConnector
    {
        $this->onWebDriverCreated = $onWebDriverCreated;
        return $this;
    }

}
