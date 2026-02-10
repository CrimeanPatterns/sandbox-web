<?php

namespace AwardWallet\Common;

class UrlBase64
{

    public static function encode(string $text) : string
    {
        return rtrim(strtr(base64_encode($text), '+/=', '._-'), '-');
    }

    public static function decode(string $text) : string
    {
        return base64_decode(strtr($text, '._-', '+/='));
    }

}