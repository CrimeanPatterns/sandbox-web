<?php

namespace AwardWallet\Common\Geo;

use AwardWallet\Common\Geo\Google\DirectionParameters;
use AwardWallet\Common\Geo\Google\GeoResultConverter;
use AwardWallet\Common\Geo\Google\GoogleApi;
use AwardWallet\Common\Geo\Google\GoogleRequestFailedException;
use AwardWallet\Common\Geo\Google\PlaceDetailsParameters;
use AwardWallet\Common\Geo\Google\PlaceTextSearchParameters;
use AwardWallet\Common\Geo\Google\ReverseGeoCodeParameters;
use AwardWallet\Common\Itineraries\ItinerariesCollection;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;

class GoogleGeo
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var GoogleApi
     */
    private $googleApi;

    /**
     * @var EntityRepository
     */
    private $geotagRepo;

    private $defaultContext = ['components' => 'GoogleGeo'];

    /**
     * @var AddressParser
     */
    private $addressParser;

    /**
     * @var AirportResolver
     */
    private $airportResolver;

    /**
     * @var TimezoneResolver
     */
    private $timezoneResolver;

    /**
     * @var GeoCodeSourceInterface[]
     */
    private $geocodeSources;

    /**
     * @var CityCorrector
     */
    private $cityCorrector;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $em,
        GoogleApi $googleApi,
        CityCorrector $cityCorrector,
        AddressParser $addressParser,
        AirportResolver $airportResolver,
        TimezoneResolver $timezoneResolver,
        Iterable $geocodeSources
    ) {
        $this->connection = $em->getConnection();
        $this->logger = $logger;
        $this->googleApi = $googleApi;
        $this->geotagRepo = $em->getRepository(\AwardWallet\Common\Entity\Geotag::class);
        $this->addressParser = $addressParser;
        $this->airportResolver = $airportResolver;
        $this->timezoneResolver = $timezoneResolver;
        $this->geocodeSources = $geocodeSources;
        $this->cityCorrector = $cityCorrector;
    }

    /**
     * @return string - id of geocoding source
     */
    private function googleGeoTag($sAddress, &$nLat, &$nLng, &$arDetailedAddress = null, $placeName = null, $expectedType = 0, $bias = []) : ?string
    {
        if ($sAddress == "") {
            throw new GeoException("GoogleGeoTag: Empty address");
        }
        if (preg_match("/[<>]/ims", $sAddress)) {
            throw new GeoException("GoogleGeoTag: invalid characters in address");
        }

        $nLat = $nLng = $arDetailedAddress = null;
        $parsedAddress = $this->addressParser->parse($sAddress);

        $result = null;
        $resultSourceId = null;
        $cityUnreliable = false;

        $attempts = 0;
        foreach ($this->geocodeSources as $source) {
            $attempts++;
            $results = $source->geoCode($sAddress, $bias);
            $this->logger->debug("got " . count($results) . " results from source");
            if (count($results) > 0) {
                $resultSourceId = $source->getSourceId();
                $result = reset($results);
                if (count($results) === 1) {
                    break;
                }
                foreach ($results as $tag) {
                    if (!empty($tag->postalCode) && !empty($parsedAddress['PostalCode'])
                        && $parsedAddress['PostalCode'] === $tag->postalCode) {
                        $result = $tag;
                        break 2;
                    }
                }
                break;
            }
        }
        $this->logger->info('geocode sources', ['attemptCount' => $attempts, 'final' => $resultSourceId]);

        $tryPlace = !empty($placeName);
        $matchQuality = PHP_INT_MAX;
        if ($result) {
            if ((Constants::GEOTAG_TYPE_AIRPORT === $expectedType) && !in_array('airport', $result->types, true)) {
                $this->logger->debug("airport required, but got something other: " . implode(", ", $result->types));
                return null;
            }

            $nLat = (float)$result->lat;
            $nLng = (float)$result->lng;
            $this->logger->info("found $sAddress [{$nLat}, {$nLng}]", $this->defaultContext);
            if (!empty($result->formattedAddress))
                $matchQuality = similar_text($sAddress, $result->formattedAddress);
            $arDetailedAddress = $result->detailedAddress;
            $cityUnreliable = $result->cityUnreliable;
            // for addresses like 2424 E 38th Street, Dallas, TX 75261 US, which resolved to partial match
            if (!empty($parsedAddress['PostalCode'])) {
                if (!empty($arDetailedAddress['PostalCode']) && $parsedAddress['PostalCode'] == $arDetailedAddress['PostalCode']) {
                    $tryPlace = false;
                } else {
                    $arDetailedAddress = $parsedAddress;
                }
            }
        }

        if ($tryPlace) {
            $place = $this->GooglePlace($sAddress, $placeName);
            if (!empty($place)) {
                $result = null;
                try {
                    $results = $this->googleApi->reverseGeoCode(ReverseGeoCodeParameters::makeFromLatLng($place->getLatitude(), $place->getLongitude()));
                    if (count($results->getResults()) > 0) {
                        $result = $results->getResults()[0];
                    }
                } catch (GoogleRequestFailedException $e) {
                }
                if ($result) {
                    $placeAddress = GeoResultConverter::decodeGoogleGeoResult($result);
                    if (!empty($placeAddress)) {
                        if (!empty($parsedAddress['PostalCode']) && !empty($placeAddress['PostalCode']) && $parsedAddress['PostalCode'] == $placeAddress['PostalCode']) {
                            $arDetailedAddress = $placeAddress;
                            // is the correction needed? may be places already have correct city names?
                            $cityUnreliable = true;
                        }
                        if (similar_text($sAddress, $result->formatted_address) > $matchQuality) {
                            $resultSourceId = 'gp';
                            $nLat = $place->getGeometry()->getLocation()->getLat();
                            $nLng = $place->getGeometry()->getLocation()->getLng();
                        }
                    }
                }
            }
        }

        if ($nLat !== null && $arDetailedAddress !== null && $cityUnreliable && isset($arDetailedAddress['City']) && isset($arDetailedAddress['CountryCode'])) {
            $arDetailedAddress['City'] = $this->cityCorrector->correct($sAddress, $arDetailedAddress['City'], $arDetailedAddress['CountryCode'], $nLat, $nLng);
        }

        return $resultSourceId;
    }

    private function googlePlace($addressText, $placeName){
        $detailedAddress = null;
        if (empty($placeName)) {
            return false;
        }
        $address = $this->addressParser->parse($addressText);
        if (empty($address)) {
            return false;
        }
        try {
            $response = $this->googleApi->placeTextSearch(PlaceTextSearchParameters::makeFromQuery($placeName . ' ' . $addressText));
        } catch (GoogleRequestFailedException $e) {
            return false;
        }
        if (empty($response->getResults())) {
            return false;
        }

        $bestMatch = null;
        foreach ($response->getResults() as $result) {
            $foundAddress = $this->addressParser->parse($result->getFormattedAddress());
            if (!empty($foundAddress['PostalCode']) && $foundAddress['PostalCode'] == $address['PostalCode']
                && !empty($result->getGeometry()) && !empty($result->getGeometry()->getLocation()) && !empty($result->getGeometry()->getLocation()->getLat())) {
                $distance = levenshtein($result->getName() . ' ' . $result->getFormattedAddress(), $placeName . ' ' . $addressText);
                if (!isset($minDistance) || $distance < $minDistance) {
                    $minDistance = $distance;
                    $bestMatch = $result;
                }
            }
        }

        return $bestMatch;
    }

    /**
     * look for address in cache (GeoTag table) or google it, and save to cache
     * @param mixed $sAddress - string, or array of strings (addresses)
     * @param null $placeName
     * @param int $expectedType
     * @return array - row from GeoTag table
     * @throws GeoException
     */
    public function FindGeoTag($sAddress, $placeName = null, $expectedType = 0, bool $immutable = false, bool $useCache = true, array $bias = []){
        $originalExpectedType = $expectedType;
        if ($expectedType === Constants::GEOTAG_TYPE_AIRPORT_WITH_ADDRESS) {
            $expectedType = Constants::GEOTAG_TYPE_AIRPORT;
        }
        $tempAddress = $sAddress;
        if (is_array($sAddress)) {
            $arAddressVariants = $sAddress;
            foreach ($arAddressVariants as $key => $value) {
                $arAddressVariants[$key] = AddressParser::normalizeAddress($value);
            }
            $sAddress = $arAddressVariants[0];
        }
        $sAddress = AddressParser::normalizeAddress($sAddress);
        if ($sAddress === "") {
            if (!is_array($tempAddress) && $tempAddress !== "") { // maybe china address
                $arAddressVariants[] = $tempAddress;
                $sAddress = $tempAddress;
            }
        }
        $this->logger->debug("GoogleGeoTag: FindGeoTag: " . $sAddress, $this->defaultContext);

        $sql1 = <<<SQL
			SELECT * FROM GeoTag WHERE Address = :ADDRESS
SQL;
        $fieldsQuery = $this->connection->executeQuery($sql1, [':ADDRESS' => $sAddress])->fetch();
        $fields = $fieldsQuery;

        if (isset($fields['Address']) && '' === $fields['Address']) {
            $this->logger->info('Fetched empty GeoTag from DB', array_merge($this->defaultContext, ['geo_fields' => $fields]));
            return $fields;
        }
        if (empty($sAddress)) {
            if ($fields === false) {
                $this->connection->executeUpdate("insert ignore into GeoTag(Address, UpdateDate, FoundAddress) values('', now(), '')");
                return $this->connection->executeQuery($sql1, [':ADDRESS' => $sAddress])->fetch();
            }
        }

        // request if:
        // no result OR resetParam OR (old AND limitOk)
        // cached if: = not request
        // result AND no resetParam AND (fresh OR not limitOk)
        if (isset($fields['Lat']) && $fields['Lat'] != '') {
            $cacheTime = SECONDS_PER_DAY * 31;
        } else {
            $cacheTime = 3600 * 8;
        }
        if ($useCache && $fields && ($fields['Lat'] != null) && ((ItinerariesCollection::SQLToDateTime($fields["UpdateDate"]) > (time() - $cacheTime)))) {
            $this->logger->debug('found from DB', array_merge($this->defaultContext, ['geo_fields' => $fields]));

            return $fields;
        }

        // cache is invalid
        $fields = [];

        if (!isset($arAddressVariants)) {
            $arAddressVariants = AddressParser::getAddressVariants($sAddress);
        }
        $found = false;
        foreach ($arAddressVariants as $sCurAddress) {
            $this->logger->debug("GoogleGeoTag: searching: " . $sCurAddress);
            $airport = false;
            if (
                ((Constants::GEOTAG_TYPE_AIRPORT === $expectedType) || preg_match("#^[A-Z]{3}$#ims", $sCurAddress)) &&
                ($airPort = $this->airportResolver->lookupAirPort($sCurAddress)) && !empty($airPort['Lat'])
            ) {
                $this->logger->debug("GoogleGeoTag: resolved as airport");
                $fields['Lat'] = $airPort['Lat'];
                $fields['Lng'] = $airPort['Lng'];
                $sCurAddress = $airPort['AirName'] . ", " . $airPort['CityName'] . ', ' . $airPort['CountryCode'];
                $fields["City"] = $airPort['CityName'];
                $fields["State"] = $airPort['StateName'];
                $fields["Country"] = $airPort['CountryName'];
                $fields["CountryCode"] = $airPort['CountryCode'];
                $fields["StateCode"] = $airPort['State'];
                $fields["TimeZoneLocation"] = $airPort['TimeZoneLocation'];
                $found = $originalExpectedType !== Constants::GEOTAG_TYPE_AIRPORT_WITH_ADDRESS;
                $airport = true;
            }

            if (!$found && $sourceId = $this->googleGeoTag($sCurAddress, $nLat, $nLng, $detailedAddress, $placeName, $expectedType, $bias)) {
                $this->logger->debug("GoogleGeoTag: found: " . $sCurAddress . "<br>");
                $fields["Lat"] = $nLat;
                $fields["Lng"] = $nLng;
                $fields['Source'] = $sourceId;

                $addFields = array('AddressLine', 'PostalCode');
                if (!$airport) {
                    $addFields = array_merge($addFields, array('City', 'State', 'Country', 'StateCode', 'CountryCode'));
                }
                foreach ($addFields as $key) {
                    if (isset($detailedAddress[$key])) {
                        $fields[$key] = $detailedAddress[$key];
                    }
                }
                $found = true;
            }

            if (
                $found
                && empty($fields["TimeZoneLocation"])
                && $this->timezoneResolver->getTimeZoneByCoordinates($fields['Lat'], $fields['Lng'], $timezoneId)
            ) {
                if (isset($timezoneId)) {
                    $fields["TimeZoneLocation"] = $timezoneId;
                }
            }
            if (!isset($fields['Lat'])) {
                $fields['Lat'] = '';
                $fields['Lng'] = '';
            }
            if ($found) {
                break;
            }
        }
        if (!$found) {
            $detailedAddress = $this->addressParser->parse($sAddress);
            if (!empty($detailedAddress)) {
                foreach (array('AddressLine', 'City', 'State', 'Country', 'CountryCode', 'PostalCode') as $key) {
                    if (isset($detailedAddress[$key])) {
                        $fields[$key] = $detailedAddress[$key];
                    }
                }
            }
        }

        $fields['Address'] = $sAddress;
        $fields['FoundAddress'] = $sCurAddress;

        $ar = $fields;
        $ar['HostName'] = gethostname();
        $this->checkGeoTagUpdateFields($fields);
        $ar["Address"] = $sAddress;
        $ar["FoundAddress"] = $sCurAddress;
        foreach (array('AddressLine', 'City', 'State', 'Country', 'PostalCode', 'StateCode', 'CountryCode') as $key) {
            $ar[$key] = $fields[$key] ?? null;
        }

        if (isset($ar["UpdateDate"]))
            unset($ar["UpdateDate"]);

        if ($ar["Lat"] == "") {
            $ar["Lat"] = null;
            $ar["Lng"] = null;
        }

        if (!$immutable) {
            $insertAr = [];

            foreach ($ar as $arKey => $arVal) {
                if ($arKey === 'GeoTagID') continue;
                $insertAr[':' . strtoupper($arKey)] = $arVal;
            }

            if (!$fieldsQuery) {
                $this->logger->debug("GoogleGeoTag: inserting: " . $sCurAddress . "<br>");

                $insertSQL = "INSERT INTO GeoTag (" . implode(", ", array_keys($ar)) . ", UpdateDate) VALUES(" . implode(", ", array_keys($insertAr)) . ", NOW())";
                $this->connection->executeQuery(
                    $insertSQL . " on duplicate key update Lat = :LAT1, Lng = :LNG1",
                    array_merge($insertAr, [':LAT1' => $insertAr[':LAT'], ':LNG1' => $insertAr[':LNG']])
                );
            } else {
                $this->logger->debug("GoogleGeoTag: updating: " . $sCurAddress . "<br>");

                $setSQL = [];
                foreach ($ar as $arKey => $arVal) {
                    if ($arKey === 'GeoTagID') continue;
                    $setSQL[] = $arKey . ' = :' . strtoupper($arKey);
                }

                $updateSQL = "UPDATE GeoTag SET " . implode(", ", $setSQL) . ", UpdateDate = NOW() WHERE GeoTagID = :GEOTAGID";
                $this->connection->executeQuery(
                    $updateSQL,
                    array_merge($insertAr, [':GEOTAGID' => $fieldsQuery['GeoTagID']])
                );
            }

            $qTag = $this->connection->executeQuery("SELECT * FROM GeoTag WHERE Address = :ADDRESS", [':ADDRESS' => $ar['Address']])->fetch();

            if (!$qTag) {
                throw new GeoException("GoogleGeoTag: " . (!$fieldsQuery ? 'inserted' : 'updated') . " address not found: {$ar['Address']}");
            }

            $fields['GeoTagID'] = $qTag['GeoTagID'];
            $fields['UpdateDate'] = $qTag['UpdateDate'];
        }

        $this->logger->debug("FindGeoTag: result [{$fields['Lat']},{$fields['Lng']}]<br>");

        return $fields;
    }

    public function findGeoTagEntity(string $address, string $placeName = null, int $expectedType = 0, bool $immutable = false) : ?\AwardWallet\Common\Entity\Geotag
    {
        $fields = $this->FindGeoTag($address, $placeName, $expectedType);

        if ($immutable) {
            $result = new \AwardWallet\Common\Entity\Geotag();
            $result->setUpdatedate(new \DateTime());
            if (isset($fields['State'])) {
                $result->setState($fields['State']);
            }
            $result->setAddress($fields['Address']);
            if (isset($fields['AddressLine'])) {
                $result->setAddressline($fields['AddressLine'] ?? null);
            }
            if (isset($fields['City'])) {
                $result->setCity($fields['City']);
            }
            if (isset($fields['Country'])) {
                $result->setCountry($fields['Country']);
            }
            if (isset($fields['CountryCode'])) {
                $result->setCountryCode($fields['CountryCode']);
            }
            if (isset($fields['FoundAddress'])) {
                $result->setFoundaddress($fields['FoundAddress']);
            }
            $result->setLat($fields['Lat']);
            $result->setLng($fields['Lng']);
            if (isset($fields['TimeZoneLocation'])) {
                $result->setTimeZoneLocation($fields['TimeZoneLocation']);
            }

            return $result;
        }

        return $this->geotagRepo->find($fields['GeoTagID']);
    }


    public function FindReverseGeoTag($lat, $lng, $useLevels = ['locality', 'administrative_area_level_2'])
    {
        try {
            $results = $this->googleApi->reverseGeoCode(ReverseGeoCodeParameters::makeFromLatLng($lat, $lng));
            foreach($results->getResults() as $geoTag) {
                if (count(array_intersect($useLevels, $geoTag->getTypes())) > 0) {
                    $result = $geoTag;
                    break;
                }
            }
        }
        catch(GoogleRequestFailedException $e){ }
        if(isset($result)) {
            $placeAddress = GeoResultConverter::decodeGoogleGeoResult($result);
            if(!empty($placeAddress)) {
                return $placeAddress;
            }
        }
        return null;
    }

    /**
     * look for duration in memcache or google it, and save to memcache
     * @param mixed $startPoint - string, or array of float (lat,lng)
     * @param mixed $endPoint - string, or array of float (lat,lng)
     * @param mixed $mode - string or null
     * @param mixed $transit_mode - string or null
     * @return int, duration driving from .. to .. in sec
     * @throws GeoException
     */
    public function FindDuration($startPoint, $endPoint, $mode, $transit_mode = null){
        if (is_array($startPoint)) {
            if (count($startPoint) === 2 && is_float($startPoint[0]) && is_float($startPoint[1])) {
                $startPoint = implode(',', $startPoint);
            } else {
                throw new GeoException("GoogleGeoDuration: wrong format address startPoint");
            }
        }
        if (is_array($endPoint)) {
            if (count($endPoint) === 2 && is_float($endPoint[0]) && is_float($endPoint[1])) {
                $endPoint = implode(',', $endPoint);
            } else {
                throw new GeoException("GoogleGeoDuration: wrong format address endPoint");
            }
        }

        $tempAddress = $startPoint;
        $startPoint = AddressParser::normalizeAddress($startPoint);
        if ($startPoint == "") {
            if (!is_array($tempAddress) && $tempAddress != "") { // maybe china address
                $arAddressVariants[] = $tempAddress;
                $startPoint = $tempAddress;
            } else {
                throw new GeoException("GoogleGeoDuration: empty address startPoint");
            }
        }
        $tempAddress = $endPoint;
        $endPoint = AddressParser::normalizeAddress($endPoint);
        if ($endPoint == "") {
            if (!is_array($tempAddress) && $tempAddress != "") { // maybe china address
                $arAddressVariants[] = $tempAddress;
                $endPoint = $tempAddress;
            } else {
                throw new GeoException("GoogleGeoDuration: empty address endPoint");
            }
        }

        $strDirection = $startPoint . ">>>" . $endPoint;
        $this->logger->info("GoogleGeoDuration: FindDuration: " .$strDirection,
            $this->defaultContext);

        try {
            $result = $this->googleApi->directionSearch(DirectionParameters::makeFromDerection($startPoint,
                $endPoint, $mode, $transit_mode));
        } catch (GoogleRequestFailedException $e) {
            $this->logger->info('FindDuration exception', ['exception' => get_class($e), 'text' => $e->getMessage()]);
        }

        $duration = 0;
        if (isset($result)) {
            if ($result->getStatus() !== "OK") {
                $this->logger->info("GoogleDirections: google return " . $result->getStatus());
                return null;
            }

            $routes = $result->getRoutes();
            $route = array_shift($routes);
            if (isset($route['legs']) && count($route['legs']) > 0) {
                $leg = array_shift($route['legs']);
                if (isset($leg['duration']) && isset($leg['duration']['value'])) {
                    $duration = (int)$leg['duration']['value'];
                    $this->logger->info("GoogleDirections: found [{$startPoint}]->[{$endPoint}] duration is {$duration} sec",
                        $this->defaultContext);
                    return $duration;
                } else {
                    throw new GeoException("GoogleDirections: other format response (no duration-value)");
                }

            } else {
                throw new GeoException("GoogleDirections: other format response (no legs)");
            }
        } else {
            $this->logger->info("GoogleDirections: empty result [{$startPoint}]->[{$endPoint}]",
                $this->defaultContext);
        }
        return $duration;
    }

    public function FindHotel($place)
    {
        $fields = ['Name' => $place];
        //Search for place ID
        $placeSearchParameters = PlaceTextSearchParameters::makeFromQuery($place);
        $placeSearchParameters->setType('lodging');
        try {
            $this->logger->info(
                'Requesting Place Text Search Google API',
                array_merge($this->defaultContext, ['parameters' => $placeSearchParameters->toArray()])
            );
            $placesSearchResponse = $this->googleApi->placeTextSearch($placeSearchParameters);
        } catch (GoogleRequestFailedException $e) {
            $this->logger->warning("Failed to perform Google Place Text search: " . $e->getMessage(), $this->defaultContext);
            return $fields;
        }
        if (empty($placesSearchResponse->getResults())) {
            $this->logger->notice('No results from Place Search Google API request, skipping', $this->defaultContext);
            return $fields;
        }
        $placeId = $placesSearchResponse->getResults()[0]->getPlaceId();
        //Get address by place ID
        $placeDetailsParameters = PlaceDetailsParameters::makeFromPlaceId($placeId);
        try {
            $this->logger->info(
                'Requesting Place Details Google API',
                array_merge($this->defaultContext, ['parameters' => $placeDetailsParameters->toArray()])
            );
            $placeDetailsResponse = $this->googleApi->placeDetails($placeDetailsParameters);
        } catch (GoogleRequestFailedException $e) {
            $this->logger->warning(
                "Failed to perform Google Place Details search: " . $e->getMessage(),
                $this->defaultContext
            );
            return $fields;
        }
        if (null === $placeDetailsResponse->getResult()) {
            $this->logger->notice('No result for Place Details Google API request, skipping', $this->defaultContext);
            return $fields;
        }
        $placeDetails = $placeDetailsResponse->getResult();
        $fields = [
            'Name' => $place,
            'Address' => $placeDetails->getFormattedAddress(),
            'FoundAddress' => $placeDetails->getFormattedAddress(),
            'AddressLine' => $placeDetails->getAddressLine(),
            'City' => $placeDetails->getCity(),
            'State' => $placeDetails->getState(),
            'Country' => $placeDetails->getCountry(),
            'CountryCode' => $placeDetails->getCountryShort(),
            'PostalCode' => $placeDetails->getPostalCode(),
            'Lat' => $placeDetails->getLatitude(),
            'Lng' => $placeDetails->getLongitude(),
        ];
        if (
            ($lat = $placeDetails->getLatitude())
            && ($lng = $placeDetails->getLongitude())
            && $this->timezoneResolver->getTimeZoneByCoordinates($lat, $lng, $tzid)
            && $tzid
        ) {
            $fields['TimeZoneLocation'] = $tzid;
        }
        $this->logger->debug("FindHotel: result " . var_export($fields, true) . "<br>");

        return $fields;
    }

    private function checkGeoTagUpdateFields($fields) {
        $airPattern = '/([a-z]{3}) Airport/i';
        $isAirport = isset($fields['FoundAddress'])
            && (strlen($fields['FoundAddress']) == 3
                || preg_match($airPattern, $fields['FoundAddress']));
        # AirCode
        if ($isAirport) {
            if (preg_match($airPattern, $fields['FoundAddress'], $matches)) {
                $fields['FoundAddress'] = $matches[1];
            }
        }
    }

}
