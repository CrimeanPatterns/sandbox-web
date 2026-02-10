<?php

class SeleniumSingleServerFinder implements \SeleniumFinderInterface
{

    /**
     * @var string
     */
    private $server;

    public function __construct(string $server)
    {
        $this->server = $server;
    }

    public function getServers(SeleniumFinderRequest $request): array
    {
        // selenoid
        if ($request->getVersion() >= 100) {
            return [new SeleniumServer($this->server, 4444)];
        }

        if ($request->getBrowser() === SeleniumFinderRequest::BROWSER_FIREFOX) {
            return [new SeleniumServer($this->server, (int)('11'.$request->getVersion().'4'))];
        }

        if ($request->getBrowser() === SeleniumFinderRequest::BROWSER_CHROMIUM) {
            return [new SeleniumServer($this->server, (int)('13'.$request->getVersion().'4'))];
        }

        if ($request->getBrowser() === SeleniumFinderRequest::BROWSER_CHROME) {
            return [new SeleniumServer($this->server, (int)('12'.$request->getVersion().'4'))];
        }

        if ($request->getBrowser() === SeleniumFinderRequest::BROWSER_CHROME_PUPPETEER) {
            return [new SeleniumServer($this->server, (int)('14' . sprintf("%02d", $request->getVersion()) . '4'))];
        }

        throw new \Exception("Unknown browser requested: {$request->getBrowser()}-{$request->getVersion()}");
    }

    /**
     * @internal
     * @param string $server
     * @return $this
     */
    public function setServer(string $server)
    {
        $this->server = $server;
        return $this;
    }

}
