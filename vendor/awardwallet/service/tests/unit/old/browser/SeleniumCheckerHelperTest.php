<?php

namespace tests\unit\old\browser;

use PHPUnit\Framework\TestCase;

class SeleniumCheckerHelperTest extends TestCase
{

    public function testKeepState()
    {
        require_once __DIR__ . '/../../../../old/constants.php';

        \TAccountChecker::$logDir = '/tmp/logs';

        $checker = new class extends \TAccountChecker {
            use \SeleniumCheckerHelper;

            public function testFunction()
            {
                $this->http->setUserAgent('SomeAgent1');
                $this->UseSelenium();
            }

        };

        $checker->setCurlDriver($this->createMock(\CurlDriver::class));
        $checker->InitBrowser();
        $checker->testFunction();
        $this->assertEquals('SomeAgent1', $checker->http->userAgent);
    }

}