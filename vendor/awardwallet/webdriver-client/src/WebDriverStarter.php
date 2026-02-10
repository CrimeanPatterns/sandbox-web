<?php

namespace AwardWallet\WebdriverClient;

use Facebook\WebDriver\Remote\RemoteWebDriver;

class WebDriverStarter
{

    public function start(string $nodeAddress, StartOptions $startOptions) : RemoteWebDriver
    {
        return RemoteWebDriver::create($nodeAddress . ':4444/wd/hub', $startOptions->getDesiredCapabilities());
    }

}