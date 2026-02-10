<?php


class SeleniumArrayFinder implements SeleniumFinderInterface
{

    /**
     * @var SeleniumServer[]
     */
    private $servers;

    /**
     * @param SeleniumServer[] $servers
     */
    public function __construct(array $servers)
    {
        $this->servers = $servers;
    }

    /**
     * @return SeleniumServer[]
     */
    public function getServers(SeleniumFinderRequest $request) : array
    {
        return $this->servers;
    }

}