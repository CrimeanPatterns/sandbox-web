<?php


class SeleniumConsulFinder implements SeleniumFinderInterface
{

    /** @var string */
    private $consulHost;
    /** @var \Psr\Log\LoggerInterface */
    private $logger;
    /** @var HttpDriverInterface */
    private $driver;

    /**
     * SeleniumConsulFinder constructor.
     * @param string $consulHost
     * @param string $dir
     * @param string $share
     * @param \Psr\Log\LoggerInterface $logger
     * @param HttpDriverInterface $driver
     */
    public function __construct(
        $consulHost,
        \Psr\Log\LoggerInterface $logger,
        \HttpDriverInterface $driver
    )
    {
        $this->consulHost = $consulHost;
        $this->logger = $logger;
        $this->driver = $driver;
    }

    /**
     * @return SeleniumServer[]
     */
    public function getServers(SeleniumFinderRequest $request) : array
    {
        if ($request->getBrowser() === SeleniumFinderRequest::BROWSER_FIREFOX && in_array($request->getVersion(), [45, 53]))
            $this->logger->notice("Please use latest version of Firefox");

        $version = $request->getBrowser() . '-' . $request->getVersion();
        $servers = $this->getServersFromMonitor($version, $request->getOs());
        if(empty($servers))
            $this->logger->notice("There are no available selenium servers: " . $request->getBrowserName());
        return $servers;
    }

    private function getServersFromMonitor(string $version, ?string $os)
    {
        $host = $this->consulHost;
        $this->logger->debug("searching selenium-monitor on consul at " . $host);
        $response = $this->driver->request(new HttpDriverRequest('http://' . $host . ':8500/v1/health/service/selenium-monitor?tag=' . urlencode($version), "GET", null, [], 5))->body;
        $servers = json_decode($response, true);

        if(!is_array($servers)) {
            $servers = [];
        }

        $this->logger->debug("got " . count($servers) . " unfiltered servers");
        $unfiltered = $servers;

        $serverInfo = $this->getKVTree(["/selenium-monitor/"]);

        if(isset($serverInfo['selenium-monitor']))
            $serverInfo = $serverInfo['selenium-monitor'];
        else
            $serverInfo = [];

        $servers = array_filter($servers, function (array $server) use($serverInfo, $version, $os) {
            $serverInfoKey = $this->getServerInfoKey($server);
            $curServerInfo = $serverInfo[$serverInfoKey] ?? null;

            if ($curServerInfo === null) {
//                $this->logger->info("no server info for $serverInfoKey");
                return false;
            }
            
            if(
                !isset(
                    $curServerInfo[$version . '-port'],
                    $curServerInfo['sessionsTime'],
                    $curServerInfo['webdriverTime'],
                    $curServerInfo['sessionsCount'],
                    $curServerInfo['healthy']
                )
                || (int)$curServerInfo['sessionsTime'] > 100
                || (int)$curServerInfo['webdriverTime'] > 1000
                || (int)$curServerInfo['sessionsCount'] > 20
                || $curServerInfo['healthy'] !== '1'
                || ($os !== null && (($curServerInfo['os'] ?? null) !== $os))
            ) {
//                $this->logger->info("bad server info for $serverInfoKey: " . json_encode($curServerInfo) . ", os: $os");
                return false;
            }

            $healthy = true;
            foreach ($server['Checks'] as $check) {
                if ($check['Status'] !== 'passing') {
//                    $this->logger->info("failed healthcheck for $serverInfoKey: " . json_encode($check));
                    $healthy = false;
                    break;
                }
            }
            return $healthy;
        });
        $this->logger->debug("got " . count($servers) . " healthy servers");

        $servers = array_map(function(array $server) use($serverInfo) {
            $server['load'] = $this->serverLoad($serverInfo[$this->getServerInfoKey($server)]);
            return $server;
        }, $servers);

        shuffle($servers);

        usort($servers, function(array $a, array $b) {
            return $a['load'] <=> $b['load'];
        });

        $servers = array_map(function(array $server) use($serverInfo, $version){
            return new SeleniumServer($server['Service']['Address'], $serverInfo[$this->getServerInfoKey($server)][$version . "-port"]);
        }, $servers);

        if (count($servers) === 0) {
            $this->logger->info("no selenium servers found: " . $version . ", unfiltered: " . count($unfiltered) . ", serverInfo: " . count($serverInfo));
        }

        return $servers;
    }

    private function getServerInfoKey(array $server) : string
    {
        $serverKey = str_replace('selenium-monitor:', '', $server['Service']['ID']);

        return $serverKey;
    }

    private function serverLoad(array $serverInfo)
    {
        return round((min((float)$serverInfo["sessionsCount"], 20) / 20 * 0.2 + (100 - (float)$serverInfo["idle"]) / 100) * 100);
    }

    public function getKVTree(array $prefixes) : array
    {
        $requests = [];
        foreach ($prefixes as $key)
            $requests[] = [
                "KV" => [
                    "Verb" => "get-tree",
                    "Key" => $key,
                ]
            ];
        $response = @json_decode($this->driver->request(new HttpDriverRequest('http://' . $this->consulHost . ':8500/v1/txn', 'PUT', json_encode($requests), [], 5))->body, true);
        if($response === false || !is_array($response) || !isset($response['Results'])) {
            $this->logger->warning("failed to get kv tree");
            return [];
        }
        $result = [];
        foreach ($response['Results'] as $item) {
            $this->setArrayValueByPath(explode("/", substr($item['KV']['Key'], 1)), base64_decode($item['KV']['Value']), $result);
        }
        return $result;
    }

    private function setArrayValueByPath(array $path, $value, array &$array)
    {
        $key = array_shift($path);
        if(empty($path))
            $array[$key] = $value;
        else {
            if (!isset($array[$key]))
                $array[$key] = [];
            $this->setArrayValueByPath($path, $value, $array[$key]);
        }
    }

}
