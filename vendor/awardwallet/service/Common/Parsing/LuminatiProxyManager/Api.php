<?php
namespace AwardWallet\Common\Parsing\LuminatiProxyManager;

use Psr\Log\LoggerInterface;

class Api
{
    /** @var \HttpDriverInterface */
    private $curl;
    /** @var LoggerInterface */
    private $logger;
    private $timeout = 10;
    private $token;
    private $host;
    private $proxyManagerUrl;

    public function __construct(\HttpDriverInterface $driver, LoggerInterface $logger, string $host)
    {
        $this->curl = $driver;
        $this->host = $host;
        $this->logger = $logger;
        $this->proxyManagerUrl = 'http://' .  $this->host . ':22999/api';
    }

    /**
     * Get Proxy Manager version
     * https://help.brightdata.com/hc/en-us/articles/4420279962513-Get-Proxy-Manager-version
     * @return \stdClass $decodeResponseBody
     * @throws \Exception
     */
    public function getVersion(): \stdClass
    {
        $response = $this->sendRequest(
            $this->proxyManagerUrl . '/version',
            'GET',
            null,
            [],
            $this->timeout
        );

        $this->checkResponse($response, [200]);

        return json_decode($response->body);
    }

    /**
     * Get Proxy Manager last version
     * https://help.brightdata.com/hc/en-us/articles/4420271458449-Get-latest-Proxy-Manager-versions
     * @return \stdClass $decodeResponseBody
     * @throws \Exception
     */
    public function getLastVersion(): \stdClass
    {
        $response = $this->sendRequest(
            $this->proxyManagerUrl . '/last_version',
            'GET',
            null,
            [],
            $this->timeout
        );

        $this->checkResponse($response, [200]);

        return json_decode($response->body);
    }

    /**
     * Get currently running NodeJS version
     * https://help.brightdata.com/hc/en-us/articles/4420280748561-Get-currently-running-NodeJS-version
     * @return \stdClass $decodeResponseBody
     * @throws \Exception
     */
    public function getNodeJSVersion(): \stdClass
    {
        $response = $this->sendRequest(
            $this->proxyManagerUrl . '/node_version',
            'GET',
            null,
            [],
            $this->timeout
        );

        $this->checkResponse($response, [200]);

        return json_decode($response->body);
    }

    /**
     * Get explicit configuration of all or specified proxies
     * https://help.brightdata.com/hc/en-us/articles/4420281586833-Get-explicit-configuration-of-all-or-specified-proxies
     * @param int|null $portNumber
     * @return array[\stdClass] $decodeResponseBody
     * @throws \Exception
     */
    public function getProxiesConfiguration(?int $portNumber = null): array
    {
        $response = $this->sendRequest(
            $this->proxyManagerUrl . '/proxies' . (($portNumber) ? '/' . $portNumber : ''),
            'GET',
            null,
            [],
            $this->timeout
        );

        $this->checkResponse($response, [200]);

        return json_decode($response->body);
    }

    /**
     * Create a new proxy port
     * https://help.brightdata.com/hc/en-us/articles/4420293364369-Create-a-new-proxy-port
     * @param array $params
     * @return \stdClass
     * @throws \Exception
     */
    public function createProxyPort(array $params): \stdClass
    {
        $response = $this->sendRequest(
            $this->proxyManagerUrl . '/proxies',
            'POST',
            $params,
            [
                'Content-Type' => 'application/json',
            ],
            $this->timeout
        );

        $this->checkResponse($response, [200]);

        return json_decode($response->body);
    }

    /**
     * Update a proxy port
     * https://help.brightdata.com/hc/en-us/articles/4420274430993-Update-a-proxy-port
     * @param int $portNumber
     * @param array $params
     * @return bool
     * @throws \Exception
     */
    public function updateProxyPort(int $portNumber, array $params): bool
    {
        $response = $this->sendRequest(
            $this->proxyManagerUrl . "/proxies/{$portNumber}",
            'PUT',
            $params,
            [
                'Content-Type' => 'application/json',
            ],
            $this->timeout
        );

        return $this->checkResponse($response, [200]);
    }

