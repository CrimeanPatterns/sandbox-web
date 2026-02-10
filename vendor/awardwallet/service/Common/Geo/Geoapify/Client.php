<?php

namespace AwardWallet\Common\Geo\Geoapify;

use AwardWallet\Common\Geo\GeoCodeResult;
use AwardWallet\Common\Geo\GeoCodeSourceInterface;
use AwardWallet\Common\Geo\ReverseGeoCodeSourceInterface;
use HttpDriverCache;
use HttpDriverInterface;
use HttpDriverRequest;
use HttpDriverResponse;
use Psr\Log\LoggerInterface;

class Client implements GeoCodeSourceInterface, ReverseGeoCodeSourceInterface
{

    private const ATTR_DECODED_RESPONSE = 'tzdbc_decoded_response';

    /**
     * @var HttpDriverInterface
     */
    private $http;
    /**
     * @var string
     */
    private $accessKey;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(HttpDriverInterface $http, string $accessKey, LoggerInterface $logger)
    {
        $this->http = $http;
        $this->accessKey = $accessKey;
        $this->logger = $logger;
    }

    public function getSourceId(): string
    {
        return 'gfy';
    }

    public function geoCode(string $query, array $bias = []) : array
    {
        if (strlen($this->accessKey) < 10) {
            return [];
        }
        if (strlen($query) < 3) {
            $this->logger->info("request is too short");
            return [];
        }
        $this->logger->info("geo code request", ['source' => 'gfy', 'query' => $query, 'bias' => json_encode($bias)]);
        $url = 'https://api.geoapify.com/v1/geocode/search?apiKey=' . $this->accessKey . '&text=' . urlencode($query) . '&format=json';
        if ($bias) {
            $url .= '&'.$this->buildBias($bias);
        }
        return $this->makeGeoCodeRequest($url, $query);
    }

    public function reverseGeoCode(float $lat, float $lng): array
    {
        if (strlen($this->accessKey) < 10) {
            return [];
        }
        $this->logger->info("reverseGeoCode request", ['source' => 'gfy', 'query' => sprintf('%s %s', $lat, $lng)]);
        $url = 'https://api.geoapify.com/v1/geocode/reverse?apiKey=' . $this->accessKey . '&lat=' . urlencode($lat) . '&lon=' . urlencode($lng) . '&format=json';
        return $this->makeReverseGeoCodeRequest($url, $lat . ' ' . $lng);
    }

    private function makeGeoCodeRequest(string $url, string $query) : array
    {
        $json = $this->makeRequest($url, $query);
        $maxConfidence = 0;
        $maxConfResult = null;
        $json['results'] = array_filter($json['results'], function(array $row) use (&$maxConfidence, &$maxConfResult) {
            if (!isset($row['rank']['confidence']) || !isset($row['rank']['match_type']) || !isset($row['result_type'])) {
                return false;
            }
            if ($row['rank']['confidence'] > $maxConfidence) {
                $maxConfidence = $row['rank']['confidence'];
                $maxConfResult = $row;
            }
            if ($row['rank']['confidence'] < 0.7) {
                return false;
            }
            if (!in_array($row['rank']['match_type'], ['full_match', 'inner_part', 'match_by_building', 'match_by_street', 'match_by_postcode'])) {
                return false;
            }
            if (!in_array($row['result_type'], ['amenity', 'building', 'street', 'suburb', 'district', 'postcode', 'city'])) {
                return false;
            }
            return true;
        });

        if (count($json['results']) === 0) {
            $this->logger->info('geoapify request result', ['success' => false, 'error' => 'noResults', 'query' => $query, 'maxConfidence' => $maxConfidence, 'candidate' => $maxConfResult]);
            return [];
        }
        $this->logger->info('geoapify request result', ['success' => true, 'query' => $query]);
        return $this->convertRows($json);
    }

    private function makeReverseGeoCodeRequest(string $url, string $query): array
    {
        $json = $this->makeRequest($url, $query);
        if (count($json['results']) !== 1) {
            $this->logger->info('geoapify request result', ['success' => false, 'error' => 'noResults', $query => $query]);
            return [];
        }
        $this->logger->info('geoapify request result', ['success' => true, 'query' => $query]);
        return $this->convertRows($json);
    }

    private function makeRequest(string $url, string $query): array
    {
        $response = $this->http->request(new HttpDriverRequest($url, 'GET', null, [], 5, [
            HttpDriverCache::ATTR_TTL => 60 * 60 * 24 * 30,
            HttpDriverCache::ATTR_CAN_CACHE_CALLBACK => function (HttpDriverResponse $response) {
                if ($response->httpCode != 200) {
                    return false;
                }

                $decoded = @json_decode($response->body, true);
                $response->attributes[self::ATTR_DECODED_RESPONSE] = $decoded;

                return is_array($decoded) && isset($decoded['data']);
            }
        ]));
        $json = $response->attributes[self::ATTR_DECODED_RESPONSE] ?? @json_decode($response->body, true);

        if ($response->httpCode !== 200) {
            $this->logger->info('geoapify request result', ['success' => false, 'error' => 'httpCode', 'code' => $response->httpCode, 'query' => $query]);
            return ['results' => []];
        }

        if (!is_array($json) || !isset($json['results']) || !is_array($json['results'])) {
            $this->logger->info("geoapify request result", ['success' => false, 'error' => 'body', 'body' => substr($response->body, 0, 250), 'query' => $query, 'cacheHit' => $response->attributes[HttpDriverCache::ATTR_FROM_CACHE]]);
            return ['results' => []];
        }
        return $json;
    }

    private function convertRows(array $json): array
    {
        return array_map(function(array $row){
            $result = new GeoCodeResult($row['lat'], $row['lon']);
            $result->formattedAddress = $row['formatted'] ?? null;
            // geoapify returns all possible zip codes if query is just a city name
            if (isset($row['postcode']) && strlen($row['postcode']) > 10) {
                $row['postcode'] = null;
            }
            $result->postalCode = $row['postcode'] ?? null;
            $result->detailedAddress['PostalCode'] = $result->postalCode;
            $result->detailedAddress['CountryCode'] = isset($row['country_code']) ? strtoupper($row['country_code']) : null;
            $result->detailedAddress['Country'] = $row['country'] ?? null;
            $result->detailedAddress['StateCode'] = $row['state_code'] ?? null;
            $result->detailedAddress['State'] = $row['state'] ?? null;
            $result->detailedAddress['City'] = $row['city'] ?? null;
            $result->tzId = $row['timezone']['name'] ?? null;
            if (!empty($row['housenumber']) && !empty($row['street'])) {
                $result->detailedAddress['AddressLine'] = $row['housenumber'] . ' ' . $row['street'];
            }
            else {
                $result->detailedAddress['AddressLine'] = $row['address_line1'] ?? null;
            }
            return $result;
        }, $json['results']);
    }

    private function buildBias(array $bias): string
    {
        $query = '';
        foreach($bias as $type => $value) {
            switch($type) {
                case 'country':
                    if ('uk' == $value) {
                        $value = 'gb';
                    }
                    $query .= 'filter=countrycode:'.$value;
                    break;
                case 'box':
                    list($neLat, $neLng, $swLat, $swLng) = explode(' ', $value);
                    $query .= sprintf('filter=rect:%s,%s,%s,%s', $neLng, $neLat, $swLng, $swLat);
                    break;
            }
        }
        return $query;
    }
}