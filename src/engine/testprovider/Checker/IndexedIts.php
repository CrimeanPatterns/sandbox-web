<?php

namespace AwardWallet\Engine\testprovider\Checker;

use AwardWallet\Engine\testprovider\Success;

class IndexedIts extends Success
{
    public function ParseItineraries()
    {
        return [
            2 => [
                'Kind'          => 'T',
                'RecordLocator' => 'IND2',
                'TripSegments'  => [
                    [
                        'AirlineName'  => 'Timeline Airlines',
                        'Duration'     => '2:00',
                        'DepDate'      => strtotime("2037-08-03 9:00"),
                        'DepCode'      => 'LAX',
                        'DepName'      => 'Los Angeles International Airport',
                        'ArrDate'      => strtotime("2037-08-03 16:30"),
                        'ArrCode'      => 'JFK',
                        'ArrName'      => 'JF Kennedy Airport',
                        'FlightNumber' => 'INDF2',
                    ],
                ],
            ],
            0 => [
                'Kind'          => 'T',
                'RecordLocator' => 'IND0',
                'TripSegments'  => [
                    [
                        'AirlineName'  => 'Timeline Airlines',
                        'Duration'     => '2:00',
                        'DepDate'      => strtotime("2033-08-03 9:00"),
                        'DepCode'      => 'LAX',
                        'DepName'      => 'Los Angeles International Airport',
                        'ArrDate'      => strtotime("2033-08-03 16:30"),
                        'ArrCode'      => 'JFK',
                        'ArrName'      => 'JF Kennedy Airport',
                        'FlightNumber' => 'INDF1',
                    ],
                ],
            ],
        ];
    }
}