    /**
     * Delete proxy ports
     * https://help.brightdata.com/hc/en-us/articles/4420273697809-Delete-proxy-ports
     * @param array[int] $portNumbers
     * @return bool
     * @throws \Exception
     */
    public function deleteProxyPorts(array $portNumbers): bool
    {
        $response = $this->sendRequest(
            $this->proxyManagerUrl . '/proxies/delete',
            'POST',
            ['ports' => $portNumbers],
            [
                'Content-Type' => 'application/json',
            ],
            $this->timeout
        );

        return $this->checkResponse($response, [204]);
    }

    /**
     * Delete a proxy port
     * https://help.brightdata.com/hc/en-us/articles/4420274008081-Delete-a-proxy-port
     * @param int $portNumber
     * @return bool
     * @throws \Exception
     */
    public function deleteProxyPort(int $portNumber): bool
    {
        $response = $this->sendRequest(
            $this->proxyManagerUrl . "/proxies/{$portNumber}",
            'DELETE',
            [],
            [
                'Content-Type' => 'application/json',
            ],
            $this->timeout
        );

        return $this->checkResponse($response, [204]);
    }

    /**
     * Get effective configuration for all running proxies
     * https://help.brightdata.com/hc/en-us/articles/4420471622417-Get-effective-configuration-for-all-running-proxies
     * @return array[\stdClass] $decodeResponseBody
     * @throws \Exception
     */
    public function getProxiesEffectiveConfiguration(): array
    {
        $response = $this->sendRequest(
            $this->proxyManagerUrl . '/proxies_running',
            'GET',
            null,
            [],
            $this->timeout
        );

        $this->checkResponse($response, [200]);

        return json_decode($response->body);
    }

    /**
     * Get recent stats
     * https://help.brightdata.com/hc/en-us/articles/4420478392721-Get-recent-stats
     * @return \stdClass $decodeResponseBody
     * @throws \Exception
     */
    public function getRecentStats(): \stdClass
    {
        $response = $this->sendRequest(
            $this->proxyManagerUrl . '/recent_stats',
            'GET',
            null,
            [],
            $this->timeout
        );

        $this->checkResponse($response, [200]);

        return json_decode($response->body);
    }

    /**
     * Get proxy port status
     * https://help.brightdata.com/hc/en-us/articles/4420514904209-Get-proxy-port-status
     * @param int $portNumber
     * @return \stdClass $decodeResponseBody
     * @throws \Exception
     */
    public function getProxyStatus(int $portNumber): \stdClass
    {
        $response = $this->sendRequest(
            $this->proxyManagerUrl . "/proxy_status/{$portNumber}",
            'GET',
            null,
            [],
            $this->timeout
        );

        $this->checkResponse($response, [200]);

        return json_decode($response->body);
    }

    /**
     * Ban an IP (single)
     * https://help.brightdata.com/hc/en-us/articles/4420521120529-Ban-an-IP-single-
     * @param int $portNumber
     * @param string $ip
     * @param string|null $domain
     * @param int|null $ms
     * @return bool
     * @throws \Exception
     */
    public function banIp(int $portNumber, string $ip, ?string $domain = null, ?int $ms = null): bool
    {
        $postData['ip'] = $ip;

        if ($domain) {
            $postData['domain'] = $domain;
        }

        if ($ms) {
            $postData['ms'] = $ms;
        }

        $response = $this->sendRequest(
            $this->proxyManagerUrl . "/proxies/{$portNumber}/banip",
            'POST',
            $postData,
            [
                'Content-Type' => 'application/json',
            ],
            $this->timeout
        );

        return $this->checkResponse($response, [204]);
    }

    /**
     * Unban an IP
     * https://help.brightdata.com/hc/en-us/articles/4421073699985-Unban-an-IP
     * @param int $portNumber
     * @param string $ip
     * @param string|null $domain
     * @return bool
     * @throws \Exception
     */
    public function unbanIp(int $portNumber, string $ip, ?string $domain = null): bool
    {
        $postData['ip'] = $ip;

        if ($domain) {
            $postData['domain'] = $domain;
        }

        $response = $this->sendRequest(
            $this->proxyManagerUrl . "/proxies/{$portNumber}/unbanip",
            'POST',
            $postData,
            [
                'Content-Type' => 'application/json',
            ],
            $this->timeout
        );

        return $this->checkResponse($response, [204]);
    }

