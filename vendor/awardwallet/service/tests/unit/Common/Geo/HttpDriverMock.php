<?php

namespace tests\unit\Common\Geo;

use HttpDriverRequest;
use HttpDriverResponse;
use HttpLoggerInterface;

class HttpDriverMock implements \HttpDriverInterface
{


    public function start($proxy = null, $proxyLogin = null, $proxyPassword = null, $userAgent = null)
    {
    }

    public function stop()
    {
    }

    public function isStarted()
    {
        return true;
    }

    public function request(HttpDriverRequest $request)
    {
        switch ($request->url) {
            case "http://api.positionstack.com/v1/forward?access_key=some_pos_stack_key&query=test":
                return new HttpDriverResponse('
                {
                   "data": [
                     {
                        "latitude": 38.897675,
                        "longitude": -77.036547,
                        "label": "1600 Pennsylvania Avenue NW, Washington, DC, USA",
                        "name": "1600 Pennsylvania Avenue NW",
                        "type": "address",
                        "number": "1600",
                        "street": "Pennsylvania Avenue NW",
                        "postal_code": "20500",
                        "confidence": 1,
                        "region": "District of Columbia",
                        "region_code": "DC",
                        "administrative_area": null,
                        "neighbourhood": "White House Grounds",
                        "country": "United States",
                        "country_code": "US",
                        "locality": "Washington DC",
                        "map_url": "http://map.positionstack.com/38.897675,-77.036547"
                     },
                     {
                        "latitude": 33.897675,
                        "longitude": -73.036547,
                        "label": "1500 Pennsylvania Avenue NW, Washington, DC, USA",
                        "name": "1500 Pennsylvania Avenue NW",
                        "type": "address",
                        "number": "1500",
                        "street": "Pennsylvania Avenue NW",
                        "postal_code": "23500",
                        "confidence": 1,
                        "region": "District of Columbia",
                        "region_code": "DC",
                        "administrative_area": null,
                        "neighbourhood": "White House Grounds",
                        "country": "United States",
                        "country_code": "US",
                        "map_url": "http://map.positionstack.com/33.897675,-73.036547"
                     }
                  ]
                }', 200);
        }
    }

    public function getState()
    {
    }

    public function setState(array $state)
    {
    }

    public function setLogger(HttpLoggerInterface $logger)
    {
    }
}