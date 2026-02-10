<?php

namespace tests\unit\old\browser;

use PHPUnit\Framework\TestCase;

class HttpBrowserStateTest extends TestCase
{

    public function testRestoreState()
    {
        $request1 = new \HttpDriverRequest('http://some.url', 'GET', null, ['User-Agent' => 'UserAgent1', 'Origin' => 'http://some.url']);

        $response1 = new \HttpDriverResponse('somebody1', 200);
        $response1->headers = ['set-cookie' => 'cookie1=value1'];
        $response1->request = $request1;

        $httpDriver = $this->createMock(\HttpDriverInterface::class);
        $httpDriver
            ->expects($this->once())
            ->method('request')
            ->with($request1)
            ->willReturn($response1)
        ;
        $httpDriver
            ->expects($this->once())
            ->method('getState')
            ->willReturn([])
        ;

        $http = new \HttpBrowser("none", $httpDriver);
        $http->setUserAgent('UserAgent1');
        $this->configureHttp($http);
        $http->GetURL("http://some.url");
        $this->assertEquals('somebody1', $http->Response['body']);

        $state = $http->GetState();

        $request2 = new \HttpDriverRequest('http://some.url', 'GET', null, [
            'Cookie' => 'cookie1=value1',
            'User-Agent' => 'UserAgent1',
            'Origin' => 'http://some.url',
        ]);

        $response2 = new \HttpDriverResponse('somebody2', 200);
        $response2->request = $request2;

        $httpDriver = $this->createMock(\HttpDriverInterface::class);
        $httpDriver
            ->expects($this->once())
            ->method('request')
            ->with($request2)
            ->willReturn($response2)
        ;

        $http = new \HttpBrowser("none", $httpDriver);
        $this->configureHttp($http);
        $http->SetState($state);
        $http->GetURL("http://some.url");
        $this->assertEquals('somebody2', $http->Response['body']);
    }

    /**
     * @dataProvider fixedUserAgents
     */
    public function testRestoreFixedUserAgent(string $stateAgent, string $restoredAgent)
    {
        $http = new \HttpBrowser("none", new \CurlDriver());
        $http->setKeepUserAgent(true);
        $state = $http->GetState();
        $state['UserAgent'] = $stateAgent;
        $http->setUserAgent('someUA');
        $http->SetState($state);
        $this->assertEquals($restoredAgent, $http->getDefaultHeader('User-Agent'));
    }

    public function testDoNotRestoreUserAgent()
    {
        $http = new \HttpBrowser("none", new \CurlDriver());
        $state = $http->GetState();
        $state['UserAgent'] = "SomeStoredAgent";
        $http->setUserAgent('SomeNewAgent');
        $http->SetState($state);
        $this->assertEquals("SomeNewAgent", $http->getDefaultHeader('User-Agent'));
    }

    private function configureHttp(\HttpBrowser $http)
    {
        $http->setKeepUserAgent(true);
        foreach (array_keys($http->getDefaultHeaders()) as $key) {
            if ($key !== 'User-Agent') {
                $http->unsetDefaultHeader($key);
            }
        }
    }

    public function fixedUserAgents()
    {
        return [
            ['public', \HttpBrowser::PUBLIC_USER_AGENT],
            ['proxy', \HttpBrowser::PROXY_USER_AGENT],
            ['someCustom', 'someCustom'],
        ];
    }


}