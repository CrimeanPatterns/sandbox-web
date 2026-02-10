<?php

namespace AwardWallet\MainBundle\Service;

class ParsingConstants
{

    private static $defined = false;

    public function __construct(array $params)
    {
        if (!self::$defined) {
            foreach ($params as $name => $value) {
                define(strtoupper($name), $value);
            }
            self::$defined = true;
        }
    }

}