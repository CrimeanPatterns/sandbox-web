<?php


class SeleniumFinderRequest
{

    const BROWSER_FIREFOX = 'firefox';
    const BROWSER_CHROMIUM = 'chromium';
    const BROWSER_CHROME = 'chrome';
    const BROWSER_CHROME_PUPPETEER = 'chrome-puppeteer';
    const BROWSER_FIREFOX_PLAYWRIGHT = 'firefox-playwright';
    const BROWSER_CHROME_PLAYWRIGHT = 'chrome-playwright';
    const BROWSER_BRAVE_PLAYWRIGHT = 'brave-playwright';
    const BROWSER_CHROME_EXTENSION = 'chrome-extension';

    const FIREFOX_DEFAULT = '100';
    const FIREFOX_53 = '53';
    const FIREFOX_59 = '59';
    const FIREFOX_84 = '84';
    const FIREFOX_100 = '100';

    const CHROMIUM_DEFAULT = '80';
    const CHROMIUM_80 = '80';

    const CHROME_DEFAULT = '100';
    const CHROME_84 = '84';
    /** mac */
    const CHROME_94 = '94';
    const CHROME_95 = '95';
    const CHROME_99 = '99';
    const CHROME_100 = '100';

    const CHROME_PUPPETEER_DEFAULT = '103';
    const CHROME_PUPPETEER_100 = '100';
    const CHROME_PUPPETEER_103 = '103';

    const CHROME_EXTENSION_103 = '103';
    const CHROME_EXTENSION_DEFAULT = '103';

    const FIREFOX_PLAYWRIGHT_DEFAULT = '101';
    const FIREFOX_PLAYWRIGHT_100 = '100';
    const FIREFOX_PLAYWRIGHT_101 = '101';
    
    const CHROME_PLAYWRIGHT_DEFAULT = '101';
    const BRAVE_PLAYWRIGHT_DEFAULT = '101';

    const BROWSER_VERSIONS = [
        self::BROWSER_FIREFOX => [self::FIREFOX_DEFAULT, self::FIREFOX_53, self::FIREFOX_59, self::FIREFOX_84, self::FIREFOX_100],
        self::BROWSER_CHROMIUM => [self::CHROMIUM_DEFAULT, self::CHROMIUM_80],
        self::BROWSER_CHROME => [self::CHROME_DEFAULT, self::CHROME_84, self::CHROME_94, self::CHROME_95, self::CHROME_99, self::CHROME_100],
        self::BROWSER_CHROME_PUPPETEER => [self::CHROME_PUPPETEER_100, self::CHROME_PUPPETEER_103],
        self::BROWSER_FIREFOX_PLAYWRIGHT => [self::FIREFOX_PLAYWRIGHT_100, self::FIREFOX_PLAYWRIGHT_101],
        self::BROWSER_CHROME_PLAYWRIGHT => [self::CHROME_PLAYWRIGHT_DEFAULT],
        self::BROWSER_BRAVE_PLAYWRIGHT => [self::BRAVE_PLAYWRIGHT_DEFAULT],
        self::BROWSER_CHROME_EXTENSION => [self::CHROME_EXTENSION_103],
    ];
    
    public const OS_WINDOWS = 'windows';
    public const OS_MAC = 'mac';
    public const OS_MAC_M1 = 'mac-m1';
    public const OS_LINUX = 'linux';
    
    public const OSES = [
        self::OS_MAC,
        self::OS_MAC_M1,
        self::OS_WINDOWS,
        self::OS_LINUX,
    ]; 

    /**
     * see BROWSER_ constants
     * @var string
     */
    private $browser;
    /**
     * @var string
     */
    private $version;
    /**
     * @var string
     */
    private $os;
    /**
     * @var int
     */
    private $hotPoolSize;
    /**
     * @var string
     */
    private $hotPoolPrefix;
    /**
     * @var int
     */
    private $hotIndex;
    /**
     * @var string
     */
    private $hotAccountKey;
    /**
     * @var string
     */
    private $hotProvider;
    /**
     * @var string
     */
    private $serverHost;
    /**
     * @var bool
     */
    private $isBackground;

    /**
     * see BROWSER_ constants
     */
    public function getBrowser() : string
    {
        return $this->browser;
    }

    public function getVersion() : string
    {
        return $this->version;
    }

    public function __construct($browser = null, $version = null, $os = null)
    {
        $this->request($browser, $version, $os);
    }

    public function request($browser = null, $version = null, $os = null)
    {
        if(empty($browser)) {
            $browser = self::BROWSER_FIREFOX;
        }
        
        if(!array_key_exists($browser, self::BROWSER_VERSIONS)) {
            throw new \Exception("Invalid browser: $browser");
        }

        if($version === null) {
            $version = self::BROWSER_VERSIONS[$browser][0];
        }
        
        if(!in_array($version, self::BROWSER_VERSIONS[$browser])) {
            throw new \Exception("Invalid browser version: {$browser}-{$version}");
        }

        if($os !== null && !in_array($os, self::OSES)) {
            throw new \Exception("Invalid os: $os");
        }

        $this->browser = $browser;
        $this->version = $version;
        $this->os = $os;
    }

    public function getBrowserName() : string
    {
        return $this->browser . "-" . $this->version;
    }

    public function getOs(): ?string
    {
        return $this->os;
    }

    /**
     * @param string $os
     */
    public function setOs(?string $os): self
    {
        $this->os = $os;

        return $this;
    }

    public function IsBackground(): bool
    {
        return $this->isBackground ?? false;
    }

    public function setIsBackround(): self
    {
        $this->isBackground = true;

        return $this;
    }

    /**
     * @deprecated The setHotPool is deprecated and will be removed in next version. Please use the setHotSessionPool method instead.
     */
    public function setHotPool(string $prefix, ?int $poolSize = 1000) : self
    {
        $this->hotPoolPrefix = $prefix;
        $this->hotPoolSize = $poolSize;

        return $this;
    }

    public function getHotPoolSize(): ?int
    {
        return $this->hotPoolSize;
    }

    public function getHotPoolPrefix(): ?string
    {
        return $this->hotPoolPrefix;
    }

    public function setHotSessionPool(string $prefix, string $provider, ?string $accountKey = null) : self
    {
        $this->hotPoolPrefix = $prefix;
        $this->hotProvider = $provider;
        $this->hotAccountKey = $accountKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getHotAccountKey(): ?string
    {
        return $this->hotAccountKey;
    }

    /**
     * @return string
     */
    public function getHotProvider(): ?string
    {
        return $this->hotProvider;
    }

    /**
     * use only this server
     */
    public function setServerHost(?string $host) : void
    {
        $this->serverHost = $host;
    }

    public function getServerHost() : ?string
    {
        return $this->serverHost;
    }

}
