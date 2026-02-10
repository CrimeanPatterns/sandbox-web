<?php
namespace tests\unit\Common\Memcached;

use AwardWallet\Common\AWS\Util;
use AwardWallet\Common\MemoryCache\Cache;
use AwardWallet\Common\TimeCommunicator;
use PHPUnit\Framework\TestCase;


class UtilTest extends TestCase
{

    public function testGetHostNameSuccess()
    {
        $http = $this->createMock(\HttpDriverInterface::class);
        $http
            ->expects($this->once())
            ->method('request')
            ->with(new \HttpDriverRequest('http://169.254.169.254/latest/meta-data/public-hostname', 'GET', null, [], 3))
            ->willReturn(new \HttpDriverResponse('ec2-54-204-36-66.compute-1.amazonaws.com'))
        ;

        $util = new Util($http, new Cache(new TimeCommunicator()));
        $this->assertEquals('ec2-54-204-36-66.compute-1.amazonaws.com', $util->getHostName());
        $this->assertEquals('ec2-54-204-36-66.compute-1.amazonaws.com', $util->getHostName());
    }

    public function testGetHostNameFail()
    {
        $http = $this->createMock(\HttpDriverInterface::class);
        $http
            ->expects($this->once())
            ->method('request')
            ->with(new \HttpDriverRequest('http://169.254.169.254/latest/meta-data/public-hostname', 'GET', null, [], 3))
            ->willReturn(new \HttpDriverResponse('Timeout', 502))
        ;

        $util = new Util($http, new Cache(new TimeCommunicator()));
        $this->assertEquals(null, $util->getHostName());
        $this->assertEquals(null, $util->getHostName());
    }

    public function testRegionSuccess()
    {
        $http = $this->createMock(\HttpDriverInterface::class);
        $http
            ->expects($this->once())
            ->method('request')
            ->with(new \HttpDriverRequest('http://169.254.169.254/latest/meta-data/placement/availability-zone', 'GET', null, [], 3))
            ->willReturn(new \HttpDriverResponse('us-east-1b'))
        ;

        $util = new Util($http, new Cache(new TimeCommunicator()));
        $this->assertEquals('us-east-1', $util->getRegion());
        $this->assertEquals('us-east-1', $util->getRegion());
    }

    public function testLocalIP()
    {
        $http = $this->createMock(\HttpDriverInterface::class);
        $http
            ->expects($this->once())
            ->method('request')
            ->with(new \HttpDriverRequest('http://169.254.169.254/latest/meta-data/local-ipv4', 'GET', null, [], 3))
            ->willReturn(new \HttpDriverResponse('1.2.3.4'))
        ;

        $util = new Util($http, new Cache(new TimeCommunicator()));
        $this->assertEquals('1.2.3.4', $util->getLocalIP());
        $this->assertEquals('1.2.3.4', $util->getLocalIP());
    }

    public function testPublicIP()
    {
        $http = $this->createMock(\HttpDriverInterface::class);
        $http
            ->expects($this->once())
            ->method('request')
            ->with(new \HttpDriverRequest('http://169.254.169.254/latest/meta-data/public-ipv4', 'GET', null, [], 3))
            ->willReturn(new \HttpDriverResponse('1.2.3.4'))
        ;

        $util = new Util($http, new Cache(new TimeCommunicator()));
        $this->assertEquals('1.2.3.4', $util->getPublicIP());
        $this->assertEquals('1.2.3.4', $util->getPublicIP());
    }

}