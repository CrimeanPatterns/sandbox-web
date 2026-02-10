<?php

namespace AwardWallet\Engine\testprovider\Itineraries;

use AwardWallet\Engine\testprovider\Success;

class NoItineraries extends Success
{
    public const NO_ITINERARIES_PASS = 'NoItineraries';

    public function ParseItineraries()
    {
        if ($this->AccountFields['Pass'] == self::NO_ITINERARIES_PASS) {
            return $this->noItinerariesArr();
        }
        echo "returning trip\n";

        return [
            [
                'Kind'          => 'T',
                'RecordLocator' => 'FLYIN1',
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
                        'FlightNumber' => 'OUTSEG1',
                    ],
                ],
            ],
        ];
    }
}
