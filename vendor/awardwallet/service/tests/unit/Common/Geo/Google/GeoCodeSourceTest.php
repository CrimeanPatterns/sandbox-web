<?php

namespace unit\Common\Geo\Google;

use AwardWallet\Common\Geo\Google\GeoCodeResponse;
use AwardWallet\Common\Geo\Google\GeoCodeSource;
use AwardWallet\Common\Geo\Google\GoogleApi;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerInterface;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use JMS\Serializer\SerializerBuilder;
use Psr\Log\LoggerInterface;

class GeoCodeSourceTest extends TestCase
{

    public function testCityInEnglish()
    {
        $serializer = SerializerBuilder::create()->build();

        $googleGeoResponse = $serializer->deserialize(<<<EOF
{
   "results" : [
      {
         "address_components" : [
            {
               "long_name" : "1 корпус 6",
               "short_name" : "1 корпус 6",
               "types" : [ "street_number" ]
            },
            {
               "long_name" : "Partiynyy Pereulok",
               "short_name" : "Partiynyy Pereulok",
               "types" : [ "route" ]
            },
            {
               "long_name" : "Yuzhnyy administrativnyy okrug",
               "short_name" : "Yuzhnyy administrativnyy okrug",
               "types" : [ "political", "sublocality", "sublocality_level_1" ]
            },
            {
               "long_name" : "Moskva",
               "short_name" : "Moskva",
               "types" : [ "locality", "political" ]
            },
            {
               "long_name" : "Danilovskiy",
               "short_name" : "Danilovskiy",
               "types" : [ "administrative_area_level_3", "political" ]
            },
            {
               "long_name" : "Moskva",
               "short_name" : "Moskva",
               "types" : [ "administrative_area_level_2", "political" ]
            },
            {
               "long_name" : "Russia",
               "short_name" : "RU",
               "types" : [ "country", "political" ]
            },
            {
               "long_name" : "115093",
               "short_name" : "115093",
               "types" : [ "postal_code" ]
            }
         ],
         "formatted_address" : "Partiynyy Pereulok, 1 корпус 6, Moskva, Russia, 115093",
         "geometry" : {
            "location" : {
               "lat" : 55.721217,
               "lng" : 37.634331
            },
            "location_type" : "ROOFTOP",
            "viewport" : {
               "northeast" : {
                  "lat" : 55.7225659802915,
                  "lng" : 37.63567998029149
               },
               "southwest" : {
                  "lat" : 55.7198680197085,
                  "lng" : 37.63298201970849
               }
            }
         },
         "place_id" : "ChIJ1Zf3aj1LtUYR_uyWWRrRnxQ",
         "plus_code" : {
            "global_code" : "9G7VPJCM+FP"
         },
         "types" : [ "street_address" ]
      }
   ],
   "status" : "OK"
}
EOF
        , GeoCodeResponse::class, 'json');

        $logger = $this->createMock(Logger::class);
        $serializer = $this->createMock(SerializerInterface::class);
        $googleApi = $this->createMock(GoogleApi::class);
        $googleApi
            ->expects(self::once())
            ->method('geoCode')
            ->willReturn($googleGeoResponse)
        ;

        $geo = new GeoCodeSource($googleApi, $logger, $serializer);
        $results = $geo->geoCode('Partiynyy Pereulok, 1 корпус 6, Moskva, Russia, 115093');
        // actually should be Moscou, but it will be corrected in GoogleGeo
        self::assertEquals('Moskva', $results[0]->detailedAddress['City']);
    }

}