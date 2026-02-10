<?php

namespace AwardWallet\Common\Selenium;

class FingerprintRequest
{
    /**
     * like 'Win32', 'Win64', 'MacIntel', 'Linux x86_64', 'Linux armv7l'
     * @var string
     */
    public $platform;
    /**
     * like, 'chrome', 'firefox', 'safari'
     * @var string
     */
    public $browserFamily;
    /**
     * select browsers with version greater or equal this value
     * @var int
     */
    public $browserVersionMin;
    /**
     * select browsers with version lower or equal this value
     * @var int
     */
    public $browserVersionMax;
    /**
     * select only mobile browsers. you could set to null to return all browsers.
     * @var bool
     */
    public $isMobile = false;

    public static function chrome() : self
    {
        $result = new static();
        $result->browserFamily = 'chrome';
        return $result;
    }

    public static function firefox() : self
    {
        $result = new static();
        $result->browserFamily = 'firefox';
        return $result;
    }

    public static function safari() : self
    {
        $result = new static();
        $result->browserFamily = 'safari';
        return $result;
    }

    public static function mac() : self
    {
        $result = new static();
        $result->platform = 'MacIntel';
        return $result;
    }

}
