<?php

namespace tests\unit\Common\Geo;

use AwardWallet\Common\Geo\AddressParser;
use AwardWallet\Common\Geo\AirportResolver;
use AwardWallet\Common\Geo\Bing\ReverseGeoCoder;
use AwardWallet\Common\Geo\CityCorrector;
use AwardWallet\Common\Geo\GeoAirportFinder;
use AwardWallet\Common\Geo\GeoCodeResult;
use AwardWallet\Common\Geo\Google\GeoCodeResponse;
use AwardWallet\Common\Geo\Google\GeoCodeSource;
use AwardWallet\Common\Geo\Google\GoogleApi;
use AwardWallet\Common\Geo\GoogleGeo;
use AwardWallet\Common\Geo\PositionStack\Client;
use AwardWallet\Common\Geo\TimezoneResolver;
use AwardWallet\Common\Memcached\MemcachedMock;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class GoogleGeoTest extends TestCase
{

    public function testCityInEnglish()
    {
        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->method('getRepository')->willReturn($this->createMock(ObjectRepository::class));
        $emMock->method('getConnection')->willReturn($this->createConnectionMock());

        $tzResolverMock = $this->createMock(TimezoneResolver::class);
        $tzResolverMock->method('getTimeZoneByCoordinates')->willReturn(false);
        $tzResolverMock->method('getTimeZoneOffsetByLocation')->willReturn(null);

        $googleApiMock = $this->createGoogleApiMock();

        $geoCodeResult = new GeoCodeResult(100, 100);
        $geoCodeResult->detailedAddress = ['City' => 'Moscow'];

        $reverseGeoCoderMock = $this->createMock(ReverseGeoCoder::class);
        $reverseGeoCoderMock->method('reverseGeoCode')->willReturn([$geoCodeResult]);

        $connectionMock = $this->createConnectionMock();

        $cityCorrector = new CityCorrector($reverseGeoCoderMock, new NullLogger(), new MemcachedMock(), $connectionMock, $this->createMock(GeoAirportFinder::class));

        $logger = $this->createMock(Logger::class);
        $serializer = $this->createMock(SerializerInterface::class);

        $geo = new GoogleGeo(
            new NullLogger(),
            $emMock,
            $googleApiMock,
            $cityCorrector,
            new AddressParser($connectionMock),
            $this->createMock(AirportResolver::class),
            $tzResolverMock,
            [new GeoCodeSource($googleApiMock, $logger, $serializer)]
        );

        $tag = $geo->FindGeoTag('Partiynyy Pereulok, 1 корпус 6, Moskva, Russia, 115093', null, 0, true);
        self::assertEquals("Moscow", $tag["City"]);
    }

    private function createGoogleApiMock() : GoogleApi
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

        $googleApi = $this->createMock(GoogleApi::class);
        $googleApi
            ->expects(self::once())
            ->method('geoCode')
            ->willReturn($googleGeoResponse)
        ;

        return $googleApi;
    }

    private function createConnectionMock() : Connection
    {
        $statementMock = $this->createMock(ResultStatement::class);
        $statementMock->method('fetchColumn')->willReturn(false);
        $statementMock->method('fetch')->willReturn(false);
        $statementMock->method('fetchAll')->willReturn([]);

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock
            ->method('executeQuery')->willReturn($statementMock)
        ;

        $connectionMock->method('prepare')->willReturn($this->createMock(Statement::class));

        return $connectionMock;
    }

}