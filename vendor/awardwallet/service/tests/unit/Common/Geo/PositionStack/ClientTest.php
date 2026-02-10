<?php
namespace tests\unit\Common\Geo\PositionStack;

use AwardWallet\Common\Geo\GeoCodeResult;
use AwardWallet\Common\Geo\PositionStack\Client;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ClientTest extends TestCase
{

    public function testSuccess()
    {
        $request = new \HttpDriverRequest('http://api.positionstack.com/v1/forward?access_key=accessKey&query=TestAddr', 'GET', null, [], 5, [
            'hdc_ttl' => 2592000,
            'hdc_can_cache' => function() {},
        ]);
        $response = new \HttpDriverResponse('
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
}        ', 200);

        $http = $this->createMock(\HttpDriverCache::class);
        $http
            ->expects($this->once())
            ->method('request')
            ->with($request)
            ->willReturn($response)
        ;
        $client = new Client($http, "accessKey", $this->createMock(LoggerInterface::class));

        $results = $client->geoCode('TestAddr');
        $this->assertCount(2, $results);

        $result = new GeoCodeResult(38.897675, -77.036547);
        $result->postalCode = '20500';
        $result->detailedAddress = [
            'PostalCode' => '20500',
            'CountryCode' => 'US',
            'Country' => 'United States',
            'StateCode' => 'DC',
            'State' => 'District of Columbia',
            'City' => 'Washington DC',
            'AddressLine' => '1600 Pennsylvania Avenue NW',
        ];
        $result->formattedAddress = '1600 Pennsylvania Avenue NW, Washington, DC, USA';

        $this->assertEquals($result, $results[0]);
    }

}