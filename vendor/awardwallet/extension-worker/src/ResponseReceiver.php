<?php

namespace AwardWallet\ExtensionWorker;

use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

class ResponseReceiver
{

    private AbstractConnection $rabbitConnection;
    private LoggerInterface $logger;
    private RabbitQueue $rabbitQueue;

    public function __construct(AbstractConnection $rabbitConnection, LoggerInterface $logger, RabbitQueue $rabbitQueue)
    {
        $this->rabbitConnection = $rabbitConnection;
        $this->logger = $logger;
        $this->rabbitQueue = $rabbitQueue;
    }

    public function receive(ExtensionResponse $response)
    {
        $this->logger->info("received response from {$response->sessionId}");
        $queue = Communicator::rabbitQueueName($response->sessionId);
        $this->rabbitQueue->createRabbitQueue($queue);
        $this->rabbitConnection->channel()->basic_publish(new AMQPMessage(json_encode($response)), $queue);
    }

}