    /**
     * Ban IPs (array)
     * https://help.brightdata.com/hc/en-us/articles/4421059674897-Ban-IPs-array-
     * @param int $portNumber
     * @param array $ips
     * @param string|null $domain
     * @param int|null $ms
     * @return bool
     * @throws \Exception
     */
    public function banIps(int $portNumber, array $ips, ?string $domain = null, ?int $ms = null): bool
    {
        $postData['ips'] = $ips;

        if ($domain) {
            $postData['domain'] = $domain;
        }

        if ($ms) {
            $postData['ms'] = $ms;
        }

        $response = $this->sendRequest(
            $this->proxyManagerUrl . "/proxies/{$portNumber}/banips",
            'POST',
            $postData,
            [
                'Content-Type' => 'application/json',
            ],
            $this->timeout
        );

        return $this->checkResponse($response, [204]);
    }

    /**
     * Unban IPs
     * https://help.brightdata.com/hc/en-us/articles/4421063771409-Unban-IPs
     * @param int $portNumber
     * @return bool
     * @throws \Exception
     */
    public function unbanIps(int $portNumber): bool
    {
        $response = $this->sendRequest(
            $this->proxyManagerUrl . "/proxies/{$portNumber}/unbanips",
            'POST',
            null,
            [],
            $this->timeout
        );

        return $this->checkResponse($response, [204]);
    }

    /**
     * Ban an IP for all ports
     * https://help.brightdata.com/hc/en-us/articles/4421079220241-Ban-an-IP-for-all-ports
     * @param string $ip
     * @param string|null $domain
     * @param int|null $ms
     * @return bool
     * @throws \Exception
     */
    public function banIpForAllPorts(string $ip, ?string $domain = null, ?int $ms = null): bool
    {
        $postData['ip'] = $ip;

        if ($domain) {
            $postData['domain'] = $domain;
        }

        if ($ms) {
            $postData['ms'] = $ms;
        }

        $response = $this->sendRequest(
            $this->proxyManagerUrl . "/banip",
            'POST',
            $postData,
            [
                'Content-Type' => 'application/json',
            ],
            $this->timeout
        );

        return $this->checkResponse($response, [204]);
    }

    /**
     * Refresh Proxy Manager port sessions
     * https://help.brightdata.com/hc/en-us/articles/4421086476817-Refresh-Proxy-Manager-port-sessions
     * @param int $portNumber
     * @return \stdClass $decodeResponseBody
     * @throws \Exception
     */
    public function refreshPortSessions(int $portNumber): \stdClass
    {
        $response = $this->sendRequest(
            $this->proxyManagerUrl . "/refresh_sessions/{$portNumber}",
            'GET',
            null,
            [],
            $this->timeout
        );

        $this->checkResponse($response, [200]);

        return json_decode($response->body);
    }

    /**
     * Get general settings
     * https://help.brightdata.com/hc/en-us/articles/4421086476817-Refresh-Proxy-Manager-port-sessions
     * @return \stdClass $decodeResponseBody
     * @throws \Exception
     */
    public function getGeneralSettings(): \stdClass
    {
        $response = $this->sendRequest(
            $this->proxyManagerUrl . "/settings",
            'GET',
            null,
            [],
            $this->timeout
        );

        $this->checkResponse($response, [200]);

        return json_decode($response->body);
    }

    /**
     * Update general settings
     * https://help.brightdata.com/hc/en-us/articles/4421172062225-Update-general-settings
     * @param string $zone
     * @param array $uiWhitelistIps
     * @param array $whitelistIps
     * @param int $logs
     * @param bool $requestStats
     * @return \stdClass $decodeResponseBody
     * @throws \Exception
     */
    public function updateGeneralSettings(string $zone, array $uiWhitelistIps, array $whitelistIps, int $logs, bool $requestStats): \stdClass
    {
        $response = $this->sendRequest(
            $this->proxyManagerUrl . "/settings",
            'PUT',
            [
                'zone' => $zone,
                'www_whitelist_ips' => $uiWhitelistIps,
                'whitelist_ips' => $whitelistIps,
                'logs' => $logs,
                'request_stats' => $requestStats,
            ],
            [],
            $this->timeout
        );

        $this->checkResponse($response, [200]);

        return json_decode($response->body);
    }

