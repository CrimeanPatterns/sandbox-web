<?php

namespace AwardWallet\WebdriverClient;

use Facebook\WebDriver\Remote\DesiredCapabilities;

class StartOptions
{

    /**
     * @var DesiredCapabilities
     */
    private $desiredCapabilities;

    public function __construct(DesiredCapabilities $desiredCapabilities)
    {
        $this->desiredCapabilities = $desiredCapabilities;
    }

    /**
     * @return DesiredCapabilities
     */
    public function getDesiredCapabilities(): DesiredCapabilities
    {
        return $this->desiredCapabilities;
    }

}