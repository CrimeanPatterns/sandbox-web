<?php

namespace AwardWallet\Common\Geo\Bing;

use AwardWallet\Common\Geo\GeoCodeResult;
use AwardWallet\Common\Geo\ReverseGeoCodeSourceInterface;
use Psr\Log\LoggerInterface;

class ReverseGeoCoder implements ReverseGeoCodeSourceInterface
{

    private const ATTR_DECODED_RESPONSE = 'tzdbc_decoded_response';

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
    /**
     * @var int
     */
    private $retries;

    public function __construct(\HttpDriverInterface $httpDriver, string $accessKey, LoggerInterface $logger, int $retries){

        $this->httpDriver = $httpDriver;
        $this->accessKey = $accessKey;
        $this->logger = $logger;
        $this->retries = $retries;
    }

    public function reverseGeoCode(float $lat, float $lng): array
    {
        $try = 0;
        do {
            if ($try > 0) {
                sleep(10 * (2 ** $try)); // 10, 20, 40, 80, 160
            }
            $response = $this->httpDriver->request(new \HttpDriverRequest("http://dev.virtualearth.net/REST/v1/Locations/{$lat},{$lng}?key=" . urlencode($this->accessKey) . '&include=ciso2', 'GET', null, [], 5, [
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
            $try++;
        } while (in_array($response->httpCode, [429, 503, 502, 500]) && $try <= $this->retries);

        if ($response->httpCode !== 200) {
            $this->logger->warning("bing returned http {$response->httpCode}: " . substr($response->body, 0, 250));
            return [];
        }

        $json = $response->attributes[self::ATTR_DECODED_RESPONSE] ?? @json_decode($response->body, true);
        $matches = $json['resourceSets'] ?? [];
        $results = [];
        foreach ($matches as $resourceSet) {
            foreach ($resourceSet['resources'] ?? [] as $resource) {
                $results[] = $this->convertResource($resource);
            }
        }

        return $results;
    }

    private function convertResource(array $resource) : GeoCodeResult
    {
        $result = new GeoCodeResult($resource['point']['coordinates'][0], $resource['point']['coordinates'][1]);
        $result->formattedAddress = $resource['address']['formattedAddress'];
        $result->postalCode = $resource['address']['postalCode'] ?? null;

        foreach ([
            'postalCode' => 'PostalCode',
            'countryRegionIso2' => 'CountryCode',
            'countryRegion' => 'Country',
            'adminDistrict' => 'StateCode',
//            'region' => 'State', @TODO: lookup state name by code on upper level
            'adminDistrict2' => 'City',
            'locality' => 'City',
        ] as $sourceField => $targetField) {
            if (!isset($result->detailedAddress[$targetField]) && isset($resource['address'][$sourceField])) {
                $result->detailedAddress[$targetField] = $resource['address'][$sourceField];
            }
        }

        return $result;
    }
}
