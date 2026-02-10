<?php


namespace AwardWallet\Common\Parser\Util;


class NameHelper
{

    public static function removePrefix(string $name)
    {
        return preg_replace('/^(Mr|Mrs|Ms|Miss|Mstr|Dr|Master)[.\s]\s*/i', '', trim($name));
    }

}