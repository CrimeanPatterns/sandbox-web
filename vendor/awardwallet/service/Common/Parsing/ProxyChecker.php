<?php

namespace AwardWallet\Common\Parsing;


use Monolog\Logger;
use Psr\Log\LoggerInterface;

class ProxyChecker
{
    /** @var \HttpDriverInterface */
    private $curl;
    /** @var \Memcached */
    private $memcached;
    /** @var LoggerInterface */
    private $logger;

    const CHECK_URL = "https://s3.amazonaws.com/awardwallet-public/healthcheck.html";
    const TIME_OFFSET = 60;
    const PROXY_CACHE_PREFIX = "live_proxy_";

    public function __construct(\HttpDriverInterface $driver, \Memcached $memcached, LoggerInterface $logger)
    {
        $this->curl = $driver;
        $this->memcached = $memcached;
        $this->logger = $logger;
    }

    /**
     *
     * @param array $proxies - proxies array in host:port format: ["1.2.3.4:3128", "4.5.6.7:8080"]
     * @param int $timeout - timeout for check connection
     * @return string - first live proxy from list in format: "1.2.3.4:3128"
     * @throws \EngineError
     */

    public function getLiveProxy(array $proxies, int $timeout = 20): string
    {
        if (empty($proxies)) {
            throw new \EngineError("Empty proxies array!");
        }

        foreach ($proxies as $proxy) {
            $cacheInfo = $this->checkProxyInfoInCache($proxy);

            if ($cacheInfo) {

                $currentTime = time();
                $nextCheckTime = $cacheInfo["nextCheckTime"];
                $isUpdateTime = ($nextCheckTime <= $currentTime && $currentTime <= $nextCheckTime + self::TIME_OFFSET);

                if ($isUpdateTime && $this->addUpdateLock($proxy)) {
                    $response = $this->checkProxy($proxy, $timeout);

                    if ($this->storeProxyInfoToCache($proxy, $response->httpCode)) {
                        return $proxy;
                    }

                    continue;
                }

                if ($cacheInfo["live"]) {
                    return $proxy;
                }

                continue;
            }

            $response = $this->checkProxy($proxy, $timeout);

            if ($this->storeProxyInfoToCache($proxy, $response->httpCode)) {
                return $proxy;
            }
        }

        throw new \Exception("Live proxy not found!");
    }

    private function checkProxyInfoInCache(string $proxy): ?array
    {
        $proxyCacheInfo = $this->memcached->get(
            $this->getLiveProxyCacheKey($proxy)
        );

        if (isset($proxyCacheInfo["live"])) {
            return $proxyCacheInfo;
        }

        return null;
    }

    private function checkProxy(string $proxy, int $timeout): \HttpDriverResponse
    {
        $proxyParts = explode(":", $proxy);
        $proxyIp = gethostbyname($proxyParts[0]);
        $proxyPort = $proxyParts[1];

        $request = new \HttpDriverRequest(self::CHECK_URL, 'GET', null, [], $timeout);
        $request->proxyAddress = $proxyIp;
        $request->proxyPort = $proxyPort;

        return $this->curl->request($request);
    }

    private function storeProxyInfoToCache(string $proxy, string $responseCode): bool
    {
        $isLive = in_array($responseCode, [200, 201, 202, 203, 204, 205, 206, 302]);

        $logMessage = ($isLive)
            ? "Live proxy found: {$proxy}"
            : "Bad proxy found: {$proxy}. Response code: {$responseCode}";
        $expirationTime = ($isLive) ? 300 : 600;

        $this->logger->info($logMessage);

        $this->memcached->set(
            $this->getLiveProxyCacheKey($proxy),
            [
                "live" => $isLive,
                "nextCheckTime" => time() + $expirationTime - self::TIME_OFFSET,
            ],
            $expirationTime
        );

        return $isLive;
    }

    private function addUpdateLock(string $proxy): bool
    {
        $updateLock = $this->memcached->add(
            "update_lock_" . $proxy,
            true,
            self::TIME_OFFSET
        );

        $logMessage = $updateLock
            ? "Added update lock for proxy - {$proxy}"
            : "Proxy - {$proxy}, already locked for update";
        $this->logger->info($logMessage);

        return $updateLock;
    }

    private function getLiveProxyCacheKey(string $proxy): string
    {
        return self::PROXY_CACHE_PREFIX . $proxy;
    }
}