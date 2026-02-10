<?php

namespace AwardWallet\Engine;

use Psr\Log\LoggerInterface;

class ProxyListResult
{
    /**
     * @var \Throttler
     */
    private $throttler;
    /**
     * @var string
     */
    private $key;
    /**
     * @var string
     */
    private $proxy;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(\Throttler $throttler, string $key, string $proxy, LoggerInterface $logger)
    {
        $this->throttler = $throttler;
        $this->key = $key;
        $this->proxy = $proxy;
        $this->logger = $logger;
    }

    public function markAsSuccessful(bool $success = true)
    {
        if ($success) {
            $this->throttler->increment($this->key);
            $this->logger->info("marking proxy {$this->proxy} as successful");
        } else {
            $this->logger->info("invalid proxy: {$this->proxy}");
        }
    }

    public function getProxy(): string
    {
        return $this->proxy;
    }
}
