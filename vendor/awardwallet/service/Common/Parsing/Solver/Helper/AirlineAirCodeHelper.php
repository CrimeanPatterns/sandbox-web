<?php


namespace AwardWallet\Common\Parsing\Solver\Helper;


class AirlineAirCodeHelper
{

    private static $airports = [
        "ABUJA" => ["U5" => "ABV"],
        "ANAMBRA" => ["U5" => "ANA"],
        "ASABA" => ["U5" => "ABB"],
        "ENUGU" => ["U5" => "ENU"],
        "LAGOS" => ["U5" => "LOS"],
        "OWERRI" => ["U5" => "QOW"],
        "PORT HARCOURT" => ["U5" => "PHC"],
        "WARRI" => ["U5" => "QRW"],
        "YENAGOA" => ["U5" => "BIA"],
    ];
    
    public static function lookup(string $name, string $airline): ?string
    {
        return self::$airports[strtoupper($name)][strtoupper($airline)] ?? null;
    }

}