<?php
namespace AwardWallet\Common\Watchdog;

use JMS\Serializer\Annotation\Type;


class Message
{

    const TYPE_START = 'start';
    const TYPE_STOP = 'stop';
    const TYPE_CONTEXT = 'context';

    /**
     * @var string
     * @Type("string")
     */
    private $type;
    /**
     * @var integer
     * @Type("integer")
     */
    private $pid;
    /**
     * @var integer
     * @Type("integer")
     */
    private $stopTime;
    /**
     * @var integer
     * @Type("integer")
     */
    private $startTime;
    /**
     * @var array
     * @Type("array")
     */
    private $eventContext = [];
    /**
     * @var array
     * @Type("array")
     */
    private $logContext = [];

    public function __construct(string $type, int $pid, ?int $startTime = null, ?int $stopTime = null, array $logContext = [], array $eventContext = [])
    {
        $this->type = $type;
        $this->pid = $pid;
        $this->startTime = $startTime;
        $this->stopTime = $stopTime;
        $this->logContext = $logContext;
        $this->logContext['WatchedPID'] = $pid;
        $this->eventContext = $eventContext;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function getPid() : int
    {
        return $this->pid;
    }

    public function getStopTime() : ?int
    {
        return $this->stopTime;
    }

    public function getStartTime() : ?int
    {
        return $this->startTime;
    }

    public function getLogContext(): array
    {
        return $this->logContext;
    }

    public function addLogContext(array $context)
    {
        $this->logContext = array_merge_recursive($this->logContext, $context);
        return $this;
    }

    public function getEventContext(): array
    {
        return $this->eventContext;
    }

    public function addEventContext(array $context)
    {
        $this->eventContext = array_merge_recursive($this->eventContext, $context);
        return $this;
    }

}