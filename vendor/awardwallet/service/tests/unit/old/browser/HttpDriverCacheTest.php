<?php

namespace tests\unit\old\browser;

use PHPUnit\Framework\TestCase;

class HttpDriverCacheTest extends TestCase
{

    public function testMissAndSave()
    {
        $request = new \HttpDriverRequest('http://some/url', 'GET');
        $response = new \HttpDriverResponse('some body', 200);
        $response->request = $request;

        $delegate = $this->createMock(\HttpDriverInterface::class);
        $delegate
            ->expects($this->once())
            ->method('request')
            ->with($request)
            ->willReturn(clone $response)
        ;

        $memcached = $this->createMock(\Memcached::class);
        $memcached
            ->expects($this->once())
            ->method('get')
            ->willReturn(false)
        ;
        $memcached
            ->expects($this->once())
            ->method('set')
            ->with($this->anything(), $this->anything(), 180)
        ;

        $cache = new \HttpDriverCache(
            $delegate,
            $memcached
        );

        $cacheResponse = $cache->request($request);
        unset($cacheResponse->attributes[\HttpDriverCache::ATTR_CACHE_DATE]);
        $response->attributes[\HttpDriverCache::ATTR_FROM_CACHE] = false;
        $this->assertEquals($response, $cacheResponse);
    }

    public function testSetTTL()
    {
        $request = new \HttpDriverRequest('http://some/url', 'GET', null, [], null, [\HttpDriverCache::ATTR_TTL => 50]);
        $response = new \HttpDriverResponse('some body', 200);
        $response->request = $request;

        $delegate = $this->createMock(\HttpDriverInterface::class);
        $delegate
            ->expects($this->once())
            ->method('request')
            ->with($request)
            ->willReturn(clone $response)
        ;

        $memcached = $this->createMock(\Memcached::class);
        $memcached
            ->expects($this->once())
            ->method('get')
            ->willReturn(false)
        ;
        $memcached
            ->expects($this->once())
            ->method('set')
            ->with($this->anything(), $this->anything(), 50)
        ;

        $cache = new \HttpDriverCache(
            $delegate,
            $memcached
        );

        $cacheResponse = $cache->request($request);
        unset($cacheResponse->attributes[\HttpDriverCache::ATTR_CACHE_DATE]);
        $response->attributes[\HttpDriverCache::ATTR_FROM_CACHE] = false;
        $this->assertEquals($response, $cacheResponse);
    }

    public function testHit()
    {
        $request = new \HttpDriverRequest('http://some/url', 'GET');
        $response = new \HttpDriverResponse('some body', 200);
        $response->request = $request;

        $delegate = $this->createMock(\HttpDriverInterface::class);
        $delegate
            ->expects($this->never())
            ->method('request')
        ;

        $memcached = $this->createMock(\Memcached::class);
        $memcached
            ->expects($this->once())
            ->method('get')
            ->willReturn(serialize($response))
        ;
        $memcached
            ->expects($this->never())
            ->method('set')
        ;

        $cache = new \HttpDriverCache(
            $delegate,
            $memcached
        );

        $cacheResponse = $cache->request($request);
        $response->attributes[\HttpDriverCache::ATTR_FROM_CACHE] = true;
        $this->assertEquals($response, $cacheResponse);
    }

    public function testNoCacheOnPost()
    {
        $request = new \HttpDriverRequest('http://some/url', 'POST');
        $response = new \HttpDriverResponse('some body', 200);
        $response->request = $request;

        $delegate = $this->createMock(\HttpDriverInterface::class);
        $delegate
            ->expects($this->once())
            ->method('request')
            ->with($request)
            ->willReturn(clone $response)
        ;

        $memcached = $this->createMock(\Memcached::class);
        $memcached
            ->expects($this->never())
            ->method('get')
        ;
        $memcached
            ->expects($this->never())
            ->method('set')
        ;

        $cache = new \HttpDriverCache(
            $delegate,
            $memcached
        );

        $cacheResponse = $cache->request($request);
        $response->attributes[\HttpDriverCache::ATTR_FROM_CACHE] = false;
        $this->assertEquals($response, $cacheResponse);
    }

    public function testCanCacheCallbackTrue()
    {
        $request = new \HttpDriverRequest('http://some/url', 'POST', null, [], null, [\HttpDriverCache::ATTR_CAN_CACHE_CALLBACK => function(\HttpDriverResponse $response){ return true; }]);
        $response = new \HttpDriverResponse('some body', 500);
        $response->request = $request;

        $delegate = $this->createMock(\HttpDriverInterface::class);
        $delegate
            ->expects($this->once())
            ->method('request')
            ->with($request)
            ->willReturn(clone $response)
        ;

        $memcached = $this->createMock(\Memcached::class);
        $memcached
            ->expects($this->once())
            ->method('get')
        ;
        $memcached
            ->expects($this->once())
            ->method('set')
        ;

        $cache = new \HttpDriverCache(
            $delegate,
            $memcached
        );

        $cacheResponse = $cache->request($request);
        $response->attributes[\HttpDriverCache::ATTR_FROM_CACHE] = false;
        unset($cacheResponse->attributes[\HttpDriverCache::ATTR_CACHE_DATE]);
        $this->assertEquals($response, $cacheResponse);
    }

    public function testCanCacheCallbackFalse()
    {
        $request = new \HttpDriverRequest('http://some/url', 'POST', null, [], null, [\HttpDriverCache::ATTR_CAN_CACHE_CALLBACK => function(\HttpDriverResponse $response){ return false; }]);
        $response = new \HttpDriverResponse('some body', 500);
        $response->request = $request;

        $delegate = $this->createMock(\HttpDriverInterface::class);
        $delegate
            ->expects($this->once())
            ->method('request')
            ->with($request)
            ->willReturn(clone $response)
        ;

        $memcached = $this->createMock(\Memcached::class);
        $memcached
            ->expects($this->once())
            ->method('get')
        ;
        $memcached
            ->expects($this->never())
            ->method('set')
        ;

        $cache = new \HttpDriverCache(
            $delegate,
            $memcached
        );

        $cacheResponse = $cache->request($request);
        $response->attributes[\HttpDriverCache::ATTR_FROM_CACHE] = false;
        unset($cacheResponse->attributes[\HttpDriverCache::ATTR_CACHE_DATE]);
        $this->assertEquals($response, $cacheResponse);
    }

}