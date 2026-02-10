<?php

namespace AwardWallet\Common\Selenium;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\DriverCommand;
use Facebook\WebDriver\Remote\HttpCommandExecutor;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\WebDriverCommand;
use Facebook\WebDriver\WebDriverCapabilities;

class DebugWebDriver extends RemoteWebDriver
{

    /**
     * @var ServerInfo
     */
    private $serverInfo;

    public function __construct(
        HttpCommandExecutor $commandExecutor = null,
                            $sessionId = null,
        WebDriverCapabilities $capabilities = null,
        $isW3cCompliant = false
    )
    {
        $this->executor = $commandExecutor;
        $this->sessionID = $sessionId;
        $this->isW3cCompliant = $isW3cCompliant;

        if ($capabilities !== null) {
            $this->capabilities = $capabilities;
        }
    }

    public function setServerInfo(ServerInfo $serverInfo)
    {
        $this->serverInfo = $serverInfo;
    }

    public static function createWithoutSession(
        $selenium_server_url = 'http://localhost:4444/wd/hub',
        $timeout_in_ms = 300000,
        ServerInfo $serverInfo
    )
    {
        $selenium_server_url = preg_replace('#/+$#', '', $selenium_server_url);

        $executor = new HttpCommandExecutor($selenium_server_url);
        if ($timeout_in_ms !== null) {
            $executor->setConnectionTimeout($timeout_in_ms);
            $executor->setRequestTimeout($timeout_in_ms);
        }

        $driver = new static();
        $driver->setServerInfo($serverInfo);
        $driver->setCommandExecutor($executor);

        return $driver;
    }

    public function createNewSession($desired_capabilities = null){
        $desired_capabilities = self::castToDesiredCapabilitiesObject($desired_capabilities);

        // W3C
        $parameters = [
            'capabilities' => [
                'firstMatch' => [(object) $desired_capabilities->toW3cCompatibleArray()],
            ],
        ];

        $parameters['desiredCapabilities'] = (object) $desired_capabilities->toArray();

        $command = new WebDriverCommand(
            null,
            DriverCommand::NEW_SESSION,
            $parameters
        );

        $response = $this->executor->execute($command);
        $value = $response->getValue();

        if (!$isW3cCompliant = isset($value['capabilities'])) {
            $this->executor->disableW3cCompliance();
        }

        if ($isW3cCompliant) {
            $returnedCapabilities = DesiredCapabilities::createFromW3cCapabilities($value['capabilities']);
        } else {
            $returnedCapabilities = new DesiredCapabilities($value);
        }

        $this->setSessionID($response->getSessionID());
        $this->capabilities = $returnedCapabilities;
        $this->isW3cCompliant = $isW3cCompliant;
    }

    public function getServerInfo() : ServerInfo
    {
        return $this->serverInfo;
    }

}
