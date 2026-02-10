<?php
namespace AwardWallet\Common\Parsing\LuminatiProxyManager;

use Psr\Log\LoggerInterface;

class Client
{
    /** @var Api */
    private $api;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(Api $api, LoggerInterface $logger)
    {
        $this->api = $api;
        $this->logger = $logger;
    }

    /**
     * Method for create port at LPM server
     * @param Port $port
     * @return int $portNumber
     */
    public function createProxyPort(Port $port): int
    {
        $result = $this->api->createProxyPort(
            $port->getData()
        );

        return $result->data->port;
    }

    /**
     * Method for delete transmitted port at LPM server
     * @param int $portNumber
     * @return bool
     */
    public function deleteProxyPort(int $portNumber): bool
    {
        return $this->api->deleteProxyPort($portNumber);
    }

    /**
     * Return lpm address
     * @return string
     */
    public function getInternalIp(): string
    {
        return $this->api->getHost();
    }
}