    /**
     * Update general settings
     * https://help.brightdata.com/hc/en-us/articles/4421172062225-Update-general-settings
     * @return \stdClass $decodeResponseBody
     * @throws \Exception
     */
    public function getEnabledZonesConfiguration(): \stdClass
    {
        $response = $this->sendRequest(
            $this->proxyManagerUrl . "/zones",
            'GET',
            null,
            [],
            $this->timeout
        );

        $this->checkResponse($response, [200]);

        return json_decode($response->body);
    }

    /**
     * Get allocated IPs in zone
     * https://help.brightdata.com/hc/en-us/articles/4421173520657-Get-allocated-IPs-in-zone
     * @param string $zone
     * @return \stdClass $decodeResponseBody
     * @throws \Exception
     */
    public function getAllocatedIPsInZone(string $zone): \stdClass
    {
        $response = $this->sendRequest(
            $this->proxyManagerUrl . "/allocated_ips?zone={$zone}",
            'GET',
            null,
            [],
            $this->timeout
        );

        $this->checkResponse($response, [200]);

        return json_decode($response->body);
    }

    /**
     * Get allocated gIPs in zone
     * https://help.brightdata.com/hc/en-us/articles/4421173798033-Get-allocated-gIPs-in-zone
     * @param string $zone
     * @return \stdClass $decodeResponseBody
     * @throws \Exception
     */
    public function getAllocatedGIPsInZone(string $zone): \stdClass
    {
        $response = $this->sendRequest(
            $this->proxyManagerUrl . "/allocated_vips?zone={$zone}",
            'GET',
            null,
            [],
            $this->timeout
        );

        $this->checkResponse($response, [200]);

        return json_decode($response->body);
    }

    /**
     * Refresh IPs or gIPs in zone
     * https://help.brightdata.com/hc/en-us/articles/4421199768721-Refresh-IPs-or-gIPs-in-zone
     * @param string $zone
     * @param bool $gIp
     * @param array $ips
     * @return bool
     * @throws \Exception
     */
    public function refreshIpInZone(string $zone, bool $gIp, array $ips): bool
    {
        $postData = ($gIp) ? ['vips' => $ips] : ['ips' => $ips];
        $postData['zone'] = $zone;

        $response = $this->sendRequest(
            $this->proxyManagerUrl . "/refresh_ips",
            'POST',
            $postData,
            [
                'Content-Type' => 'application/json',
            ],
            $this->timeout
        );

        return $this->checkResponse($response, [204]);
    }

    /**
     * Enable SSL analyzing on all proxy ports
     * https://help.brightdata.com/hc/en-us/articles/4421199768721-Refresh-IPs-or-gIPs-in-zone
     * @return bool
     * @throws \Exception
     */
    public function enableSSLAnalyzing(): bool
    {
        $response = $this->sendRequest(
            $this->proxyManagerUrl . "/enable_ssl",
            'POST',
            [],
            [
                'Content-Type' => 'application/json',
            ],
            $this->timeout
        );

        return $this->checkResponse($response, [200]);
    }

    /**
     * Shutdown Proxy Manager
     * https://help.brightdata.com/hc/en-us/articles/4423431959697-Shutdown-Proxy-Manager
     * @return bool
     * @throws \Exception
     */
    public function shutdownProxyManager(): bool
    {
        $response = $this->sendRequest(
            $this->proxyManagerUrl . "/shutdown",
            'POST',
            [],
            [
                'Content-Type' => 'application/json',
            ],
            $this->timeout
        );

        return $this->checkResponse($response, [200]);
    }

