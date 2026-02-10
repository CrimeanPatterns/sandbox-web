<?php

namespace AwardWallet\Engine\testprovider\Timeline;

use AwardWallet\Engine\testprovider\Success;

class RandomFlightNumber extends Success
{
    public function ParseItineraries()
    {
        return [
            [
                'Kind'          => 'T',
                'RecordLocator' => 'FLYRFN',
                'TripSegments'  => [
                    [
                        'AirlineName'  => 'Timeline Airlines',
                        'Duration'     => '2:00',
                        'Seats'        => '1A',
                        'DepDate'      => $this->clipSecondsFromTimeStamp(strtotime("2037-08-03 9:00")),
                        'DepCode'      => 'LAX',
                        'DepName'      => 'Los Angeles International Airport',
                        'ArrDate'      => $this->clipSecondsFromTimeStamp(strtotime("2037-08-03 16:30")),
                        'ArrCode'      => 'JFK',
                        'ArrName'      => 'JF Kennedy Airport',
                        'FlightNumber' => 'R' . rand(100000, 999999),
                        'Gate'         => '17',
                        'BaggageClaim' => '7',
                    ],
                ],
            ],
        ];
    }
}
