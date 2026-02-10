<?php

namespace AwardWallet\ExtensionWorker;

class CookieParser
{

    public static function parseCookieString($cookieString) : array
    {
        $cookies = array();
        $parts = explode(';', $cookieString);

        foreach ($parts as $part) {
            $cookie = explode('=', trim($part));
            $name = $cookie[0];
            $value = isset($cookie[1]) ? urldecode($cookie[1]) : '';
            $cookies[$name] = $value;
        }

        return $cookies;
    }
}