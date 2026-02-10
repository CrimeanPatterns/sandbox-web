<?php

namespace AwardWallet\Common\Geo\PositionStack;

use AwardWallet\Common\Geo\GeoCodeResult;
use AwardWallet\Common\Geo\GeoCodeSourceInterface;
use Psr\Log\LoggerInterface;

class Client implements GeoCodeSourceInterface
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

    public function geoCode(string $query, array $bias = []) : array
    {
        $this->logger->info("positionStack geo request: $query");
        if (strlen($query) < 3) {
            $this->logger->info("request is too short");
            return [];
        }

        return $this->makeGeoCodeRequest('http://api.positionstack.com/v1/forward?access_key=' . $this->accessKey . '&query=' . urlencode($query));
    }

    /**
     * @return GeoCodeResult[]
     */
    public function reverseGeoCode(float $lat, float $lng) : array
    {
        return $this->makeGeoCodeRequest('http://api.positionstack.com/v1/reverse?access_key=' . $this->accessKey . '&query=' . urlencode($lat . "," . $lng));
    }

    private function haveEmptyRows(array $json) : bool
    {
        if (!isset($json['data']) || !is_array($json['data'])) {
            return false;
        }

        foreach ($json['data'] as $row) {
            if (empty($row)) {
                $this->logger->warning("empty row at response, will retry");
                return true;
            }
        }

        return false;
    }

    private function makeGeoCodeRequest(string $url) : array
    {
        $try = 0;
        do {
            if ($try > 0) {
                sleep(10 * (2 ** $try)); // 10, 20, 40, 80, 160
            }
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
            $try++;
        } while (($response->httpCode === 429 || !is_array($json) || $this->haveEmptyRows($json)) && $try <= 5);

        if ($response->httpCode !== 200) {
            $this->logger->warning("positionStack returned http {$response->httpCode}: " . substr($response->body, 0, 250));
            return [];
        }

        if (!is_array($json) || !isset($json['data'])) {
            $this->logger->warning("positionStack returned invalid json: " . substr($response->body, 0, 250));
            return [];
        }

        $json['data'] = array_filter($json['data'], function(array $row) use ($response) {
            if (!isset($row['type'])) {
                throw new \Exception("Invalid positionstack row: " . json_encode($row) . ", full response: " . $response->body);
            }
            return in_array($row['type'], ["address", "street", "locality", "borough", "county", "macrocounty", "postalcode"]);
        });

        return array_map(function(array $row){
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
                if (!empty($row['street']) && !empty($row['number'])) {
                    $result->detailedAddress['AddressLine'] = $row['number'] . ' ' . $row['street'];
                }
                elseif (!empty($row['street']) && empty($row['number'])) {
                    $result->detailedAddress['AddressLine'] = $row['street'];
                }
                elseif (empty($row['street']) && !empty($row['number'])) {
                    $result->detailedAddress['AddressLine'] = $row['number'];
                }
            }

            return $result;
        }, $json['data']);
    }

}
