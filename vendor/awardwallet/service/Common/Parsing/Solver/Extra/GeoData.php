<?php


namespace AwardWallet\Common\Parsing\Solver\Extra;


use DateTime;
use DateTimeZone;
use Exception;

class GeoData {

	public $name;
	public $address;
	public $city;
	public $state;
	public $country;
	public $countryCode;
	public $zip;
	public $lat;
	public $lng;
	public $tz;
	public $tzName;

	private static $tzVariants = [
	    ['Europe/Kyiv', 'Europe/Kiev']
    ];

	public static function fromArray($arr) {
	    $arr = array_merge([
            'Name' => null,
            'AddressLine' => null,
            'City' => null,
            'State' => null,
            'Country' => null,
            'CountryCode' => null,
            'PostalCode' => null,
            'Lat' => null,
            'Lng' => null,
            'TimeZoneLocation' => null,
        ], $arr);
		$new = new self();
		$new->name = $arr['Name'];
		$new->address = $arr['AddressLine'];
		$new->city = $arr['City'];
		$new->state = $arr['State'];
		$new->country = $arr['Country'];
		$new->countryCode = $arr['CountryCode'];
		$new->zip = $arr['PostalCode'];
		$new->lat = $arr['Lat'];
		$new->lng = $arr['Lng'];
		$arr['TimeZoneLocation'] = self::fixTzName($arr['TimeZoneLocation']);

        try {
            $tz = new DateTimeZone($arr['TimeZoneLocation']);
            $new->tz = $tz->getOffset(new DateTime());
        }
        catch(Exception $e) {
            $new->tz = 0;
        }

        $new->tzName = $arr['TimeZoneLocation'];

        return $new;
	}

	public static function fixTzName($tzName)
    {
        foreach(self::$tzVariants as $variants) {
            if (in_array($tzName, $variants)) {
                foreach($variants as $variant) {
                    try {
                        new DateTimeZone($variant);
                        return $variant;
                    }
                    catch (Exception $e) {}
                }
            }
        }
        return $tzName;
    }

}