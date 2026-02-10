<?php

namespace AwardWallet\Engine\testprovider\Itineraries;

use AwardWallet\Engine\testprovider\Success;

class SpecialChars extends Success
{
    public function ParseItineraries()
    {
        return [
            [
                'Kind'          => 'T',
                'RecordLocator' => 'SPLCHR',
                'TripSegments'  => [
                    [
                        'DepCode' => 'JFK',
                        'ArrCode' => 'LAX',
                        'DepDate' => strtotime("tomorrow 10:00"),
                        'ArrDate' => strtotime("tomorrow 13:00"),
                        'Seats'   =>
                            [
                                0 => '9C',
                            ],
                        'BookingClass' => 'Q',
                        'Cabin'        => 'No Smoking',
                        'Aircraft'     => 'Super jet ©',
                        'FlightNumber' => '5138',
                        'AirlineName'  => 'Super © Airlines',
                    ],
                ],
            ],
        ];
    }
}
