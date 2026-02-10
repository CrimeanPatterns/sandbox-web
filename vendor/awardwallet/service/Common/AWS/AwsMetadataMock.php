<?php

namespace AwardWallet\Common\AWS;

use HttpDriverRequest;
use HttpDriverResponse;
use HttpLoggerInterface;

class AwsMetadataMock implements \HttpDriverInterface
{


    public function start($proxy = null, $proxyLogin = null, $proxyPassword = null, $userAgent = null)
    {
    }

    public function stop()
    {
    }

    /**
     * @return boolean
     */
    public function isStarted()
    {
        return true;
    }

    /**
     * @param $url
     * @param string $method
     * @param mixed $postData
     * @param array $headers
     * @return HttpDriverResponse
     */
    public function request(HttpDriverRequest $request)
    {
        switch ($request->url) {
            case 'http://169.254.169.254/latest/meta-data/placement/availability-zone':
                return new HttpDriverResponse('us-east-1a', 200);
            case 'http://169.254.169.254/latest/meta-data/local-ipv4':
                return new HttpDriverResponse('192.168.0.255', 200);
            case 'http://169.254.169.254/latest/meta-data/public-ipv4':
                return new HttpDriverResponse('1.2.3.4', 200);
            default:
                throw new \Exception("unknown url: {$request->url}");
        }
    }

    /**
     * @return array
     */
    public function getState()
    {
        return [];
    }

    public function setState(array $state)
    {
    }

    public function setLogger(HttpLoggerInterface $logger)
    {
    }
}