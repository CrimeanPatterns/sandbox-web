<?php
namespace AwardWallet\Common\Watchdog;

use AwardWallet\Common\TimeCommunicator;
use JMS\Serializer\Serializer;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Server
{

    /**
     * @internal
     */
    public const QUEUE_NAME = 'watchdog4_%s';

    /** @var LoggerInterface */
    private $logger;
    /** @var AMQPChannel */
    private $mqChannel;
    /** @var Serializer */
    private $serializer;
    /** @var EventDispatcherInterface */
    private $eventDispatcher;
    /** @var TimeCommunicator */
    private $time;
    /**
     * @var Message[]
     */
    private $activeProcesses = [];
    private $stopped = false;
    /**
     * @var KillContract[]
     */
    private $killQueue = [];

    public function __construct(LoggerInterface $logger, AMQPChannel $mqChannel, Serializer $serializer, EventDispatcherInterface $eventDispatcher, TimeCommunicator $time)
    {
        $this->logger = $logger;
        $this->mqChannel = $mqChannel;
        $this->serializer = $serializer;
        $this->eventDispatcher = $eventDispatcher;
        $this->time = $time;
        $this->queue = self::declareQueue($this->mqChannel);
        ;
    }

    public static function declareQueue(AMQPChannel $mqChannel) : string
    {
        $queueName = sprintf(self::QUEUE_NAME, gethostname());
        $mqChannel->queue_declare($queueName, false, true, false, false, false,  ['x-expires' => ['I', 600000]]);
        return $queueName;
    }

    public function run($loop = true){
        if ($loop) {
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, function(){
                $this->logger->info("SIGTERM received, stopping");
                $this->stopped = true;
            });
        }

        do {
            while (!$this->stopped && $this->checkMessage()) {}

            $this->processKillQueue();

            if (!$this->stopped && !$this->killSomeone() && $loop) {
                $this->time->sleep(1);
            }

        } while(!$this->stopped && $loop);
    }

    private function processKillQueue()
    {
        $curTime = $this->time->getCurrentTime();
        foreach ($this->killQueue as $killContract) {
            if ($killContract->getTime() < $curTime) {
                $this->logger->notice('terminating process', $killContract->getMessage()->getLogContext());
                posix_kill($killContract->getMessage()->getPid(), SIGKILL);
                unset($this->killQueue[$killContract->getMessage()->getPid()]);
            }
        }
    }

    private function checkMessage()
    {
        /** @var AMQPMessage $amqpMessage */
        $amqpMessage = $this->mqChannel->basic_get($this->queue);
        if (!$amqpMessage) {
            return false;
        }

        /** @var Message $message */
        $message = $this->serializer->deserialize($amqpMessage->getBody(), Message::class, 'json');
        if (!in_array($message->getType(), [Message::TYPE_START, Message::TYPE_STOP, Message::TYPE_CONTEXT])){
            $this->mqChannel->basic_ack($amqpMessage->delivery_info['delivery_tag']);
            $this->logger->critical('Unknown message type: ' . $message->getType());
            return true;
        }

        if ($message->getType() === Message::TYPE_START) {
            $this->logger->debug("starting monitoring of " . $message->getPid() . ", end time: " . date("Y-m-d H:i:s", $message->getStopTime()));
            if (isset($this->activeProcesses[$message->getPid()])) {
                $this->logger->debug("process already exists: " . $message->getPid(), $this->activeProcesses[$message->getPid()]->getLogContext());
            }
            $this->activeProcesses[$message->getPid()] = $message;
            unset($this->killQueue[$message->getPid()]);
        }

        if($message->getType() === Message::TYPE_STOP && isset($this->activeProcesses[$message->getPid()])) {
            $existingMessage = $this->activeProcesses[$message->getPid()] ?? null;
            if ($existingMessage === null) {
                $this->logger->warning("missing process: " . $message->getPid());
                return true;
            }
            unset($this->activeProcesses[$message->getPid()]);
            $this->logger->debug("stopped monitoring of " . $message->getPid(), $existingMessage->getLogContext());
        }

        if($message->getType() === Message::TYPE_CONTEXT && isset($this->activeProcesses[$message->getPid()])){
            /** @var Message $existingMessage */
            $existingMessage = $this->activeProcesses[$message->getPid()] ?? null;
            if ($existingMessage === null) {
                $this->logger->warning("missing process: " . $message->getPid());
                return true;
            }
            $existingMessage->addLogContext($message->getLogContext());
            $existingMessage->addEventContext($message->getEventContext());
            $this->logger->debug("added context to " . $message->getPid(), $existingMessage->getLogContext());
        }

        $this->mqChannel->basic_ack($amqpMessage->delivery_info['delivery_tag']);
        return true;
    }

    private function killSomeone() : bool
    {
        /** @var Message $message */
        foreach($this->activeProcesses as $pid => $message)
        {
            if($this->time->getCurrentTime() >= $message->getStopTime()) {
                $this->killProcess($message);
                // killing takes 3 seconds, we do not want to hang in this loop for long amount of time
                // processes may start / stop in this time
                // we should read commands from new processes first
                return true;
            }
        }

        return false;
    }

    private function killProcess(Message $message)
    {
        $this->logger->notice('killing process', $message->getLogContext());

        posix_kill($message->getPid(), SIGTERM);
        $this->killQueue[$message->getPid()] = new KillContract($message, time());

        unset($this->activeProcesses[$message->getPid()]);
        $this->logger->debug('process killed', $message->getLogContext());
        $this->eventDispatcher->dispatch(KillEvent::NAME, new KillEvent($message->getStartTime(), $message->getLogContext(), $message->getEventContext()));
    }

}