<?php


namespace AwardWallet\Common\FlightStats;

use JMS\Serializer\Annotation as JMS;


class Airport
{
    /**
     * The FlightStats code for the airport, globally unique across time.
     *
     * @var string
     * @JMS\Type("string")
     */
    private $fs;

    /**
     * The IATA code for the airport.
     *
     * @var string = null
     * @JMS\Type("string")
     */
    private $iata = null;

    /**
     * The ICAO code for the airport.
     *
     * @var string = null
     * @JMS\Type("string")
     */
    private $icao = null;

    /**
     * The FAA code for the airport.
     *
     * @var string = null
     * @JMS\Type("string")
     */
    private $faa = null;

    /**
     * The name of the airport.
     *
     * @var string = null
     * @JMS\Type("string")
     */
    private $name = null;

    /**
     * The street address of the airport, part 1.
     *
     * @var string = null
     * @JMS\Type("string")
     */
    private $street1 = null;

    /**
     * Street address of the airport, part 2.
     *
     * @var string = null
     * @JMS\Type("string")
     */
    private $street2 = null;

    /**
     * The city with which the airport is associated.
     *
     * @var string
     * @JMS\Type("string")
     */
    private $city;

    /**
     * The city code with which the airport is associated.
     *
     * @var string = null
     * @JMS\Type("string")
     */
    private $cityCode = null;

    /**
     * The State in which the airport is located.
     *
     * @var string = null
     * @JMS\Type("string")
     */
    private $stateCode = null;

    /**
     * The postal code in which the airport resides.
     *
     * @var string = null
     * @JMS\Type("string")
     */
    private $postalCode;

    /**
     * The code for the country in which the airport is located.
     *
     * @var string
     * @JMS\Type("string")
     */
    private $countryCode;

    /**
     * The name of the country in which the Airport is located.
     *
     * @var string
     * @JMS\Type("string")
     */
    private $countryName;

    /**
     * The name of the region in which the Airport is located.
     *
     * @var string
     * @JMS\Type("string")
     */
    private $regionName;

    /**
     * The name of the Time Zone region in which the Airport is located.
     *
     * @var string
     * @JMS\Type("string")
     */
    private $timeZoneRegionName;

    /**
     * The NOAA weather zone (US only) in which the Airport is located.
     *
     * @var string = null
     * @JMS\Type("string")
     */
    private $weatherZone = null;

    /**
     * The local time at the Airport when the request was made.
     *
     * @var string
     * @JMS\Type("string")
     */
    private $localTime;

    /**
     * The current UTC offset at the Airport when the request was made.
     *
     * @var double
     * @JMS\Type("double")
     */
    private $utcOffsetHours;

    /**
     * The latitude of the airport in decimal degrees.
     *
     * @var double
     * @JMS\Type("double")
     */
    private $latitude;

    /**
     * The longitude of the airport in decimal degrees.
     *
     * @var double
     * @JMS\Type("double")
     */
    private $longitude;

    /**
     * @var int
     * @JMS\Type("integer")
     */
    private $elevationFeet;

    /**
     * The FlightStats classification of the airport, 1(max) to 5(min).
     *
     * @var int
     * @JMS\Type("integer")
     */
    private $classification;

    /**
     * Boolean value indicating if the airport is currently operational.
     *
     * @var bool
     * @JMS\Type("boolean")
     */
    private $active;

