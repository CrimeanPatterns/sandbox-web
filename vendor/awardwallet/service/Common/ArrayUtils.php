<?php

namespace AwardWallet\Common;

class ArrayUtils
{

    public static function convertToKeyValue(array $rows, string $keyField, string $valueField) : array
    {
        $result = [];
        foreach ($rows as $row) {
            $result[$row[$keyField]] = $row[$valueField];
        }
        return $result;
    }

}