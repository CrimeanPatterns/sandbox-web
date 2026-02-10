<?php

namespace AwardWallet\Common\Parsing;

class ParsingConstants
{

    private static $defined = false;

    public function __construct(array $params, string $checkerLogDir)
    {
        if (!self::$defined) {
            foreach ($params as $name => $value) {
                define(strtoupper($name), $value);
            }
            self::$defined = true;
        }

        require_once __DIR__ . '/../../old/constants.php';

        \TAccountChecker::$logDir = $checkerLogDir;
    }

}
