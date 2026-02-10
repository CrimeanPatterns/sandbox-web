<?php

namespace App\Tests;

use AwardWallet\WebdriverClient\NodeFinder;
use AwardWallet\WebdriverClient\Starter;
use AwardWallet\WebdriverClient\StartOptions;
use AwardWallet\WebdriverClient\WebDriverStarter;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use PHPUnit\Framework\TestCase;

class StarterTest extends TestCase
{

    public function testNoNode()
    {
        $nodeFinder = $this->createMock(NodeFinder::class);
        $nodeFinder
            ->expects($this->once())->method('getNode')->willReturn(null);
        $webDriverStarter = $this->createMock(WebDriverStarter::class);
        $webDriverStarter
            ->expects($this->never())->method('start');
        $starter = new Starter($nodeFinder, $webDriverStarter);
        $session = $starter->start(new StartOptions(DesiredCapabilities::firefox()));
        $this->assertNull($session);
    }

    public function testNodeExists()
    {
        $nodeFinder = $this->createMock(NodeFinder::class);
        $nodeFinder
            ->expects($this->once())->method('getNode')->willReturn('1.2.3.4');

        $firefox = DesiredCapabilities::firefox();
        $startOptions = new StartOptions($firefox);
        $webDriverStarter = $this->createMock(WebDriverStarter::class);
        $webDriverStarter
            ->expects($this->once())->method('start')->with('1.2.3.4', $startOptions)->willReturn(RemoteWebDriver::createBySessionID('session-id', 'http://1.2.3.4:4444'));
        $starter = new Starter($nodeFinder, $webDriverStarter);
        $session = $starter->start($startOptions);
        $this->assertNotNull($session);
        $this->assertNotNull($session->getWebDriver());
    }

}