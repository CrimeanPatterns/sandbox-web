<?php

namespace AwardWallet\Common\Watchdog;


use Symfony\Component\EventDispatcher\Event;

class KillEvent extends Event
{

    const NAME = 'aw.watchdog.kill_process';
    /** @var int */
    private $startTime;
    /** @var array */
    private $logContext;
    /** @var array */
    private $eventContext;

    public function __construct(int $startTime, array $logContext = [], array $eventContext = [])
    {
        $this->startTime = $startTime;
        $this->logContext = $logContext;
        $this->eventContext = $eventContext;
    }

    public function getStartTime(): int
    {
        return $this->startTime;
    }

    public function getLogContext(): array
    {
        return $this->logContext;
    }

    public function getEventContext(): array
    {
        return $this->eventContext;
    }

}