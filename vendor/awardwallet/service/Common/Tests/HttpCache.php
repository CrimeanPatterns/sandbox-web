<?php

namespace AwardWallet\Common\Tests;

class HttpCache
{

    /**
     * @var array
     */
    public static $mockedResponses = [];

    public static function load(string $file)
    {
        self::$mockedResponses = [];
        if (file_exists($file)) {
            self::$mockedResponses = json_decode(file_get_contents($file), true);
            self::$mockedResponses = array_map(function ($item) {
                if (is_string($item)) {
                    return ["response" => $item, "file" => null, "version" => 1];
                } else {
                    return $item;
                }
            }, self::$mockedResponses);
        }
    }

    /**
     * @return bool - file was changed
     */
    public static function save(string $file) : bool
    {
        if (count(self::$mockedResponses) === 0) {
            return false;
        }

        $result = false;
        ksort(self::$mockedResponses);
        $newContent = json_encode(self::$mockedResponses, JSON_PRETTY_PRINT);
        if (!file_exists($file) || file_get_contents($file) !== $newContent) {
            $result = true;
        }

        file_put_contents(
            $file,
            $newContent
        );

        return $result;
    }

}