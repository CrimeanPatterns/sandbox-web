<?php


class SeleniumSession
{
    /**
     * @var SeleniumOptions
     */
    private $seleniumOptions;
    /**
     * @var string
     */
    private $path;

    /**
     * @return string
     */
    public function getSessionId(): string
    {
        return $this->sessionId;
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

    /**
     * @var string
     */
    private $sessionId;
    /**
     * @var string
     */
    private $host;
    /**
     * @var int
     */
    private $port;
    /**
     * @var string
     */
    private $share;
    /**
     * @var array
     */
    private $context;

    public function __construct(string $sessionId, string $host, int $port, string $path, string $share, array $context, SeleniumOptions $seleniumOptions)
    {
        $this->sessionId = $sessionId;
        $this->host = $host;
        $this->port = $port;
        $this->share = $share;
        $this->seleniumOptions = $seleniumOptions;
        $this->path = $path;
        $this->context = $context;
    }

    /**
     * @return SeleniumOptions
     */
    public function getSeleniumOptions(): SeleniumOptions
    {
        return $this->seleniumOptions;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getContext(): array
    {
        return $this->context;
    }

}