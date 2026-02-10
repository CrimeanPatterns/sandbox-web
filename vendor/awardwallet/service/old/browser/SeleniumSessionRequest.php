<?php


class SeleniumSessionRequest
{

    /**
     * @var \Facebook\WebDriver\Remote\DesiredCapabilities
     */
    private $capabilities;
    /**
     * @var Callable
     */
    private $onDriverCreated;
    /** @var array */
    private $context = [];

    public function __construct(\Facebook\WebDriver\Remote\DesiredCapabilities $capabilities, ?Callable $onDriverCreated, array $context)
    {
        $this->capabilities = $capabilities;
        $this->onDriverCreated = $onDriverCreated;
        $this->context = $context;
    }

    /**
     * @return \Facebook\WebDriver\Remote\DesiredCapabilities
     */
    public function getCapabilities(): \Facebook\WebDriver\Remote\DesiredCapabilities
    {
        return $this->capabilities;
    }

    /**
     * @return Callable
     */
    public function getOnDriverCreated(): ?Callable
    {
        return $this->onDriverCreated;
    }

    public function getContext() : array
    {
        return $this->context;
    }

}