<?php

namespace AwardWallet\Common;

/**
 * @deprecated - use Strings class from AwardWallet/strings repo
 */
class Strings
{

    public static function cutInMiddle($s, $limit)
    {
        $len = strlen($s);
        if($len > $limit)
            return substr($s, 0, floor($limit / 2)  - 1) . ".." . $len . "ch..". substr($s, -(floor($limit / 2)  - 1));
        else
            return $s;
    }

    public static function getClassBaseName($object) : string
    {
        $class = get_class($object);
        return substr($class, strrpos($class, "\\") + 1);
    }

}
