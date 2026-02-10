<?php

namespace AwardWallet\WebdriverClient;

class Starter
{

    /**
     * @var NodeFinder
     */
    private $nodeFinder;
    /**
     * @var WebDriverStarter
     */
    private $webDriverStarter;

    public function __construct(NodeFinder $nodeFinder, WebDriverStarter $webDriverStarter)
    {

        $this->nodeFinder = $nodeFinder;
        $this->webDriverStarter = $webDriverStarter;
    }

    public function start(StartOptions $options): ?Session
    {
        $nodeAddress = $this->nodeFinder->getNode();

        if ($nodeAddress === null) {
            return null;
        }

        return new Session($this->webDriverStarter->start($nodeAddress, $options));
    }

}