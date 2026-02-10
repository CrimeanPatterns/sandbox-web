<?php

namespace AwardWallet\Common\AWS;

use AwardWallet\Common\MemoryCache\Cache;

class Util
{

    /** @var \HttpDriverInterface */
    private $httpDriver;
    /**
     * @var Cache
     */
    private $cache;

    public function __construct(\HttpDriverInterface $httpDriver, Cache $cache)
    {
        $this->httpDriver = $httpDriver;
        $this->cache = $cache;
    }

    public function getHostName() : ?string
    {
        return $this->cache->get("hostname", 60, function() {
            $response = $this->httpDriver->request(new \HttpDriverRequest('http://169.254.169.254/latest/meta-data/public-hostname', 'GET', null, [], 3));

            if (stripos($response->body, 'ec2') !== false) {
                return trim($response->body);
            }

            return null;
        });
    }

    public function getRegion() : string
    {
        return $this->cache->get("region", 86400, function() {
            $response = $this->httpDriver->request(new \HttpDriverRequest('http://169.254.169.254/latest/meta-data/placement/availability-zone', 'GET', null, [], 3));

            if(preg_match('#^(\w+(\-\w+)*\-\d+)[a-z]$#ims', $response->body, $matches)) {
                return $matches[1];
            }

            throw new AwsUtilException("Failed to detect region. response: " . $response->body);
        });

    }

    public function getLocalIP() : string
    {
        return $this->cache->get("local-ip", 300, function() {
            $response = $this->httpDriver->request(new \HttpDriverRequest('http://169.254.169.254/latest/meta-data/local-ipv4', 'GET', null, [], 3));
            $ip = trim($response->body);

            if(preg_match('#^\d+\.\d+\.\d+\.\d+$#ims', $ip)) {
                return $ip;
            }

            throw new AwsUtilException("Failed to detect local ip. response: " . $response->body);
        });

    }

    public function getPublicIP() : string
    {
        return $this->cache->get("public-ip", 300, function() {
            $response = $this->httpDriver->request(new \HttpDriverRequest('http://169.254.169.254/latest/meta-data/public-ipv4', 'GET', null, [], 3));
            $ip = trim($response->body);

            if(preg_match('#^\d+\.\d+\.\d+\.\d+$#ims', $ip)) {
                return $ip;
            }

            throw new AwsUtilException("Failed to detect public ip. response: " . $response->body);
        });

    }

    public function isSpotTerminating() : bool
    {
        $response = $this->httpDriver->request(new \HttpDriverRequest('http://169.254.169.254/latest/meta-data/spot/termination-time', 'GET', null, [], 3));
        return ($response->httpCode == 200);
    }

}