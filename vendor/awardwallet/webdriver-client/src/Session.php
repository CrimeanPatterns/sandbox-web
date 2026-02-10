<?php

namespace AwardWallet\WebdriverClient;

use Facebook\WebDriver\Remote\RemoteWebDriver;

class Session
{

    /**
     * @var RemoteWebDriver
     */
    private $webDriver;

    public function __construct(RemoteWebDriver $webDriver)
    {
        $this->webDriver = $webDriver;
    }

    /**
     * @return RemoteWebDriver
     */
    public function getWebDriver(): RemoteWebDriver
    {
        return $this->webDriver;
    }



}