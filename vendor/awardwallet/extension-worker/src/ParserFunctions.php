<?php

namespace AwardWallet\ExtensionWorker;

// dummy class for functions autoloading
class ParserFunctions {

    public static function load() : void
    {

    }

}

/**
 * @returns string - text matching pattern or null. if pattern contains groups, text from first group will be returned
 */
function preg(string $pattern, string $subject) : ?string
{
    if (!preg_match($pattern, $subject, $matches)) {
        return null;
    }

    if (count($matches) > 1) {
        return $matches[1];
    }

    return $matches[0];
}

function beautifulName(string $s) : string
{
    $s = str_replace(array("-", "'", "/", ".",","), array(" - ", " ' ", " / ", " . ", " , "), $s);
    $s = mb_convert_case($s, MB_CASE_TITLE, "UTF-8");
    $s = str_replace(array(" - ", " ' ", " / ", " . ", " , "), array("-", "'", "/", ".",","), $s);

    return $s;
}
