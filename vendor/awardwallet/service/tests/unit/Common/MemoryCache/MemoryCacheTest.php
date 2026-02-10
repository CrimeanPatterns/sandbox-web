<?php
namespace tests\unit\Common\MemoryCache;

use AwardWallet\Common\MemoryCache\Cache;
use AwardWallet\Common\TimeCommunicator;
use PHPUnit\Framework\TestCase;

class MemoryCacheTest extends TestCase
{

    public function testMiss()
    {
        $cache = new Cache(new TimeCommunicator());
        $result = $cache->get("key1", 600, function(){
            return "value1";
        });
        $this->assertEquals("value1", $result);
    }

    public function testHit()
    {
        $cache = new Cache(new TimeCommunicator());
        $dataSourceCallsCount = 0;
        $result = $cache->get("key1", 600, function() use (&$dataSourceCallsCount) {
            $dataSourceCallsCount++;
            return "value1";
        });
        $this->assertEquals("value1", $result);
        $this->assertEquals(1, $dataSourceCallsCount);
        $result = $cache->get("key1", 600, function() use (&$dataSourceCallsCount) {
            $dataSourceCallsCount++;
            return "value2";
        });
        $this->assertEquals("value1", $result);
        $this->assertEquals(1, $dataSourceCallsCount);
    }

    public function testExpired()
    {
        $currentTime = 1;
        $timeComm = $this->createMock(TimeCommunicator::class);
        $timeComm
            ->method('getCurrentTime')
            ->willReturnCallback(function() use (&$currentTime) {
                return $currentTime;
            })
        ;

        $cache = new Cache($timeComm);

        $result = $cache->get("key1", 100, function() {
            return "value1";
        });
        $this->assertEquals("value1", $result);

        $result = $cache->get("key1", 100, function() {
            return "value2";
        });
        $this->assertEquals("value1", $result);

        $currentTime = 102;
        $result = $cache->get("key1", 100, function() {
            return "value3";
        });
        $this->assertEquals("value3", $result);
    }

}