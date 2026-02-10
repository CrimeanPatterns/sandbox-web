<?php

namespace AwardWallet\Engine\testprovider\Itineraries;

use AwardWallet\Engine\testprovider\Success;

class AaOvernight extends Success
{
    public function ParseItineraries()
    {
        return [
            [
                'Kind'          => 'T',
                'RecordLocator' => 'AAON1',
                'TripSegments'  => [
                    [
                        'DepCode' => 'CLT',
                        'ArrCode' => 'TYS',
                        'DepDate' => 1449172500,
                        'ArrDate' => 1449176040,
                        'Seats'   =>
                            [
                                0 => '2A',
                            ],
                        'BookingClass' => 'X',
                        'Cabin'        => 'F',
                        'Aircraft'     => 'CR7',
                        'FlightNumber' => '5221',
                        'AirlineName'  => 'AA',
                    ],
                    [
                        'DepCode' => 'TYS',
                        'ArrCode' => 'CLT',
                        'DepDate' => strtotime("tomorrow 14:00"),
                        'ArrDate' => strtotime("tomorrow 13:00"),
                        'Seats'   =>
                            [
                                0 => '9C',
                            ],
                        'BookingClass' => 'Q',
                        'Cabin'        => 'Y',
                        'Aircraft'     => 'CR9',
                        'FlightNumber' => '5138',
                        'AirlineName'  => 'AA',
                    ],
                ],
            ],
        ];
    }
}
