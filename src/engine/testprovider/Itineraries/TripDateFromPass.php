<?php

namespace AwardWallet\Engine\testprovider\Itineraries;

use AwardWallet\Engine\testprovider\Success;

class TripDateFromPass extends Success
{
    public function ParseItineraries()
    {
        return [
            [
                'Kind'          => 'T',
                'RecordLocator' => 'TRDEP1',
                'TripSegments'  => [
                    [
                        'AirlineName'  => 'Test Airlines',
                        'Duration'     => '2:00',
                        'DepDate'      => strtotime($this->AccountFields['Pass']),
                        'DepCode'      => 'LAX',
                        'DepName'      => 'Los Angeles International Airport',
                        'ArrDate'      => strtotime($this->AccountFields['Pass']) + 7200,
                        'ArrCode'      => 'JFK',
                        'ArrName'      => 'JF Kennedy Airport',
                        'FlightNumber' => 'DP123',
                        'Gate'         => '17',
                        'BaggageClaim' => '7',
                    ],
                ],
            ],
        ];
    }
}