    /**
     * Restart Proxy Manager
     * https://help.brightdata.com/hc/en-us/articles/4423438962065-Restart-Proxy-Manager
     * @return bool
     * @throws \Exception
     */
    public function restartProxyManager(): bool
    {
        $response = $this->sendRequest(
            $this->proxyManagerUrl . "/restart",
            'POST',
            [],
            [
                'Content-Type' => 'application/json',
            ],
            $this->timeout
        );

        return $this->checkResponse($response, [200]);
    }

    /**
     * Upgrade Proxy Manager
     * https://help.brightdata.com/hc/en-us/articles/4423441708305-Upgrade-Proxy-Manager
     * @return bool
     * @throws \Exception
     */
    public function upgradeProxyManager(): bool
    {
        $response = $this->sendRequest(
            $this->proxyManagerUrl . "/upgrade",
            'POST',
            [],
            [
                'Content-Type' => 'application/json',
            ],
            $this->timeout
        );

        return $this->checkResponse($response, [200]);
    }

    /**
     * Get all users
     * https://help.brightdata.com/hc/en-us/articles/4423474753681-Get-all-users
     * @return array[\stdClass] $decodeResponseBody
     * @throws \Exception
     */
    public function getAllUsers(): array
    {
        $response = $this->sendRequest(
            $this->proxyManagerUrl . "/lpm_users",
            'GET',
            null,
            [
                'Content-Type' => 'application/json',
            ],
            $this->timeout
        );

        $this->checkResponse($response, [200]);

        return json_decode($response->body);
    }

    /**
     * Get tail from the log file
     * https://help.brightdata.com/hc/en-us/articles/4426998219665-Get-tail-from-the-log-file
     * @param int $limit
     * @return null|array[\stdClass] $decodeResponseBody
     * @throws \Exception
     */
    public function getTailLogFile(int $limit): ?array
    {
        $response = $this->sendRequest(
            $this->proxyManagerUrl . "/general_logs?limit={$limit}",
            'GET',
            null,
            [
                'Content-Type' => 'application/json',
            ],
            $this->timeout
        );

        $this->checkResponse($response, [200]);

        return json_decode($response->body);
    }

    /**
     * Get HAR logs
     * https://help.brightdata.com/hc/en-us/articles/4426998219665-Get-tail-from-the-log-file
     * @param array $params
     * @return \stdClass $decodeResponseBody
     * @throws \Exception
     */
    public function getHarLogs(array $params = []): \stdClass
    {
        $query = (!empty($params)) ? '?' . http_build_query($params) : '';
        $response = $this->sendRequest(
            $this->proxyManagerUrl . "/logs" . $query,
            'GET',
            null,
            [
                'Content-Type' => 'application/json',
            ],
            $this->timeout
        );

        $this->checkResponse($response, [200]);

        return json_decode($response->body);
    }

    /**
     * Get banned IPs
     * https://help.brightdata.com/hc/en-us/articles/4427097782033-Get-banned-IPs
     * @param int $portNumber
     * @param bool $full
     * @return \stdClass $decodeResponseBody
     * @throws \Exception
     */
    public function getBannedIPs(int $portNumber, bool $full = true): \stdClass
    {
        $response = $this->sendRequest(
            $this->proxyManagerUrl . "/banlist/{$portNumber}?full={$full}",
            'GET',
            null,
            [
                'Content-Type' => 'application/json',
            ],
            $this->timeout
        );

        $this->checkResponse($response, [200]);

        return json_decode($response->body);
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function setRequestTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    protected function sendRequest(string $url,string $method, ?array $postData, array $headers = [], int $timeout = null): \HttpDriverResponse
    {
        $request = new \HttpDriverRequest(
            $url, $method, json_encode($postData), $headers, $timeout
        );

        return $this->curl->request($request);
    }

    protected function checkResponse(\HttpDriverResponse $response, array $codes): bool
    {
        if (!in_array($response->httpCode, $codes)) {
            $this->logger->error(
                'Wrong response code, check problem! ' . var_export([
                    'httpCode' => $response->httpCode,
                    'errorCode' => $response->errorCode,
                    'errorMessage' => $response->errorMessage,
                    'body' => $response->body,
                ], true)
                , [
                    'pre' => true
                ]
            );

            throw new \Exception(
                'Wrong response code, check problem!'
            );
        }

        return true;
    }
}