    /**
     * Airport constructor.
     * @param string $fs
     * @param string|null $iata
     * @param string|null $icao
     * @param string|null $faa
     * @param string|null $name
     * @param string|null $street1
     * @param string|null $street2
     * @param string $city
     * @param string|null $cityCode
     * @param string|null $stateCode
     * @param string $postalCode
     * @param string $countryCode
     * @param string $countryName
     * @param string $regionName
     * @param string $timeZoneRegionName
     * @param string|null $weatherZone
     * @param string $localTime
     * @param float $utcOffsetHours
     * @param float $latitude
     * @param float $longitude
     * @param int $elevationFeet
     */
    public function __construct(
        $fs,
        $iata = null,
        $icao = null,
        $faa = null,
        $name = null,
        $street1 = null,
        $street2 = null,
        $city,
        $cityCode = null,
        $stateCode = null,
        $postalCode,
        $countryCode,
        $countryName,
        $regionName,
        $timeZoneRegionName,
        $weatherZone = null,
        $localTime,
        $utcOffsetHours,
        $latitude,
        $longitude,
        $elevationFeet
    ) {
        $this->fs = $fs;
        $this->iata = $iata;
        $this->icao = $icao;
        $this->faa = $faa;
        $this->name = $name;
        $this->street1 = $street1;
        $this->street2 = $street2;
        $this->city = $city;
        $this->cityCode = $cityCode;
        $this->stateCode = $stateCode;
        $this->postalCode = $postalCode;
        $this->countryCode = $countryCode;
        $this->countryName = $countryName;
        $this->regionName = $regionName;
        $this->timeZoneRegionName = $timeZoneRegionName;
        $this->weatherZone = $weatherZone;
        $this->localTime = $localTime;
        $this->utcOffsetHours = $utcOffsetHours;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->elevationFeet = $elevationFeet;
    }

    /**
     * The FlightStats code for the airport, globally unique across time.
     *
     * @return string
     */
    public function getFs()
    {
        return $this->fs;
    }

    /**
     * The IATA code for the airport.
     *
     * @return string
     */
    public function getIata()
    {
        return $this->iata;
    }

    /**
     * The ICAO code for the airport.
     *
     * @return string
     */
    public function getIcao()
    {
        return $this->icao;
    }

    /**
     * The FAA code for the airport.
     *
     * @return string
     */
    public function getFaa()
    {
        return $this->faa;
    }

    /**
     * The name of the airport.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * The street address of the airport, part 1.
     *
     * @return string
     */
    public function getStreet1()
    {
        return $this->street1;
    }

    /**
     * Street address of the airport, part 2.
     *
     * @return string
     */
    public function getStreet2()
    {
        return $this->street2;
    }

    /**
     * The city with which the airport is associated.
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * The city code with which the airport is associated.
     *
     * @return string
     */
    public function getCityCode()
    {
        return $this->cityCode;
    }

    /**
     * The State in which the airport is located.
     *
     * @return string
     */
    public function getStateCode()
    {
        return $this->stateCode;
    }

    /**
     * The postal code in which the airport resides.
     *
     * @return string
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * The code for the country in which the airport is located.
     *
     * @return string
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * The name of the country in which the Airport is located.
     *
     * @return string
     */
    public function getCountryName()
    {
        return $this->countryName;
    }

    /**
     * The name of the region in which the Airport is located.
     *
     * @return string
     */
    public function getRegionName()
    {
        return $this->regionName;
    }

    /**
     * The name of the Time Zone region in which the Airport is located.
     *
     * @return string
     */
    public function getTimeZoneRegionName()
    {
        return $this->timeZoneRegionName;
    }

    /**
     * The NOAA weather zone (US only) in which the Airport is located.
     *
     * @return string
     */
    public function getWeatherZone()
    {
        return $this->weatherZone;
    }

    /**
     * The local time at the Airport when the request was made.
     *
     * @return string
     */
    public function getLocalTime()
    {
        return new $this->localTime;
    }

    /**
     * The current UTC offset at the Airport when the request was made.
     *
     * @return float
     */
    public function getUtcOffsetHours()
    {
        return $this->utcOffsetHours;
    }

    /**
     * The latitude of the airport in decimal degrees.
     *
     * @return float
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * The longitude of the airport in decimal degrees.
     *
     * @return float
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @return int
     */
    public function getElevationFeet()
    {
        return $this->elevationFeet;
    }

    /**
     * The FlightStats classification of the airport, 1(max) to 5(min).
     *
     * @return int
     */
    public function getClassification()
    {
        return $this->classification;
    }

    /**
     * Boolean value indicating if the airport is currently operational.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }
}