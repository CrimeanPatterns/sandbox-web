<?php
namespace AwardWallet\ExtensionWorker;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

class Communicator
{

    private string $sessionId;
    private \phpcent\Client $centrifuge;
    private AbstractConnection $rabbitConnection;
    private LoggerInterface $logger;

    public function __construct(
        string $sessionId,
        \phpcent\Client $centrifuge,
        AbstractConnection $rabbitConnection,
        RabbitQueue $rabbitQueue,
        LoggerInterface $logger
    )
    {
        $this->sessionId = $sessionId;
        $this->centrifuge = $centrifuge;
        $this->rabbitConnection = $rabbitConnection;
        $this->logger = $logger;

        $channel = $this->rabbitConnection->channel();
        $queue = self::rabbitQueueName($this->sessionId);
        $rabbitQueue->createRabbitQueue($queue);
        $response = $this->readFromRabbitQueue($channel, $queue, "0");
        $this->logger->info("browser connected: " . json_encode($response));
    }

    public function sendMessageToExtension(ExtensionRequest $message)
    {
        $this->centrifuge->publish("#" . $this->sessionId, (array) $message);
        $response = $this->readFromRabbitQueue($this->rabbitConnection->channel(), self::rabbitQueueName($this->sessionId), $message->requestId);

        if ($response->result["status"] !== "ok") {
            throw new ExtensionError("Extension error: {$response->result["error"]}");
        }

        return $response->result["result"] ?? null;
    }

    public static function rabbitQueueName(string $sessionId) : string
    {
        return "ew-" . $sessionId;
    }

    private function readFromRabbitQueue(AMQPChannel $channel, string $queue, string $requestId) : ExtensionResponse
    {
        /** @var ExtensionResponse $response */
        $response = null;
        $consumerTag = $channel->basic_consume($queue, "e-w-communicator-" . $this->sessionId, true, false, false, false, function(AMQPMessage $rabbitMessage) use (&$response, $requestId) {
            $data = json_decode($rabbitMessage->body, true);
            $response = new ExtensionResponse($data['sessionId'], $data['result'], $data['requestId']);

            if ($response->sessionId !== $this->sessionId) {
                $error = "invalid sessionId {$response->sessionId} in session channel {$this->sessionId}";
                $this->logger->error($error);
                throw new CommunicationException("invalid sessionId {$response->sessionId} in session channel {$this->sessionId}");
            }

            if ($response->requestId !== $requestId) {
                $this->logger->warning("unknown requestId {$response->requestId}, expecting {$requestId}, possible already processed");
                $rabbitMessage->ack();
                $response = null;
            }
        });

        try {
            do {
                try {
                    $channel->wait(null, false, 15);
                } catch (AMQPTimeoutException $exception) {
                    throw new CommunicationException("timed out waiting for response");
                }
            } while (count($channel->callbacks) && $response === null);
        } finally {
            $channel->basic_cancel($consumerTag);
        }

        return $response;
    }

}