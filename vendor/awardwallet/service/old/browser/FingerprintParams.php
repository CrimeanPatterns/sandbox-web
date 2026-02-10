<?php

use Sinergi\BrowserDetector\Browser;

class FingerprintParams
{
    public $preinstalled = false;
    public $platform = "Win32";
    public $webglVendor = "Google Inc.";
    public $webglRenderer = "ANGLE (Microsoft Basic Render Driver Direct3D11 vs_5_0 ps_5_0)";
    public $brokenImageSize;
    public $hairline = false;
    public $fonts = [
        "Calibri",
        "Cambria",
        "Constantia",
        "Lucida Bright",
        "Georgia",
        "Segoe UI",
        "Candara",
        "Trebuchet MS",
        "Verdana",
        "Consolas",
        "Lucida Console",
        "Lucida Sans Typewriter",
        "Courier New",
        "Courier",
    ];
    public $firefox = false;
    /**
     * @var float
     */
    public $random;
    public $deviceMemory;
    public $webdriver;
    public $chrome = true;
    public $languages = ['en-US', 'en'];
    public $language = 'en-US';
    public $mimeTypes = [
        [
            "type" => 'application/pdf',
            "suffixes" => 'pdf',
            "description" => '',
            "__pluginName" => 'Chrome PDF Viewer',
        ],
        [
            "type" => 'application/x-google-chrome-pdf',
            "suffixes" => 'pdf',
            "description" => 'Portable Document Format',
            "__pluginName" => 'Chrome PDF Plugin',
        ],
        [
            "type" => 'application/x-nacl',
            "suffixes" => '',
            "description" => 'Native Client Executable',
            "enabledPlugin" => 'Plugin',
            "__pluginName" => 'Native Client',
        ],
        [
            "type" => 'application/x-pnacl',
            "suffixes" => '',
            "description" => 'Portable Native Client Executable',
            "__pluginName" => 'Native Client',
        ],
    ];
    public $plugins = [
        [
            "name" => 'Chrome PDF Plugin',
            "filename" => 'internal-pdf-viewer',
            "description" => 'Portable Document Format',
        ],
        [
            "name" => 'Chrome PDF Viewer',
            "filename" => 'mhjfbmdgcfjbbpaeojofohoefgiehjai',
            "description" => '',
        ],
        [
            "name" => 'Native Client',
            "filename" => 'internal-nacl-plugin',
            "description" => '',
        ],
    ];
    public $mockPlugins;
    public $mockPluginToString = false;
    public $oscpu;
    public $appVersion;
    public $doNotTrack;
    public $buildID;
    public $maxTouchPoints;
    public $hardwareConcurrency;
    public $mockPermissions = true;
    public $maskAudio = true;
    public $maskConsole = true;

    /**
     * @param string $browserFamily - one of SeleniumFinderRequest::BROWSER_
     */
    public function __construct(string $browserFamily, int $browserVersion, string $userAgent = null, ?array $fingerprint = null)
    {
        $browser = new Browser($userAgent);
        $browser->setVersion($browserVersion);
        $majorVersion = (int) round((float) $browser->getVersion());

        switch ($browserFamily) {
            case SeleniumFinderRequest::BROWSER_FIREFOX:
            case SeleniumFinderRequest::BROWSER_FIREFOX_PLAYWRIGHT:
                $browser->setName(Browser::FIREFOX);

                break;

            case SeleniumFinderRequest::BROWSER_CHROMIUM:
                $browser->setName('Chromium');

                break;

            case SeleniumFinderRequest::BROWSER_CHROME_PUPPETEER:
            case SeleniumFinderRequest::BROWSER_CHROME:
            case SeleniumFinderRequest::BROWSER_CHROME_EXTENSION:
                $browser->setName('Chrome');

                break;

            default:
                throw new \Exception("Unknown browser family: $browserFamily");
        }

        if ($browserFamily === SeleniumFinderRequest::BROWSER_CHROMIUM) {
            $this->hairline = true;
            $this->brokenImageSize = 16;
        }

        if ($browserFamily === SeleniumFinderRequest::BROWSER_FIREFOX) {
            $this->firefox = true;
            $this->webdriver = false;
            $this->chrome = false;
            $this->plugins = [
                [
                    "name" => 'Shockwave Flash',
                    "filename" => 'Flash Player.plugin',
                    "description" => 'Shockwave Flash 21.0 r0',
                ],
            ];
            $this->mimeTypes = [
                [
                    "type" => 'application/x-shockwave-flash"',
                    "suffixes" => 'swf',
                    "description" => 'Shockwave Flash',
                    "__pluginName" => 'Shockwave Flash',
                ],
                [
                    "type" => 'application/futuresplash',
                    "suffixes" => 'spl',
                    "description" => 'FutureSplash Player',
                    "__pluginName" => 'Shockwave Flash',
                ],
            ];
            $this->doNotTrack = "1";
            $this->mockPluginToString = true;
        }

        if ($userAgent !== null) {
            if (stripos($userAgent, "Macintosh") !== false) {
                $this->setupMac($userAgent, $browserFamily, $majorVersion);
            }

            if (stripos($userAgent, "Chrome") !== false) {
                $this->maxTouchPoints = 0;
                $this->hardwareConcurrency = 8;
            }

            if (stripos($userAgent, "Linux") !== false) {
//                $this->platform = "Linux x86_64";
                $this->webglVendor = "Intel Inc.";
                $this->webglRenderer = "Intel Iris OpenGL Engine";
            }
        }

        if (
            (($browser->getName() === Browser::CHROME || $browser->getName() === 'Chromium') && ($majorVersion >= 63 || $majorVersion == 0))
            || ($browser->getName() === Browser::OPERA && $majorVersion >= 50)
        ) {
            $this->deviceMemory = 8;
        }

        if (($browser->getName() === Browser::CHROME || $browser->getName() === 'Chromium') && $majorVersion >= 89) {
            // Prior to this change, Chromium only exposed `navigator.webdriver` when the browser was being automated.
            // However, other browsers expose it unconditionally per the spec (https://w3c.github.io/webdriver/#interface),
            // with the value `false` in case the browser is not being automated.
            // https://chromestatus.com/feature/5670121114697728
            $this->webdriver = false;
        }

        if ($fingerprint !== null) {
            $this->preinstalled = true;
            $this->applyFingerprint($fingerprint);
        }

        $this->random = random_int(0, 999999) / 1000000;
    }
    
