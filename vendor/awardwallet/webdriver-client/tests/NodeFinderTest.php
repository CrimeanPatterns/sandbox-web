<?php
namespace App\Tests;

use AwardWallet\WebdriverClient\NodeFinder;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @coversDefaultClass \AwardWallet\WebdriverClient\NodeFinder
 */
class NodeFinderTest extends TestCase
{

    /**
     * @dataProvider dataProvider
     */
    public function test(ResponseInterface $response, ?string $expectedAddress)
    {
        $nodeFinder = new NodeFinder(new MockHttpClient([$response]), "http://blah", new NullLogger());
        $address = $nodeFinder->getNode();
        $this->assertEquals($expectedAddress, $address);
    }

    public function dataProvider()
    {
        return [
            [
                new MockResponse('not authorized', ['http_code' => 503]),
                null
            ],
            [
                new MockResponse('bad body', ['http_code' => 200]),
                null
            ],
            [
                new MockResponse('{"node":null}', ['http_code' => 200]),
                null
            ],
            [
                new MockResponse('{"node":{"healthy":1,"expirationDate":1656585731,"address":"172.33.44.114","lowLoad":1}}', ['http_code' => 200]),
                "172.33.44.114"
            ],
        ];
    }

}