<?php

namespace AwardWallet\Engine\testprovider\Timeline;

use AwardWallet\Engine\testprovider\Success;

class RandomTimes extends Success
{
    public function ParseItineraries()
    {
        return [
            [
                'Kind'          => 'T',
                'RecordLocator' => 'FLYRAN1',
                'TripSegments'  => [
                    [
                        'AirlineName'  => 'Timeline Airlines',
                        'Duration'     => '2:00',
                        'Seats'        => rand(1, 100) . 'A',
                        'DepDate'      => $this->clipSecondsFromTimeStamp(strtotime("2037-08-03 9:00") + rand(0, 60 * 4) * 60),
                        'DepCode'      => 'LAX',
                        'DepName'      => 'Los Angeles International Airport',
                        'ArrDate'      => $this->clipSecondsFromTimeStamp(strtotime("2037-08-03 16:30") + rand(0, 60 * 3) * 60),
                        'ArrCode'      => 'JFK',
                        'ArrName'      => 'JF Kennedy Airport',
                        'FlightNumber' => '85453',
                        'Gate'         => '17',
                        'BaggageClaim' => '7',
                    ],
                ],
            ],
        ];
    }
}
