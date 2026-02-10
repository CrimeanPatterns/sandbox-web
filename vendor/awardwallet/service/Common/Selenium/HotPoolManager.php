<?php

namespace AwardWallet\Common\Selenium;

use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;

class HotPoolManager
{

    private const PREFIX = 'hpm_';
    private const CONTEXT_PREFIX = 'hpm_prefix';
    private const CONTEXT_CONNECTION = 'hpm_connection';

    /**
     * @var \Memcached
     */
    private $memcached;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var \SeleniumConnector
     */
    private $connector;
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(\Memcached $memcached, LoggerInterface $logger, \SeleniumConnector $seleniumConnector, SerializerInterface $serializer)
    {
        $this->memcached = $memcached;
        $this->logger = $logger;
        $this->connector = $seleniumConnector;
        $this->serializer = $serializer;
    }

    public function getConnection(string $prefix, int $maxSessions) : ?\SeleniumConnection
    {
        for($n = 0; $n < $maxSessions; $n++) {
            $key = $this->getCacheKey($prefix, $n);
            $sessionInfo = $this->memcached->get($key);

            if ($sessionInfo === false) {
                continue;
            }

            if (!$this->lock($prefix, $n)) {
                continue;
            }

            /** @var \SeleniumConnection $connection */
            $connection = $this->serializer->deserialize($sessionInfo, \SeleniumConnection::class, 'json');
            $session =  new \SeleniumSession(
                $connection->getSessionId(),
                $connection->getHost(),
                $connection->getPort(),
                $connection->getPath(),
                $connection->getShare(),
                $connection->getContext(),
                new \SeleniumOptions()
            );
            $webDriver = $this->connector->restoreSession($session);

            if ($webDriver === null) {
                $this->logger->info("removing connection $n, {$session->getSessionId()} from hot pool");
                $this->memcached->delete($key);
                $this->unlock($prefix, $n);
                continue;
            }

            $connection->setWebDriver($webDriver);
            $connection->addContext(self::CONTEXT_CONNECTION, $n);
            $this->logger->info("got hot connection {$n}, {$session->getSessionId()} on {$session->getHost()}:{$session->getPort()}");

            return $connection;
        }

        $this->logger->info("no hot connections found");

        return null;
    }

    private function getCacheKey(string $prefix, int $index) : string
    {
        return self::PREFIX . $prefix . '_' . $index;
    }

    private function lock(string $prefix, int $n) : bool
    {
        return $this->memcached->add($this->getLockKey($prefix, $n), time(), 300);
    }

    private function getLockKey(string $prefix, int $n) : string
    {
        return self::PREFIX . $prefix . "_" . $n . '_lock';
    }

    private function unlock(string $prefix, int $n) : void
    {
        $this->memcached->delete($this->getLockKey($prefix, $n));
    }

    public function saveConnection(\SeleniumConnection $connection, string $prefix, int $maxConnections) : void
    {
        $connection->addContext(self::CONTEXT_PREFIX, $prefix);
        $connectionIndex = $connection->getContext()[self::CONTEXT_CONNECTION] ?? null;

        if ($connectionIndex !== null) {
            $this->logger->info("ongoing hot connection $connectionIndex");
            $this->unlock($prefix, $connectionIndex);
            return;
        }

        $connectionIndex = $this->acquireFreeConnectionIndex($prefix, $maxConnections);

        if ($connectionIndex === null) {
            return;
        }

        $this->logger->info("saving hot connection $connectionIndex");
        $this->memcached->set($this->getCacheKey($prefix, $connectionIndex), $this->serializer->serialize($connection, 'json'), 13 * 60);
        $this->unlock($prefix, $connectionIndex);
    }

    private function acquireFreeConnectionIndex(string $prefix, int $maxConnections) : ?int
    {
        for( $n = 0; $n < $maxConnections; $n++ ) {
            $sessionInfo = $this->memcached->get($this->getCacheKey($prefix, $n));

            if ($sessionInfo !== false) {
                continue;
            }

            if ($this->lock($prefix, $n)) {
                $this->logger->info("acquired hot connection $n");
                return $n;
            }
        }

        return null;
    }

    public function deleteConnection(\SeleniumConnection $connection, string $prefix) : void
    {
        $connectionIndex = $connection->getContext()[self::CONTEXT_CONNECTION] ?? null;

        if ($connectionIndex === null) {
            return;
        }

        $this->logger->info("deleting hot connection $connectionIndex");
        $this->memcached->delete($this->getCacheKey($prefix, $connectionIndex));
    }

}