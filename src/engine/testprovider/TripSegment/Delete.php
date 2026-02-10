<?php

namespace AwardWallet\Engine\testprovider\TripSegment;

use AwardWallet\Engine\testprovider\Success;

class Delete extends Success
{
    public function ParseItineraries()
    {
        $segments = [
            [
                'AirlineName'  => 'Timeline Airlines',
                'Duration'     => '2:00',
                'Seats'        => '32A',
                'DepDate'      => strtotime("2037-08-03 9:00"),
                'DepCode'      => 'LAX',
                'DepName'      => 'Los Angeles International Airport',
                'ArrDate'      => strtotime("2037-08-03 16:30"),
                'ArrCode'      => 'JFK',
                'ArrName'      => 'John F. Kennedy International Airport',
                'FlightNumber' => 'DEL1',
            ],
        ];

        if ($this->AccountFields['Login2'] != 'delete') {
            $segments[] = [
                'AirlineName'  => 'Timeline Airlines',
                'Duration'     => '2:00',
                'Seats'        => '32A',
                'DepDate'      => strtotime("2037-08-03 9:00"),
                'DepCode'      => 'JFK',
                'DepName'      => 'John F. Kennedy International Airport',
                'ArrDate'      => strtotime("2037-08-03 16:30"),
                'ArrCode'      => 'PEE',
                'ArrName'      => 'Perm',
                'FlightNumber' => 'DEL2',
            ];
        }

        return [
            [
                'Kind'          => 'T',
                'RecordLocator' => 'FLYDEL1',
                'TripSegments'  => $segments,
            ],
        ];
    }
}
