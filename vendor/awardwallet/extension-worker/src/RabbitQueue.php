<?php

namespace AwardWallet\ExtensionWorker;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\Log\LoggerInterface;

class RabbitQueue
{

    private AbstractConnection $rabbitConnection;
    private LoggerInterface $logger;

    public function __construct(AbstractConnection $rabbitConnection, LoggerInterface $logger)
    {

        $this->rabbitConnection = $rabbitConnection;
        $this->logger = $logger;
    }

    public function createRabbitQueue(string $queue) : void
    {
        $exchange = $queue;
        $channel = $this->rabbitConnection->channel();
        $channel->queue_declare($exchange, false, false, false, false, false, new AMQPTable(['x-expires' => 300000]));
        $channel->basic_qos(0, 1, false);
        $channel->exchange_declare($exchange, AMQPExchangeType::DIRECT, false, false, true);
        $channel->queue_bind($queue, $exchange);
    }

}