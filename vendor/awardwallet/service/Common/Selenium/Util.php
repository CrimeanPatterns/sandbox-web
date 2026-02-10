<?php

namespace AwardWallet\Common\Selenium;

class Util
{

    public static function cleanupCurlError(string $error) : string
    {
        $startPos = strpos($error, '{');
        $endPos = strrpos($error, '}');

        if ($startPos === false || $endPos == false) {
            return $error;
        }

        // strip out sensitive information, like proxy password from errors like:
        // Curl error thrown for http POST to /session with params: {"desiredCapabilities":{"browserName":"chrome-extension","platform":"ANY" ... } Failed to connect to host.docker.internal port 4444: Connection refused
        return substr($error, 0, $startPos) . '{ ... }' . substr($error, $endPos + 1);
    }

}