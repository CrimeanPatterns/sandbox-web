<?php

namespace AwardWallet\Engine\testprovider\TripSegment;

use AwardWallet\Engine\testprovider\Success;

class AirlineName extends Success
{
    public function ParseItineraries()
    {
        $segments = [
            [
                'AirlineName'  => 'LZ',
                'Duration'     => '2:00',
                'Seats'        => '32A',
                'DepDate'      => strtotime("2037-08-03 9:00"),
                'DepCode'      => 'LAX',
                'DepName'      => 'Los Angeles International Airport',
                'ArrDate'      => strtotime("2037-08-03 16:30"),
                'ArrCode'      => 'JFK',
                'ArrName'      => 'John F. Kennedy International Airport',
                'FlightNumber' => '1234',
            ],
            [
                'AirlineName'  => 'BMM',
                'Duration'     => '2:00',
                'Seats'        => '32A',
                'DepDate'      => strtotime("2037-08-03 9:00"),
                'DepCode'      => 'LAX',
                'DepName'      => 'Los Angeles International Airport',
                'ArrDate'      => strtotime("2037-08-03 16:30"),
                'ArrCode'      => 'JFK',
                'ArrName'      => 'John F. Kennedy International Airport',
                'FlightNumber' => '4321',
            ],
            [
                'AirlineName'  => 'Normal Airline Name',
                'Duration'     => '2:00',
                'Seats'        => '32A',
                'DepDate'      => strtotime("2037-08-03 9:00"),
                'DepCode'      => 'LAX',
                'DepName'      => 'Los Angeles International Airport',
                'ArrDate'      => strtotime("2037-08-03 16:30"),
                'ArrCode'      => 'JFK',
                'ArrName'      => 'John F. Kennedy International Airport',
                'FlightNumber' => '3241',
            ],
        ];

        return [
            [
                'Kind'          => 'T',
                'RecordLocator' => 'FLYDEL2',
                'TripSegments'  => $segments,
            ],
        ];
    }
}
