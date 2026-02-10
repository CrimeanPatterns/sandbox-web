<?php

class SeleniumServer
{

    /**
     * @var string
     */
    public $host;
    /**
     * @var int
     */
    public $port;

    public function __construct(string $host, int $port)
    {
        $this->host = $host;
        $this->port = $port;
    }

}