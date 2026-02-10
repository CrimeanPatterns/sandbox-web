<?php


namespace AwardWallet\Common\Parsing;


use Psr\Log\LoggerInterface;

class WrappedProxyClient
{
    /** @var \HttpDriverInterface */
    private $curl;
    /** @var LoggerInterface */
    private $logger;
    private $internalHost;
    private $externalHost;

    public function __construct(\HttpDriverInterface $driver, LoggerInterface $logger, string $internalHost, string $externalHost)
    {
        $this->curl = $driver;
        $this->logger = $logger;
        $this->internalHost = $internalHost;
        $this->externalHost = $externalHost;
    }

    /**
     * Create wrapped proxy port
     * @param array $proxyParams - ['proxyAddress', 'proxyHost', 'proxyPort', 'proxyType', 'proxyLogin', 'proxyPassword']
     * @param int|null $ttl - time to live wrapped proxy in seconds
     * @return array - ['proxyAddress', 'proxyHost', 'proxyPort', 'proxyType', 'proxyLogin', 'proxyPassword']
     * @throws \Exception
     */
    public function createPort(array $proxyParams, int $ttl = null): array
    {
        if (empty($proxyParams['proxyHost'])) {
            $this->logger->error('Proxy host can\'t be empty. (Wrapped proxy)');
            throw new \Exception('Proxy host can\'t be empty.');
        }

        $this->logger->notice('Check parent proxy before create wrapped!');
        $response = $this->sendRequest(
            'https://ipinfo.io/',
            'GET',
            null,
            [],
            $proxyParams
        );

        if ($response->httpCode != 200) {
            $this->logger->notice(var_export([
                'httpCode' => $response->httpCode,
                'errorCode' => $response->errorCode,
                'errorMessage' => $response->errorMessage,
                'body' => substr($response->body, 0, 250),
            ], true));

            throw new \Exception(
                'Parent proxy not available!'
            );
        }

        $upstreamProxyUrl = '';

        if (!empty($proxyParams['proxyLogin']) && !empty($proxyParams['proxyPassword'])) {
            $upstreamProxyUrl .= $proxyParams['proxyLogin'] . ':'
                . $proxyParams['proxyPassword'] . '@';
        }
        $upstreamProxyUrl .= $proxyParams['proxyHost']
            . ':' . $proxyParams['proxyPort'];

        $response = $this->sendRequest(
            $this->internalHost . '/port/create',
            'POST',
            [
                'upstreamProxyUrl' => $upstreamProxyUrl,
                'ttl' => $ttl
            ],
            [
                'Content-Type' => 'application/json'
            ]
        );

        if ($response->httpCode != 201) {
            $this->logger->error(
                'Wrong response code, check problem! ' . var_export([
                    'httpCode' => $response->httpCode,
                    'errorCode' => $response->errorCode,
                    'errorMessage' => $response->errorMessage,
                    'body' => substr($response->body, 0, 250),
                ], true)
                , [
                    'pre' => true
                ]
            );

            throw new \Exception(
                'Wrong response code, check problem!'
            );
        }

        $credentials = json_decode($response->body);

        return  [
            'proxyAddress' => gethostbyname($this->externalHost),
            'proxyHost' => $this->externalHost,
            'proxyPort' => $credentials->port,
            'proxyType' => 'http',
            'proxyLogin' => $credentials->username,
            'proxyPassword' => $credentials->password,
        ];
    }

    /**
     * Delete wrapped proxy port
     * @param string $proxyLogin - proxyLogin from method createPort()
     * @return void
     * @throws \EngineError
     */
    public function deletePort(string $proxyLogin): void
    {
        $response = $this->sendRequest(
            $this->internalHost . '/port/delete',
            'DELETE',
            [
                'username' => $proxyLogin,
            ],
            [
                'Content-Type' => 'application/json'
            ]
        );

        if ($response->httpCode != 204) {
            $this->logger->error(
                'Wrong response code, check problem! ' . var_export([
                    'httpCode' => $response->httpCode,
                    'errorCode' => $response->errorCode,
                    'errorMessage' => $response->errorMessage,
                    'body' => substr($response->body, 0, 250),
                ], true)
                , [
                    'pre' => true
                ]
            );

            throw new \Exception(
                'Wrong response code, check problem!'
            );
        }
    }

    private function sendRequest(string $url,string $method, ?array $postData, array $headers = [], array $proxy = [], int $timeout = null): \HttpDriverResponse
    {
        $request = new \HttpDriverRequest(
            $url, $method, json_encode($postData), $headers, $timeout
        );

        if (!empty($proxy)) {
            $request->proxyAddress = $proxy['proxyAddress'];
            $request->proxyPort = $proxy['proxyPort'];
            $request->proxyLogin = $proxy['proxyLogin'];
            $request->proxyPassword = $proxy['proxyPassword'];
        }

        return $this->curl->request($request);
    }

}