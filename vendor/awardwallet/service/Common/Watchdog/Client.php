<?php

namespace AwardWallet\Common\Watchdog;

use AwardWallet\Common\TimeCommunicator;
use JMS\Serializer\SerializerInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

class Client
{

    public const DEFAULT_TIMEOUT = 60;

    public function __construct(LoggerInterface $logger, AMQPChannel $mqChannel, SerializerInterface $serializer, TimeCommunicator $time)
    {
        $this->logger = $logger;
        $this->mqChannel = $mqChannel;
        $this->serializer = $serializer;
        $this->time = $time;
        $this->queue = Server::declareQueue($this->mqChannel);
    }

    public function start(int $pid, int $processTimeout = self::DEFAULT_TIMEOUT, array $logContext = [], array $eventContext = [])
    {
        $this->sendMessage(new Message(Message::TYPE_START, $pid, $this->time->getCurrentTime(), $this->time->getCurrentTime() + $processTimeout, $logContext, $eventContext));
    }

    public function stop(int $pid){
        $this->sendMessage(new Message(Message::TYPE_STOP, $pid));
    }

    public function addContext(int $pid, array $logContext = [], array $eventContext = [])
    {
        $this->sendMessage(new Message(Message::TYPE_CONTEXT, $pid, null, null, $logContext, $eventContext));
    }

    private function sendMessage(Message $msg){
        $message = new AMQPMessage(
            $this->serializer->serialize($msg, 'json'),
            array('content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT, 'expiration' => 60000)
        );
        $this->mqChannel->basic_publish($message, '', $this->queue);
    }

}