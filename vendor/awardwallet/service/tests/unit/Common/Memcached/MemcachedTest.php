<?php


namespace tests\unit\Common\Memcached;

use AwardWallet\Common\Memcached\Noop;
use AwardWallet\Common\Memcached\Util;
use AwardWallet\Common\Memcached\MemcachedMock;
use PHPUnit\Framework\TestCase;

class MemcachedTest extends TestCase
{
    /**
     * @var Util
     */
    private $memcachedUtil;
    /**
     * @var \Memcached
     */
    private $memcached;
    /**
     * @var string
     */
    private $keyPrefix;

    public function setUp() : void
    {
        $this->memcached = new MemcachedMock();
        $this->memcachedUtil = new Util($this->memcached);
        $this->keyPrefix = 'memcached_test_' . bin2hex(random_bytes(10));
    }
    /**
     * @covers ::update
     */
    public function testUpdateShouldGoNextLapWhenAddFailed() : void
    {
        $invocationCounter = 0;
        $key = $this->keyPrefix . '_update';

        $updateResult = $this->memcachedUtil->update(
            $key,
            function ($data) use (& $invocationCounter, $key) {
                if (0 === $invocationCounter) {
                    $this->memcached->set($key, 'Invalidate CAS!');
                }

                $invocationCounter++;

                return 1;
            }
        );

        $this->assertEquals(1, $this->memcached->get($key));
        $this->assertEquals(2, $invocationCounter);
        $this->assertTrue($updateResult);
    }

    /**
     * @covers ::update
     */
    public function testUpdateShouldGoNextLapWhenCasFailed() : void
    {
        $invocationCounter = 0;
        $key = $this->keyPrefix . '_udpate';
        $this->memcached->set($key, 2);

        $updateResult = $this->memcachedUtil->update(
            $key,
            function ($data) use (& $invocationCounter, $key) {
                if (0 === $invocationCounter) {
                    $this->memcached->set($key, 'Invalidate CAS!');
                }

                $invocationCounter++;

                return 1;
            }
        );

        $this->assertEquals(1, $this->memcached->get($key));
        $this->assertEquals(2, $invocationCounter);
        $this->assertTrue($updateResult);
    }

    /**
     * @covers ::update
     */
    public function testWhenNoMoreAvailableLapsUpdateIterationsShouldStop() : void
    {
        $invocationCounter = 0;
        $key = $this->keyPrefix . '_udpate';
        $this->memcached->set($key, 2);

        $updateResult = $this->memcachedUtil->update(
            $key,
            function () use (&$invocationCounter, $key) {
                $this->memcached->set($key, 'Invalidate key on every updater call!');
                $invocationCounter++;

                return 1;
            },
            3600,
            3
        );

        $this->assertEquals('Invalidate key on every updater call!', $this->memcached->get($key));
        $this->assertEquals(3, $invocationCounter);
        $this->assertFalse($updateResult);
    }

    /**
     * @covers ::update
     */
    public function testWhenNoopReturnedUpdaterShouldStop() : void
    {
        $invocationCounter = 0;
        $key = $this->keyPrefix . '_udpate';
        $this->memcached->set($key, 2);

        $updateResult = $this->memcachedUtil->update(
            $key,
            function () use (&$invocationCounter, $key) {
                $this->memcached->set($key, 'Invalidate key on every updater call!');

                $invocationCounter++;

                if (1 === $invocationCounter) {
                    return 1;
                }

                // invocationCounter === 2
                return Noop::getInstance();
            },
            3600,
            1000
        );

        $this->assertEquals('Invalidate key on every updater call!', $this->memcached->get($key));
        $this->assertEquals(2, $invocationCounter);
        $this->assertFalse($updateResult);
    }
}