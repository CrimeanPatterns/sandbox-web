<?php

namespace AwardWallet\Common\MemoryCache;

class Item
{

    private $value;
    /**
     * @var int
     */
    private $expirationTime;

    public function __construct($value, int $expirationTime)
    {
        $this->value = $value;
        $this->expirationTime = $expirationTime;
    }

    public function getExpirationTime(): int
    {
        return $this->expirationTime;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

}