<?php

namespace AwardWallet\ExtensionWorker;

use PhpAmqpLib\Connection\AbstractConnection;
use Psr\Log\LoggerInterface;

class ClientFactory
{

    private \phpcent\Client $centrifugeClient;
    private LoggerInterface $logger;
    private AbstractConnection $rabbitConnection;
    private RabbitQueue $rabbitQueue;

    public function __construct(
        \phpcent\Client $centrifuge,
        LoggerInterface $logger,
        AbstractConnection $rabbitConnection,
        RabbitQueue $rabbitQueue
    )
    {
        $this->centrifugeClient = $centrifuge;
        $this->logger = $logger;
        $this->rabbitConnection = $rabbitConnection;
        $this->rabbitQueue = $rabbitQueue;
    }

    public function createClient(string $sessionId, FileLogger $fileLogger) : Client
    {
        $communicator = new Communicator($sessionId, $this->centrifugeClient, $this->rabbitConnection, $this->rabbitQueue, $this->logger);

        return new Client($communicator, $this->logger, $fileLogger);
    }

}