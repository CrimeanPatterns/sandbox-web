<?php

use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Type;


class SeleniumConnection
{

    /**
     * @var RemoteWebDriver
     * @Exclude
     */
    private $webDriver;
    /**
     * @var string
     * @Type("string")
     */
    private $host;
    /**
     * @var string
     * @Type("string")
     */
    private $sessionId;
    /**
     * @var int
     * @Type("int")
     */
    private $port;
    /**
     * @var string
     * @Type("string")
     */
    private $share;
    /**
     * @var array
     * @Type("array<string,string>")
     */
    private $context = [];
    /**
     * @var string
     * @Type("string")
     */
    private $browserFamily;
    /**
     * @var int
     * @Type("int")
     */
    private $browserVersion;
    /**
     * @var string
     * @Type("string")
     */
    private $path;
    /**
     * @var int|null
     * @Type("int")
     */
    private $startTime;

    /**
     * @param RemoteWebDriver $webDriver
     */
    public function __construct($webDriver, string $sessionId, string $host, int $port, string $path, string $share, string $browserFamily, int $browserVersion, array $context, ?int $startTime = null)
    {
        $this->webDriver = $webDriver;
        $this->sessionId = $sessionId;
        $this->host = $host;
        $this->port = $port;
        $this->share = $share;
        $this->context = $context;
        $this->browserFamily = $browserFamily;
        $this->browserVersion = $browserVersion;
        $this->path = $path;
        $this->startTime = $startTime;

        if ($this->startTime === null) {
            $this->startTime = time();
        }
    }

    /**
     * @return RemoteWebDriver
     */
    public function getWebDriver()
    {
        return $this->webDriver;
    }

    /**
     * @param RemoteWebDriver $webDriver
     */
    public function setWebDriver($webDriver): void
    {
        $this->webDriver = $webDriver;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getShare(): string
    {
        return $this->share;
    }

    public function getSeleniumEndpoint() : string
    {
        return "http://{$this->host}:{$this->port}{$this->path}";
    }

    public function getAwEndpoint() : string
    {
        $port = round($this->port / 10) * 10 + 8;
        return "http://{$this->host}:{$port}";
    }

    public function getContext() : array
    {
        return $this->context;
    }

    public function addContext(string $key, string $value) {
        $this->context[$key] = $value;
    }

    public function getBrowserFamily(): string
    {
        return $this->browserFamily;
    }

    public function getBrowserVersion(): int
    {
        return $this->browserVersion;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getStartTime(): ?int
    {
        return $this->startTime;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

}