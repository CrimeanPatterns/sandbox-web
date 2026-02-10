<?php


namespace AwardWallet\Common;


class CurrencyUtils
{

    private static $coeffs = [
        'USD' => 1,
        'CAD' => .75,
        'AUD' => .71,
        'EUR' => 1.12,
        'GBP' => 1.31,
        'COP' => .00032,
        'THB' => .031,
        'MXN' => .052,
        'JPY' => .009,
        'CNY' => .15,
        'RUB' => .015,
        'BHD' => 2.65,
        'OMR' => 2.6,
        'JOD' => 1.41,
        'KWD' => 3.28,
        'NIO' => .03,
        'PHP' => .02,

    ];

    public static function estimate($value, $sourceCurrency) {
        return isset(self::$coeffs[$sourceCurrency]) ? $value * self::$coeffs[$sourceCurrency] : null;
    }

}