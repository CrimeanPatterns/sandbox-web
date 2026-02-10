<?php
namespace tests\unit\old\browser;

use PHPUnit\Framework\MockObject\Stub\ConsecutiveCalls;
use Psr\Log\NullLogger;
use PHPUnit\Framework\TestCase;

class SeleniumConsulFinderTest extends TestCase
{

    /**
     * @dataProvider getServerDataProvider
     */
    public function testGetServer($nodesJson, $kvJson, $expectedServers, $browserFamily, $browserVersion)
    {
        $httpDriver = $this->createMock(\CurlDriver::class);
        $nodesResponse = new \HttpDriverResponse();
        $nodesResponse->body = $nodesJson;
        $kvResponse = new \HttpDriverResponse();
        $kvResponse->body = $kvJson;
        $httpDriver
            ->expects($this->exactly(2))
            ->method('request')
            ->will($this->onConsecutiveCalls(
                $nodesResponse,
                $kvResponse
            ));

        $finder = new \SeleniumConsulFinder("192.168.10.1", new NullLogger(), $httpDriver);
        $servers = $finder->getServers(new \SeleniumFinderRequest($browserFamily, $browserVersion));

        $this->assertEquals($expectedServers, $servers, '', 0, 10, true);
    }

    public function getServerDataProvider()
    {
        return [
            [
                file_get_contents(__DIR__ . '/seleniumNodesWithPort.json'),
                file_get_contents(__DIR__ . '/seleniumKVWithPort.json'),
                [
                    new \SeleniumServer("host2.docker.internal", 12654),
                    new \SeleniumServer("host1.docker.internal", 12654),
                ],
                \SeleniumFinderRequest::BROWSER_CHROME,
                \SeleniumFinderRequest::CHROME_94,
            ],
            [
                file_get_contents(__DIR__ . '/seleniumNodes.json'),
                file_get_contents(__DIR__ . '/seleniumKV.json'),
                [
                    new \SeleniumServer("host1.docker.internal", 11594),
                    new \SeleniumServer("host2.docker.internal", 11594),
                ],
                \SeleniumFinderRequest::BROWSER_FIREFOX,
                \SeleniumFinderRequest::FIREFOX_59,
            ],
            [
                file_get_contents(__DIR__ . '/seleniumNodes.json'),
                file_get_contents(__DIR__ . '/seleniumKV2.json'),
                [
                    new \SeleniumServer("host2.docker.internal", 11594),
                    new \SeleniumServer("host1.docker.internal", 11594),
                ],
                \SeleniumFinderRequest::BROWSER_FIREFOX,
                \SeleniumFinderRequest::FIREFOX_59,
            ],
        ];
    }

}