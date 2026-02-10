<?php

namespace AwardWallet\Common\Geo;

use AwardWallet\Common\Geo\Bing\ReverseGeoCoder;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Psr\Log\LoggerInterface;

class CityCorrector
{

    public const RELIABLE_CITY_COUNTRY_CODES = ['US', 'CA'];

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var \Memcached
     */
    private $memcached;
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var Statement
     */
    private $airportQuery;
    /**
     * @var ReverseGeoCoder
     */
    private $reverseGeoCoder;
    /**
     * @var GeoAirportFinder
     */
    private $airportFinder;

    public function __construct(
        ReverseGeoCodeSourceInterface $reverseGeoCoder,
        LoggerInterface $logger,
        \Memcached $memcached,
        Connection $connection,
        GeoAirportFinder $airportFinder
    )
    {

        $this->logger = $logger;
        $this->memcached = $memcached;
        $this->connection = $connection;
        $this->reverseGeoCoder = $reverseGeoCoder;
        $this->airportFinder = $airportFinder;
    }

    public function correct(string $address, string $city, string $countryCode, float $lat, float $lng): string
    {
        if (in_array($countryCode, self::RELIABLE_CITY_COUNTRY_CODES) || $this->isAirport($address)) {
            return $city;
        }

        $roundedLat = round($lat); // about 100 km in one degree
        $roundedLng = round($lng);
        $cacheKey = "city_corr2_{$city}_{$countryCode}_{$roundedLat}_{$roundedLng}";
        $cache = $this->memcached->get($cacheKey);
        if ($cache !== false) {
            $this->logger->info("got city from cache: {$cache}");
            return $cache;
        }

        $correctedCity = null;
        $aircode = $this->airportFinder->getNearestAirport($lat, $lng, 20);
        if ($aircode !== null) {
            $correctedCity = $aircode->getCityname();
            $this->logger->info("found city through airport {$aircode->getAircode()}: {$correctedCity}");
        }

        if ($correctedCity === null) {
            $correctedCity = $this->findCityByReverseGeocoding($lat, $lng);
            if ($correctedCity !== null) {
                $this->logger->info("found city by reverse geocoding: {$correctedCity}");
            }
        }

        if ($correctedCity !== null && !$this->isSameCity($correctedCity, $city)) {
            $this->logger->info("correcting city from {$city} to {$correctedCity}");
            $city = $correctedCity;
        }

        $this->memcached->set($cacheKey, $city, 86400 * 30);
        return $city;
    }

    private function isAirport(string $address) : bool
    {
        if (strlen($address) !== 3) {
            return false;
        }

        if ($this->airportQuery === null) {
            $this->airportQuery = $this->connection->prepare("select 1 from AirCode where AirCode = ?");
        }

        $this->airportQuery->execute([$address]);
        return $this->airportQuery->fetchColumn() !== false;
    }

    private function isSameCity(string $a, string $b) : bool
    {
        return strcasecmp($this->filterCity($a), $this->filterCity($b)) === 0;
    }

    private function filterCity(string $city) : string
    {
        return str_replace(["'", "’"], ["", ""], $city);
    }

    private function findCityByReverseGeocoding(float $lat, float $lng) : ?string
    {
        try {
            $results = $this->reverseGeoCoder->reverseGeoCode($lat, $lng);
            if (count($results) > 0) {
                $result = reset($results);
                if (isset($result->detailedAddress['City'])) {
                    return $result->detailedAddress['City'];
                }
            }
        } catch (\Throwable $exception) {
            $this->logger->warning("failed to correct city: " . $exception->getMessage(),
                ["exception" => $exception]);
        }

        return null;
    }

}
