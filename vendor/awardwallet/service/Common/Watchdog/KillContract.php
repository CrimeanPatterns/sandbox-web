<?php

namespace AwardWallet\Common\Watchdog;

class KillContract
{

    /**
     * @var Message
     */
    private $message;
    /**
     * @var int
     */
    private $time;

    public function __construct(Message $message, int $time)
    {
        $this->message = $message;
        $this->time = $time;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function getTime(): int
    {
        return $this->time;
    }
}