    public static function vanillaFirefox() : self
    {
        $result = new self(SeleniumFinderRequest::BROWSER_FIREFOX, 92);
        
        $result->clear();
        $result->webdriver = false;
        
        return $result;
    }
    
    public static function none() : self
    {
        $result = new self(SeleniumFinderRequest::BROWSER_CHROME, 94);

        $result->clear();
        //$result->webdriver = true;

        return $result;
    }

    public function clear() : void
    {
        foreach ($this as $property => $value) {
            $this->$property = null;
        }
    }

    private function setupMac(string $userAgent, string $browserFamily, int $browserMajorVersion)
    {
        $this->platform = "MacIntel";
        $this->webglVendor = "ATI Technologies Inc.";
        $this->webglRenderer = "AMD Radeon R9 M370X OpenGL Engine";
        $this->fonts = [
            "Hoefler Text",
            "Monaco",
            "Georgia",
            "Trebuchet MS",
            "Verdana",
            "Andale Mono",
            "Monaco",
            "Courier New",
            "Courier",
        ];

        if (stripos($userAgent, "Chrome") === false && stripos($userAgent, "Safari") !== false) {
            $this->chrome = false;
            $this->hairline = true;
            $this->brokenImageSize = 20;
            $this->languages = ['en']; // en-gb?
            $this->language = 'en';
            $this->plugins = [
                [
                    "description" => '',
                    "filename" => '',
                    "name" => 'WebKit built-in PDF',
                ],
            ];
            $this->mimeTypes = [
                [
                    "description" => "Portable Document Format",
                    "suffixes" => "pdf",
                    "type" => "application/pdf",
                ],
                [
                    "description" => "Portable Document Format",
                    "suffixes" => "pdf",
                    "type" => "text/pdf",
                ],
                [
                    "description" => "PostScript",
                    "suffixes" => "ps",
                    "type" => "application/postscript",
                ],
            ];
            $this->mockPlugins = true;
            $this->mockPluginToString = true;
        }

        if (
            $browserFamily === SeleniumFinderRequest::BROWSER_FIREFOX
            && preg_match('#Intel Mac OS X \d+.\d+#ims', $userAgent, $matches)
        ) {
            $this->oscpu = $matches[0];
            $this->appVersion = "5.0 (Macintosh)";
        }

        if ($browserFamily === SeleniumFinderRequest::BROWSER_FIREFOX) {
            if ($browserMajorVersion == 59) {
                $this->buildID = "20180117222144";
            }
            $this->appVersion = "5.0 (Macintosh)";
        }
    }

    private function applyFingerprint(array $fp): void
    {
        if (isset($fp['fonts'])) {
            $this->fonts = $fp['fonts'];
        }

        if (isset($fp['platform'])) {
            $this->platform = $fp['platform'];
        }

        if (isset($fp['fp2']['webGL']['unmasked vendor'])) {
            $this->webglVendor = $fp['fp2']['webGL']['unmasked vendor'];
        }

        if (isset($fp['fp2']['webGL']['unmasked renderer'])) {
            $this->webglRenderer = $fp['fp2']['webGL']['unmasked renderer'];
        }

        if (isset($fp['fp2']['platform'])) {
            $this->platform = $fp['fp2']['platform'];
        }
    }
}
