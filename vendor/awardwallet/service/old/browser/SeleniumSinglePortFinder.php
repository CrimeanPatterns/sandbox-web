<?php

class SeleniumSinglePortFinder implements \SeleniumFinderInterface
{

    /**
     * @var string
     */
    private $server;

    public function __construct(string $server, int $port)
    {
        $this->server = $server;
        $this->port = $port;
    }

    public function getServers(SeleniumFinderRequest $request): array
    {
        return [new SeleniumServer($this->server, (int)$this->port)];
    }

}
