<?php


namespace AwardWallet\Common\Geo\Google;


use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;

class GoogleApi
{
    const PLACE_TEXT_SEARCH_URL = 'https://maps.googleapis.com/maps/api/place/textsearch';
    const PLACE_DETAILS_URL     = 'https://maps.googleapis.com/maps/api/place/details';
    const PLACE_AUTOCOMPLETE    = 'https://maps.googleapis.com/maps/api/place/autocomplete';
    const TIME_ZONE_URL         = 'https://maps.googleapis.com/maps/api/timezone';
    const GEO_CODE_URL          = 'https://maps.googleapis.com/maps/api/geocode';
    const DIRECTIONS            = 'https://maps.googleapis.com/maps/api/directions';

    const TIMEOUT = 10; //in seconds

    // https://developers.google.com/places/supported_types
    const PLACE_TYPES = ['lodging', 'bus_station', 'geocode'];
    const PLACE_TYPE_LODGING = 'lodging';
    const PLACE_TYPE_BUS_STATION = 'bus_station';

    const PLACE_TEXT_SEARCH_CACHE_TTL = 60 * 60 * 24 * 30;
    const PLACE_DETAIL_CACHE_TTL      = 60 * 60 * 24 * 30; //Up to 30 days is google's recommendation
    const TIME_ZONE_CACHE_TTL         = 60 * 60 * 1;
    const GEO_CODE_CACHE_TTL          = 60 * 60 * 24 * 30;
    const DIRECTIONS_CACHE_TTL          = 60 * 60 * 24 * 30;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var \HttpDriverInterface
     */
    private $httpDriver;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var \Memcached
     */
    private $memcached;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var LoggerInterface
     */
    private $statLogger;

    /**
     * GoogleApi constructor.
     * @param \HttpDriverInterface $httpDriver
     * @param SerializerInterface $serializer
     * @param \Memcached $memcached
     * @param string $apiKey
     */
    public function __construct(
        \HttpDriverInterface $httpDriver,
        SerializerInterface $serializer,
        \Memcached $memcached,
        string $apiKey,
        LoggerInterface $statLogger,
        LoggerInterface $logger
    ) {
        $this->httpDriver = $httpDriver;
        $this->serializer = $serializer;
        $this->memcached = $memcached;
        $this->apiKey = $apiKey;
        $this->statLogger = $statLogger;
        $this->logger = $logger;
    }

    /**
     * @link https://developers.google.com/places/web-service/search#TextSearchRequests
     *
     * @param PlaceTextSearchParameters $parameters
     * @return PlaceSearchResponse
     * @throws GoogleRequestLimitReachedException
     * @throws GoogleRequestFailedException
     */
    public function placeTextSearch(PlaceTextSearchParameters $parameters)
    {
        /** @var PlaceSearchResponse $placeTextSearchResponse */
        $placeTextSearchResponse = $this->requestApi($parameters, self::PLACE_TEXT_SEARCH_URL, PlaceSearchResponse::class, self::PLACE_TEXT_SEARCH_CACHE_TTL, 'google_place_text_search');
        return $placeTextSearchResponse;
    }

    /**
     * @link https://developers.google.com/places/web-service/details
     *
     * @param PlaceDetailsParameters $parameters
     * @return PlaceDetailsResponse
     * @throws GoogleRequestFailedException
     * @throws GoogleRequestLimitReachedException
     */
    public function placeDetails(PlaceDetailsParameters $parameters)
    {
        /** @var PlaceDetailsResponse $placeDetailsResponse */
        $placeDetailsResponse = $this->requestApi($parameters, self::PLACE_DETAILS_URL, PlaceDetailsResponse::class, self::PLACE_DETAIL_CACHE_TTL, 'google_place_details');
        return $placeDetailsResponse;
    }

    /**
     * @param PlaceAutocompleteParameters $parameters
     * @return PlaceAutocompleteResponse
     * @throws GoogleRequestFailedException
     * @throws GoogleRequestLimitReachedException
     */
    public function placeAutocomplete(PlaceAutocompleteParameters $parameters)
    {
        /** @var PlaceAutocompleteResponse $placeAutocompleteResponse */
        $placeAutocompleteResponse = $this->requestApi($parameters, self::PLACE_AUTOCOMPLETE, PlaceAutocompleteResponse::class);
        return $placeAutocompleteResponse;
    }

        /**
     * @link https://developers.google.com/maps/documentation/timezone/intro
     *
     * @param TimeZoneParameters $parameters
     * @return TimeZoneResponse
     * @throws GoogleRequestFailedException
     * @throws GoogleRequestLimitReachedException
     */
    public function timeZone(TimeZoneParameters $parameters)
    {
        /** @var TimeZoneResponse $timeZoneResponse */
        $timeZoneResponse = $this->requestApi($parameters, self::TIME_ZONE_URL, TimeZoneResponse::class, self::TIME_ZONE_CACHE_TTL, 'google_time_zone');
        return $timeZoneResponse;
    }

    /**
     * @param GeoCodeParameters $parameters
     * @return GeoCodeResponse
     * @throws GoogleRequestFailedException
     * @throws GoogleRequestLimitReachedException
     */
    public function geoCode(GeoCodeParameters $parameters)
    {
        /** @var GeoCodeResponse $geoCodeResponse */
        $geoCodeResponse = $this->requestApi($parameters, self::GEO_CODE_URL, GeoCodeResponse::class, self::GEO_CODE_CACHE_TTL, 'google_geo_code');
        return $geoCodeResponse;
    }

