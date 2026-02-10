<?php

namespace AwardWallet\WebdriverClient;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NodeFinder
{

    /**
     * @var HttpClientInterface
     */
    private $httpClient;
    /**
     * @var string
     */
    private $endPoint;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(HttpClientInterface $httpClient, string $endPoint, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->endPoint = $endPoint;
        $this->logger = $logger;
    }

    public function getNode(?string $table = null) : ?string
    {
        $url = $this->endPoint . "/node";
        if ($table !== null) {
            $url .= "?table=" . urlencode($table);
        }
        
        $response = $this->httpClient->request("GET", $url);
        if ($response->getStatusCode() !== 200) {
            $this->logger->warning("failed to get new webdriver node: {$response->getStatusCode()}: " . $response->getContent(false));
            return null;
        }

        $decoded = json_decode($response->getContent(), true);
        if (!is_array($decoded) || !array_key_exists('node', $decoded)) {
            $this->logger->warning("no node in response: " . $response->getContent());
            return null;
        }

        if ($decoded["node"] === null) {
            $this->logger->info("no free node found");
            return null;
        }

        return $decoded["node"]["address"];
    }

}