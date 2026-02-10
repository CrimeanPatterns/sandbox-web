<?php

namespace AwardWallet\Common\Parser\Util;


class PriceHelper {

    public static $threeDigitCurrencies = [
        'BHD',
        'IQD',
        'JOD',
        'KWD',
        'LYD',
        'OMR',
        'TND',
    ];

    public static function cost($str, $thousands = ',', $decimals = '.')
    {
        if ($thousands === $decimals)
            return null;
        $str = trim(preg_replace('/\s+/', '', $str));
        $decimals = preg_quote($decimals);
        if (preg_match("#$decimals\d+$#", $str)) {
            // .7 -> 0.7
            $str = '0' . $str;
        }
        if (!preg_match('/^\d/', $str))
            return null;

        $thousands = preg_quote($thousands);
        $thousandsRe = "/$thousands(\d{3})/";
        $str = preg_replace($thousandsRe, '\1', $str);

        $decimalsPresent = preg_match('/[^\d]\d+$/', $str);
        $decimalsRe = "/$decimals(\d+)$/";
        if ($decimalsPresent && !preg_match($decimalsRe, $str))
            return null;
        $str = preg_replace($decimalsRe, '.\1', $str);

        if (is_numeric($str))
            return (float) $str;
        return null;
    }

    public static function is3digit(string $currency): bool
    {
        return in_array($currency, self::$threeDigitCurrencies);
    }

    private static function areGroupsValid(string $str, ?string $thousand, ?string $decimal, ?string $currency): bool
    {
        if ($decimal) {
            list($str, $fraction) = explode($decimal, $str);
            if (strlen($fraction) > (!isset($currency) || self::is3digit($currency) ? 3 : 2) || strlen($fraction) < 1 || strlen($str) < 1) {
                return false;
            }
        }
        if ($thousand) {
            $groups = explode($thousand, $str);
            foreach ($groups as $i => $group) {
                if ($i !== 0 && strlen($group) !== 3 || $i === 0 && (strlen($group) < 1 || strlen($group) > 3)) {
                    return false;
                }
            }
        }
        return true;
    }

    public static function parse($str, $currencyCode = null)
    {
        if (!is_string($str))
            return $str;
        $str = trim($str);
        if (preg_match_all('/\D/u', $str, $matches) == 0) {
            return $str;
        }
        $separators = $matches[0];
        if (count($separators) === 0) {
            return $str;
        }
        $filtered = $occurrences = [];
        foreach($separators as $separator) {
            if (empty($filtered) || $filtered[count($filtered) - 1] !== $separator) {
                $filtered[] = $separator;
            }
            if (!isset($occurrences[$separator])) {
                $occurrences[$separator] = 0;
            }
            $occurrences[$separator]++;
        }
        switch(count($filtered)) {
            case 2:
                list($thousand, $decimal) = $filtered;
                if ($occurrences[$decimal] > 1 || !self::areGroupsValid($str, $thousand, $decimal, $currencyCode)) {
                    return null;
                }
                return str_replace($decimal, '.', str_replace($thousand, '', $str));
            case 1:
                $separator = $filtered[0];
                if ($occurrences[$separator] > 1) {
                    return self::areGroupsValid($str, $separator, null, $currencyCode) ? str_replace($separator, '', $str) : null;
                }
                if (strpos($str, $separator) === 0 && in_array($separator, [',', '.'])) {
                    $str = '0' . $str;
                    return self::areGroupsValid($str, null, $separator, $currencyCode) ? str_replace($separator, '.', $str) : null;
                }
                list($whole, $fraction) = explode($separator, $str);
                if (strlen($whole) > 3) {
                    return self::areGroupsValid($str, null, $separator, $currencyCode) ? str_replace($separator, '.', $str) : null;
                }
                if (strlen($fraction) < 3 || strlen($fraction) === 3 && null !== $currencyCode && self::is3digit($currencyCode)) {
                    return self::areGroupsValid($str, null, $separator, $currencyCode) ? str_replace($separator, '.', $str) : null;
                }
                return self::areGroupsValid($str, $separator, null, $currencyCode) ? str_replace($separator, '', $str) : null;
            case 0:
                return $str;
            default:
                return null;
        }
    }

}
