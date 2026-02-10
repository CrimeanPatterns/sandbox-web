<?php
namespace tests\unit\Common\Geo;

use AwardWallet\Common\Geo\TimezoneDb\Client;
use AwardWallet\Common\Geo\TimezoneDb\Response;
use PHPUnit\Framework\TestCase;

class TimezoneDbClientTest extends TestCase
{

    public function testSuccess()
    {
        $httpDriver = $this->createMock(\HttpDriverCache::class);
        $httpDriver
            ->expects($this->once())
            ->method('request')
            ->willReturn(new \HttpDriverResponse('{"status":"OK","message":"","countryCode":"US","countryName":"United States","zoneName":"America\/Los_Angeles","abbreviation":"PST","gmtOffset":-28800,"dst":"0","zoneStart":1541322000,"zoneEnd":1552212000,"nextAbbreviation":"PDT","timestamp":1548108614,"formatted":"2019-01-21 22:10:14"}', 200))
        ;

        $client = new Client(
            "http://api.timezonedb.com",
            "someKey",
            $httpDriver
        );

        $response = $client->getTimezone(100, 200);

        $this->assertEquals(new Response('America/Los_Angeles', -28800), $response);
    }

    public function testHttpError()
    {
        $httpDriver = $this->createMock(\HttpDriverCache::class);
        $httpDriver
            ->expects($this->once())
            ->method('request')
            ->willReturn(new \HttpDriverResponse('{"status":"OK","message":"","countryCode":"US","countryName":"United States","zoneName":"America\/Los_Angeles","abbreviation":"PST","gmtOffset":-28800,"dst":"0","zoneStart":1541322000,"zoneEnd":1552212000,"nextAbbreviation":"PDT","timestamp":1548108614,"formatted":"2019-01-21 22:10:14"}', 400))
        ;

        $client = new Client(
            "http://api.timezonedb.com",
            "someKey",
            $httpDriver
        );

        $response = $client->getTimezone(100, 200);
        $this->assertNull($response);
    }

    public function testResponseFormatError()
    {
        $httpDriver = $this->createMock(\HttpDriverCache::class);
        $httpDriver
            ->expects($this->once())
            ->method('request')
            ->willReturn(new \HttpDriverResponse('where is my cookies?', 400))
        ;

        $client = new Client(
            "http://api.timezonedb.com",
            "someKey",
            $httpDriver
        );

        $response = $client->getTimezone(100, 200);
        $this->assertNull($response);
    }

}