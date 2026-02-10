<?php

namespace AwardWallet\Common\Selenium\HotSession;

use Psr\Container\ContainerInterface;

abstract class KeepActiveHotConfig implements KeepActiveHotSessionInterface
{
    use \SeleniumCheckerHelper;
    /**
     * @var ContainerInterface
     */
    public $services;

    /**
     * @var \CheckerLogger
     */
    public $logger;

    private $httpLogDir;
    /**
     * @var \HttpBrowser
     */
    public $http;
    protected $parseMode;

    /**
     * KeepActiveHotConfig constructor.
     * @param ContainerInterface $services
     */
    public function __construct($parseMode)
    {
        $this->parseMode = $parseMode;
        $this->httpLogDir = \TAccountChecker::$logDir . "/tmp/logs/pid-" . getmypid() . "-" . sprintf("%03f", microtime(true));
    }

    public function getHttpLogDir(): string
    {
        return $this->httpLogDir;
    }

    public function getParseMode(): ?string
    {
        return $this->parseMode;
    }

}