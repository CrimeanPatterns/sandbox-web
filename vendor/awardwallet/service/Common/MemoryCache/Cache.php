<?php

namespace AwardWallet\Common\MemoryCache;

use AwardWallet\Common\TimeCommunicator;

class Cache
{

    /**
     * @var TimeCommunicator
     */
    private $timeCommunicator;

    public function __construct(TimeCommunicator $timeCommunicator)
    {
        $this->timeCommunicator = $timeCommunicator;
    }

    /** @var Item[] */
    private $cache = [];

    public function get(string $key, int $ttl, Callable $dataSource)
    {
        $existing = $this->cache[$key] ?? null;
        if ($existing !== null && $existing->getExpirationTime() <= $this->timeCommunicator->getCurrentTime()) {
            $existing = null;
        }

        if ($existing !== null) {
            return $existing->getValue();
        }

        $value = $dataSource();
        $this->cache[$key] = new Item($value, $this->timeCommunicator->getCurrentTime() + $ttl);
        return $value;
    }

    public function clear() : void
    {
        $this->cache = [];
    }

}