    /**
     * @param ReverseGeoCodeParameters $parameters
     * @return GeoCodeResponse
     * @throws GoogleRequestFailedException
     * @throws GoogleRequestLimitReachedException
     */
    public function reverseGeoCode(ReverseGeoCodeParameters $parameters)
    {
        /** @var GeoCodeResponse $geoCodeResponse */
        $geoCodeResponse = $this->requestApi($parameters, self::GEO_CODE_URL, GeoCodeResponse::class, self::GEO_CODE_CACHE_TTL, 'google_reverse_geo_code');
        return $geoCodeResponse;
    }

    /**
     * @param DirectionParameters $parameters
     * @return DirectionResponse
     * @throws GoogleRequestFailedException
     * @throws GoogleRequestLimitReachedException
     */
    public function directionSearch(DirectionParameters $parameters)
    {
        /** @var DirectionResponse $directionResponse */
        $directionResponse = $this->requestApi($parameters, self::DIRECTIONS, DirectionResponse::class, self::DIRECTIONS_CACHE_TTL, 'google_direction');
        return $directionResponse;
    }

    /**
     * @param Parameters $parameters
     * @param string $baseUrl
     * @param string $responseClass
     * @param int $cacheLifespan Cache TTL in seconds
     * @param string $cachePrefix
     * @return GoogleResponse
     * @throws GoogleRequestFailedException
     * @throws GoogleRequestLimitReachedException
     */
    private function requestApi(Parameters $parameters, string $baseUrl, string $responseClass, $cacheLifespan = 0, $cachePrefix = '')
    {
        $cacheKey = $cachePrefix . '_' . $parameters->getHash();
        $lockKey = $cacheKey . '_lock';
        $responseBody = $this->memcached->get($cacheKey);
        $waited = false;
        $locked = false;

        // prevent situation when multiple threads are doing the same request
        // let only one thread do the work, and others will read results from cache
        if (!$responseBody) {
            $locked = $this->memcached->add($lockKey, true, 2);
            if (!$locked) {
                usleep(random_int(1100000, 1300000));
                $waited = true;
                $responseBody = $this->memcached->get($cacheKey);
            }
        }

        try {
            $params = $parameters->toArray();
            $this->statLogger->info("google api request", [
                "url" => $baseUrl,
                "params" => $params,
                "textParams" => implode(" ", $params),
                "cacheKey" => $cacheKey,
                "cacheHit" => !empty($responseBody),
                "cacheResult" => $this->memcached->getResultCode(),
                "locked" => $locked,
                "waited" => $waited
            ]);

            if ($responseBody) {
                /** @var GoogleResponse $deserializedResponse */
                $deserializedResponse = $this->serializer->deserialize($responseBody, $responseClass, 'json');
                return $deserializedResponse;
            }

            $queryParameters = array_merge($parameters->toArray(), ['key' => $this->apiKey]);
            $queryString = http_build_query($queryParameters);
            $requestUrl = $baseUrl . "/json?$queryString";

            $result = $this->httpDriver->request(new \HttpDriverRequest($requestUrl));
            $this->logger->debug("google api response: {$result->httpCode}, {$result->body}");
            if (\HttpDriverResponse::HTTP_OK !== $result->httpCode) {
                $this->logger->debug("google api request failed");
                throw new GoogleRequestFailedException($result->errorMessage,
                    $result->httpCode ? $result->httpCode : 500);
            }
            try {
                /** @var GoogleResponse $googleResponse */
                $googleResponse = $this->serializer->deserialize($result->body, $responseClass, 'json');
            } catch (\RuntimeException $e) {
                $this->logger->debug("google api response deserialization failed: " . $e->getMessage());
                throw new GoogleRequestFailedException($e->getMessage(), $e->getCode(), $e);
            }
            if (null === $googleResponse->getStatus()) {
                throw new GoogleRequestFailedException("Response cannot be understood.", 500);
            }
            switch ($googleResponse->getStatus()) {
                case GoogleResponse::STATUS_ZERO_RESULTS:
                case GoogleResponse::STATUS_NOT_FOUND:
                case GoogleResponse::STATUS_OK:
                    break;
                case GoogleResponse::STATUS_OVER_QUERY_LIMIT:
                    throw new GoogleRequestLimitReachedException($googleResponse->getErrorMessage(), 429);
                case GoogleResponse::STATUS_INVALID_REQUEST:
                    throw new GoogleRequestFailedException($googleResponse->getErrorMessage(), 400);
                case GoogleResponse::STATUS_REQUEST_DENIED:
                    throw new GoogleRequestFailedException($googleResponse->getErrorMessage(), 401);
                case GoogleResponse::STATUS_UNKNOWN_ERROR:
                default:
                    throw new GoogleRequestFailedException($googleResponse->getErrorMessage(), 500);
            }

            $this->memcached->set($cacheKey, $result->body, $cacheLifespan);
            $this->statLogger->info("set google api cache", [
                "url" => $baseUrl,
                "params" => $params,
                "textParams" => implode(" ", $params),
                "cacheKey" => $cacheKey,
                "cacheLifeSpan" => $cacheLifespan,
                "responseSize" => strlen($result->body),
                "cacheResult" => $this->memcached->getResultCode()
            ]);
            $this->logger->debug("google api request successful");
        }
        finally{
            if ($locked) {
                $this->memcached->delete($lockKey);
            }
        }

        $this->logger->debug("returning google response");
        return $googleResponse;
    }
}