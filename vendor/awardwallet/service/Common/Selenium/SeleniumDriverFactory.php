<?php

namespace AwardWallet\Common\Selenium;

use AwardWallet\Common\Selenium\HotSession\HotPoolManagerInterface;
use Psr\Log\LoggerInterface;

class SeleniumDriverFactory
{

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var \SeleniumConnector
     */
    private $seleniumConnector;
    /**
     * @var \CurlDriver
     */
    private $curlDriver;
    /**
     * @var HotPoolManager
     */
    private $hotPoolManager;
    /**
     * @var HotPoolManagerInterface
     */
    private $hotSessionPoolManager;

    public function __construct(
        LoggerInterface $logger,
        \SeleniumConnector $seleniumConnector,
        \CurlDriver $curlDriver,
        HotPoolManager $hotPoolManager,
        HotPoolManagerInterface $hotSessionPoolManager
    )
    {

        $this->logger = $logger;
        $this->seleniumConnector = $seleniumConnector;
        $this->curlDriver = $curlDriver;
        $this->hotPoolManager = $hotPoolManager;
        $this->hotSessionPoolManager = $hotSessionPoolManager;
    }

    public function getDriver(
        \SeleniumFinderRequest $finderRequest,
        \SeleniumOptions $seleniumOptions,
        ?LoggerInterface $logger
    ): \SeleniumDriver
    {
        return new \SeleniumDriver(
            $logger ?? $this->logger,
            $this->seleniumConnector,
            $finderRequest,
            $seleniumOptions,
            new FirefoxProfileManager($this->curlDriver, $this->logger),
            new DownloadManager($this->curlDriver, $this->logger),
            'http://awardwallet-browser-control.s3.amazonaws.com/extension-control.html',
            $this->hotPoolManager,
            $this->hotSessionPoolManager
        );
    }

}
