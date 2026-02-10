<?php

namespace AwardWallet\Common\Geo;

use AwardWallet\Common\Geo\Google\GoogleApi;
use AwardWallet\Common\Geo\Google\LatLng;
use AwardWallet\Common\Geo\Google\TimeZoneParameters;
use AwardWallet\Common\Geo\TimezoneDb\Client;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class TimezoneResolver
{

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var GoogleApi
     */
    private $googleApi;
    /**
     * @var Client
     */
    private $timezoneDbClient;

    public function __construct(
        LoggerInterface $logger, Connection $connection, GoogleApi $googleApi, Client $timezoneDbClient
    )
    {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->googleApi = $googleApi;
        $this->timezoneDbClient = $timezoneDbClient;
    }

    public function getTimeZoneByCoordinates($lat, $lng, &$timezoneId) : bool
    {
        if (($lat === "" || $lng === '') || (abs($lat) > 90 || abs($lng) > 180)) {
            throw new GeoException("Empty/Incorrect Coordinates: {$lat}, {$lng}");
        }

        $timezoneId = null;

        try {
            $result = $this->timezoneDbClient->getTimezone($lat, $lng);
            if ($result !== null) {
                $timezoneId = $result->getZoneName();
            }
        } catch (\Exception $exception) {
            // fallback to google
        }

        if (!isset($timezoneId)) {
            $response = $this->googleApi->timeZone(TimeZoneParameters::makeFromLatLng(new LatLng($lat, $lng)));
            $timezoneId = $response->getTimeZoneId();
        }

        if (isset($timezoneId)) {
            $this->logger->debug("GeoTag: TimeZoneByCoordinates: found [{$lat},{$lng}], timezone {$timezoneId}");
        } else {
            $this->logger->debug("GeoTag: TimeZoneByCoordinates: failed to find timezone for [{$lat},{$lng}]");
        }

        return isset($timezoneId);
    }

    public function getTimeZoneOffsetByLocation($location)
    {
        try {
            $dateTimeZone = new \DateTimeZone($location);
            $offset = $dateTimeZone->getOffset(new \DateTime());
        } catch (\Exception $e) {
            $this->logger->critical('"'.$location.'" time zone not allowed');
            return null;
        }

        return $offset;
    }
}
