<?php

namespace AwardWallet\Common\Geo\TimezoneDb;

class Client
{

    private const ATTR_DECODED_RESPONSE = 'tzdbc_decoded_response';

    /**
     * @var string
     */
    private $apiKey;
    /**
     * @var \HttpDriverInterface
     */
    private $httpDriver;
    /**
     * @var string
     */
    private $endpoint;

    public function __construct(string $endpoint, string $apiKey, \HttpDriverCache $httpDriver)
    {
        $this->apiKey = urlencode($apiKey);
        $this->httpDriver = $httpDriver;
        $this->endpoint = $endpoint;
    }

    public function getTimezone(float $lat, float $lng, int $timestamp = null) : ?Response
    {
        $url = "{$this->endpoint}/v2.1/get-time-zone?key={$this->apiKey}&format=json&by=position&lat="
            . urlencode($lat) . "&lng=" . urlencode($lng);

        if ($timestamp !== null) {
            $url .= "&time=" . $timestamp;
        }

        $response = $this->httpDriver->request(new \HttpDriverRequest($url, 'GET', null, [], 30, [
            \HttpDriverCache::ATTR_TTL => 300,
            \HttpDriverCache::ATTR_CAN_CACHE_CALLBACK => function(\HttpDriverResponse $response){
                if ($response->httpCode != 200) {
                    return false;
                }

                $decoded = json_decode($response->body, true);
                $response->attributes[self::ATTR_DECODED_RESPONSE] = $decoded;

                return is_array($decoded) && isset($decoded['status']) && $decoded['status'] === 'OK';
            }
        ]));

        if ($response->httpCode !== 200) {
            return null;
        }

        $data = $response->attributes[self::ATTR_DECODED_RESPONSE] ?? json_decode($response->body, true);
        if (!isset($data['status']) || $data['status'] !== 'OK' || !isset($data['zoneName']) || !isset($data['gmtOffset'])) {
            return null;
        }

        return new Response($data['zoneName'], $data['gmtOffset']);
    }

}