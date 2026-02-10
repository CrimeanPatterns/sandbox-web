<?php

namespace AwardWallet\Common\Geo\PositionStack;

use AwardWallet\Common\Geo\Geo;
use AwardWallet\Common\Geo\GeoCodeResult;
use Psr\Log\LoggerInterface;

class SolverClient
{

    private const ATTR_DECODED_RESPONSE = 'tzdbc_decoded_response';

    private const FIELD_MAP = [
        'postal_code' => 'PostalCode',
        'country_code' => 'CountryCode',
        'country' => 'Country',
        'region_code' => 'StateCode',
        'region' => 'State',
        'locality' => 'City',
        'county' => 'City',
    ];

    /**
     * @var \HttpDriverInterface
     */
    private $httpDriver;
    /**
     * @var string
     */
    private $accessKey;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(\HttpDriverInterface $httpDriver, string $accessKey, LoggerInterface $logger)
    {
        $this->httpDriver = $httpDriver;
        $this->accessKey = $accessKey;
        $this->logger = $logger;
    }

    public function getSourceId() : string
    {
        return 'ps';
    }

    public function geoCode(string $query) : array
    {
        if (strlen($query) < 3) {
            $this->logger->info("request is too short");
            return [];
        }
        $this->logger->info("geo code request", ['source' => 'ps', 'query' => $query]);

        return $this->makeGeoCodeRequest('http://api.positionstack.com/v1/forward?access_key=' . $this->accessKey . '&query=' . urlencode($query), $query);
    }

    private function haveEmptyRows(array $json) : bool
    {
        if (!isset($json['data']) || !is_array($json['data'])) {
            return false;
        }

        foreach ($json['data'] as $row) {
            if (empty($row)) {
                $this->logger->notice("empty row at response, will retry");
                return true;
            }
        }

        return false;
    }

    private function makeGeoCodeRequest(string $url, string $query) : array
    {
        $response = $this->httpDriver->request(new \HttpDriverRequest($url, 'GET', null, [], 5, [
            \HttpDriverCache::ATTR_TTL => 60 * 60 * 24 * 30,
            \HttpDriverCache::ATTR_CAN_CACHE_CALLBACK => function (\HttpDriverResponse $response) {
                if ($response->httpCode != 200) {
                    return false;
                }

                $decoded = json_decode($response->body, true);
                $response->attributes[self::ATTR_DECODED_RESPONSE] = $decoded;

                return is_array($decoded) && isset($decoded['data']);
            }
        ]));
        $json = $response->attributes[self::ATTR_DECODED_RESPONSE] ?? @json_decode($response->body, true);

        if ($response->httpCode !== 200) {
            $this->logger->info('positionstack request result', ['success' => false, 'error' => 'httpCode', 'code' => $response->httpCode, 'query' => $query]);
            return [];
        }

        if ($this->haveEmptyRows($json)) {
            $this->logger->info('positionstack request result', ['success' => false, 'error' => 'emptyRows', 'query' => $query, 'body' => substr($response->body, 0, 1000), 'cacheHit' => $response->attributes[\HttpDriverCache::ATTR_FROM_CACHE]]);
            return [];
        }

        if (!is_array($json) || !isset($json['data']) || !is_array($json['data'])) {
            $this->logger->info("positionstack request result", ['success' => false, 'error' => 'body', 'body' => substr($response->body, 0, 250), 'query' => $query, 'cacheHit' => $response->attributes[\HttpDriverCache::ATTR_FROM_CACHE]]);
            return [];
        }

        if (count($json['data']) === 2) {
            $json['data'] = $this->collapse($json['data'], $query);
        }

        if (count($json['data']) !== 1) {
            $this->logger->info("positionstack request result", ['success' => false, 'error' => 'resultCount', 'count' => count($json['data']), 'query' => $query, 'body' => $response->body]);
            return [];
        }

        if (empty($json['data'][0]['country_code']) || 'USA' !== $json['data'][0]['country_code']) {
            $this->logger->info("positionstack request result", ['success' => false, 'error' => 'nonUs', 'query' => $query, 'body' => $response->body]);
            return [];
        }

        $this->logger->info('positionstack request result', ['success' => true, 'query' => $query, 'body' => $response->body]);

        $json['data'] = array_filter($json['data'], function(array $row) use ($response) {
            if (!isset($row['type'])) {
                throw new \Exception("Invalid positionstack row: " . json_encode($row) . ", full response: " . $response->body);
            }
            return in_array($row['type'], ["address", "street", "locality", "borough", "county", "macrocounty", "postalcode"]);
        });

        $results = array_map(function(array $row){
            $result = new GeoCodeResult($row['latitude'], $row['longitude']);
            $result->formattedAddress = $row['label'];
            $result->postalCode = $row['postal_code'];

            foreach (self::FIELD_MAP as $fromField => $toField) {
                if (isset($result->detailedAddress[$toField])) {
                    continue;
                }
                if (!empty($row[$fromField])) {
                    $result->detailedAddress[$toField] = $row[$fromField];
                }
            }
            if (!empty($row['street']) && !empty($row['number'])) {
                $result->detailedAddress['AddressLine'] = $row['number'] . ' ' . $row['street'];
            }
            elseif (!empty($row['street']) && empty($row['number'])) {
                $result->detailedAddress['AddressLine'] = $row['street'];
            }
            elseif (empty($row['street']) && !empty($row['number'])) {
                $result->detailedAddress['AddressLine'] = $row['number'];
            }

            // todo: remove when ps support responds and possibly replace with country_module=1
            if (!empty($result->detailedAddress['CountryCode']) && 'USA' === $result->detailedAddress['CountryCode']) {
                $result->detailedAddress['CountryCode'] = 'US';
            }

            return $result;
        }, $json['data']);
        return array_filter($results, function(GeoCodeResult $row){
            return !empty($row->detailedAddress['AddressLine']);
        });
    }

    private function collapse(array $rows, string $query): array
    {
        foreach(['latitude', 'longitude', 'number', 'country_code', 'label'] as $key) {
            foreach ($rows as $row) {
                if (empty($row[$key])) {
                    return $rows;
                }
            }
        }
        if ($rows[0]['number'] == $rows[1]['number']
            && Geo::vincentyDistance(
                $rows[0]['latitude'], $rows[0]['longitude'],$rows[1]['latitude'], $rows[1]['longitude']
            ) < 50) { // arbitrary 50 meters based on examples
            $this->logger->info('positionstack: collapsed two similar addresses', ['query' => $query]);
            return [$rows[similar_text($query, $rows[1]['label']) > similar_text($query, $rows[0]['label']) ? 1 : 0]];
        }
        return $rows;
    }

}
