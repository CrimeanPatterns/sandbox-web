<?php

namespace tests\unit\Common\Parsing;

use AwardWallet\Common\Memcached\MemcachedMock;
use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Common\Parsing\ProxyChecker;
use AwardWallet\Common\Parsing\Solver\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;


class ProxyCheckerTest extends TestCase
{
    /** @var \CurlDriver */
    private $curlDriver;
    /** @var \Memcached */
    private $memcached;
    /** @var ProxyChecker */
    private $proxyChecker;
    const TEST_GOOD_PROXY = '47.88.3.19:8080';
    const TEST_BAD_PROXY = '1.1.1.1:8080';

    protected function setUp()
    {
        parent::setUp();
        $this->curlDriver = $this->createMock(\CurlDriver::class);
        $this->memcached = new MemcachedMock();
        $this->proxyChecker = new ProxyChecker(
            $this->curlDriver,
            $this->memcached,
            $this->createMock(LoggerInterface::class)
        );
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->memcached = null;
        $this->curlDriver = null;
        $this->proxyChecker = null;
    }

    public function testOneGoodProxy()
    {
        $this->curlDriver->method('request')
            ->willReturn(
                new \HttpDriverResponse(null, 200)
            );
        $this->curlDriver->expects($this->once())
            ->method('request');

        $proxy = $this->proxyChecker->getLiveProxy([self::TEST_GOOD_PROXY]);
        $this->assertEquals($proxy, self::TEST_GOOD_PROXY);

        $proxy = $this->proxyChecker->getLiveProxy([self::TEST_GOOD_PROXY]);
        $this->assertEquals($proxy, self::TEST_GOOD_PROXY);
    }

    public function testTwoProxies()
    {
        $this->curlDriver->method('request')
            ->willReturnOnConsecutiveCalls(
                new \HttpDriverResponse(null, 403),
                new \HttpDriverResponse(null, 200)
            );

        $proxy = $this->proxyChecker->getLiveProxy([self::TEST_BAD_PROXY, self::TEST_GOOD_PROXY]);
        $this->assertEquals($proxy, self::TEST_GOOD_PROXY);
    }

    public function testExceptionProxy()
    {
        $this->curlDriver->method('request')
            ->willReturn(
                new \HttpDriverResponse(null, 403)
            );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Live proxy not found!');

        $this->proxyChecker->getLiveProxy([self::TEST_BAD_PROXY, self::TEST_GOOD_PROXY]);
    }
}
