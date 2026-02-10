<?php

namespace AwardWallet\Common\Tests;

class TestPathExtractor
{

    private const TEST_PATH =  __DIR__ . "/../../../../../tests";

    public static function getFileAndLine()
    {
        $testDir = realpath(self::TEST_PATH) . '/';
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT);
        $trace = array_values(array_filter($trace, function($frame) use ($testDir) {
            return isset($frame["file"]) && isset($frame["line"]) && strpos($frame["file"], $testDir) === 0 && substr($frame["file"], strlen($testDir), 1) !== "_";
        }));
        if(empty($trace))
            return null;
        return substr($trace[0]["file"], strlen($testDir)) . ':' . $trace[0]["line"];
    }

}