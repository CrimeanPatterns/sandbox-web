<?php


class SeleniumOptions
{

    const PROXY_CONTENT_STATIC = 'static';
    const PROXY_CONTENT_ALL = 'all';

    /**
     * @var string
     */
    public $startupText = 'new selenium session';
    /**
     * @var string
     */
    public $proxyHost;
    /**
     * @var int
     */
    public $proxyPort;
    /**
     * @var string
     */
    public $proxyUser;
    /**
     * @var string
     */
    public $proxyPassword;
    /**
     * @var string
     */
    public $proxyContent = self::PROXY_CONTENT_STATIC;
    /**
     * @var string
     */
    public $pacFile;
    /**
     * @var string
     */
    public $userAgent;
    /**
     * @var bool
     */
    public $showImages = true;
    /**
     * like [1024, 768]
     * @var array
     */
    public $resolution;
    /**
     * browser profile data - zip file contents (not base64 encoded)
     * @var string
     */
    public $profile;
    /**
     * SeleniumConnection context
     * @var array
     */
    public $connectionContext;
    /**
     * @var string 
     */
    public $timezone;
    /**
     * array of data from Fingerprint table, Fingerprint column
     * @var array
     */
    public $fingerprint;
    /**
     * add our extension for hiding selenium/webdriver
     * @var bool
     */
    public $addHideSeleniumExtension = true;
    /**
     * add evasions ported from https://github.com/berstend/puppeteer-extra/tree/master/packages/puppeteer-extra-plugin-stealth
     * @var bool
     */
    public $addPuppeteerStealthExtension = false;
    /**
     * @var FingerprintParams|null - set custom fingerprint params, usually to disable some evasions 
     */
    public $fingerprintParams = null;
    /**
     * record XHR requests
     * use browserCommunicator->getRecordedRequests() to get them later
     * @var bool
     */
    public $recordRequests = false;
    /**
     * @var bool
     * add https://anti-captcha.com/ru/apidoc/articles/how-to-integrate-the-plugin
     * for solving captchas in page
     */
    public $addAntiCaptchaExtension = false;
    /**
     * result of ProxyList::getCaptchaProxy method
     * used to solve captcha through anticaptcha, with addAntiCaptchaExtension = true  
     * @var null 
     */
    public $antiCaptchaProxyParams = null;

    /**
     * @var array - key, value, will be added to logs on selenium servers
     */
    public $loggingContext = [];
    /**
     * something like ['devices' => ['desktop'], 'operatingSystems' => ['macos']]
     * see https://github.com/apify/fingerprint-suite/
     * works only with puppeteer (chrome-95, chrome-94 (mac)))
     * see implementation in 
     * https://github.com/AwardWallet/selenium-puppeteer/blob/33440b2d5d33e608d3fc7bebb9b7c4cc8b2f55c8/server.js#L300-L300
     * @var array
     */
    public $fingerprintOptions;

    /**
     * @param string $startupText
     * @return SeleniumOptions
     */
    public function setStartupText(string $startupText): SeleniumOptions
    {
        $this->startupText = $startupText;
        return $this;
    }

    /**
     * @param string $proxyHost
     * @return SeleniumOptions
     */
    public function setProxyHost(string $proxyHost): SeleniumOptions
    {
        $this->proxyHost = $proxyHost;
        return $this;
    }

    /**
     * @param int $proxyPort
     * @return SeleniumOptions
     */
    public function setProxyPort(int $proxyPort): SeleniumOptions
    {
        $this->proxyPort = $proxyPort;
        return $this;
    }

    /**
     * @param string $proxyUser
     * @return SeleniumOptions
     */
    public function setProxyUser(string $proxyUser): SeleniumOptions
    {
        $this->proxyUser = $proxyUser;
        return $this;
    }

    /**
     * @param string $proxyPassword
     * @return SeleniumOptions
     */
    public function setProxyPassword(string $proxyPassword): SeleniumOptions
    {
        $this->proxyPassword = $proxyPassword;
        return $this;
    }

    /**
     * @param string $proxyContent
     * @return SeleniumOptions
     */
    public function setProxyContent(string $proxyContent): SeleniumOptions
    {
        $this->proxyContent = $proxyContent;
        return $this;
    }

    /**
     * @param string $pacFile
     * @return SeleniumOptions
     */
    public function setPacFile(string $pacFile): SeleniumOptions
    {
        $this->pacFile = $pacFile;
        return $this;
    }

    /**
     * @param string $userAgent
     * @return SeleniumOptions
     */
    public function setUserAgent(string $userAgent): SeleniumOptions
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    /**
     * @param bool $showImages
     * @return SeleniumOptions
     */
    public function setShowImages(bool $showImages): SeleniumOptions
    {
        $this->showImages = $showImages;
        return $this;
    }

    /**
     * @param array $resolution
     * @return SeleniumOptions
     */
    public function setResolution(array $resolution): SeleniumOptions
    {
        $this->resolution = $resolution;
        return $this;
    }

}
