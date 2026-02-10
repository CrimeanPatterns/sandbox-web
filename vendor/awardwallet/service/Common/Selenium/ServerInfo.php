<?php

namespace AwardWallet\Common\Selenium;

class ServerInfo
{

    /**
     * @var \SeleniumFinderRequest
     */
    private $request;

    public function __construct(\SeleniumFinderRequest $request)
    {
        $this->request = $request;
    }

    public function isMouseOffetFromCenter() : bool
    {
        if (in_array($this->request->getBrowserName(), ['chromium-80', 'chrome-94', 'chrome-95'])) {
            return false;
        }

        // puppeteer-based
        if (in_array($this->request->getBrowser(), [\SeleniumFinderRequest::BROWSER_CHROME_PUPPETEER, \SeleniumFinderRequest::BROWSER_FIREFOX_PLAYWRIGHT])) {
            return false;
        }

        // test results
        // firefox-84: center, 3.8.1
        // firefox-100: center

        // chrome-84: center
        // chrome-95: left-top, puppeteer
        // chrome-99: center, 4.1.3
        // chrome-100: center

        // chromium-80: left-top, 3.9.0

        // chrome-puppeteer-103: left-top, puppeteer
        // firefox-playwright-100: left-top, puppeteer

        return true;
    }

}