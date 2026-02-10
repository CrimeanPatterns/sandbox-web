<?php
namespace tests\unit\old\browser;


use PHPUnit\Framework\TestCase;

class Http2Test extends TestCase {

    /**
     * @var \HttpBrowser
     */
    protected $browser;

    public function setUp(){
        parent::setUp();
        $this->browser = new \HttpBrowser("none", new \CurlDriver());
        $this->browser->RetryCount = 0;
    }

    public function testHttp2(){
        $this->browser->userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.181 Safari/537.36';
        $this->browser->setHttp2(true);
        $this->browser->GetURL("https://www.tunetheweb.com/performance-test/");
        $this->assertContains("wrench-icon", $this->browser->Response['body']);
        $this->assertContains("HTTP/2", $this->browser->Response['rawHeaders']);
    }

}