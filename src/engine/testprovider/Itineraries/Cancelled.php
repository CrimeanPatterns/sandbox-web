<?php

namespace AwardWallet\Engine\testprovider\Itineraries;

use AwardWallet\Engine\testprovider\Success;

class Cancelled extends Success
{
    public function ParseItineraries()
    {
        $lastTime = time();
        $lastTime = $lastTime - $lastTime % 60;

        return [
            [
                'Kind'          => 'T',
                'TripCategory'  => TRIP_CATEGORY_AIR,
                'RecordLocator' => '23645266373',
                'Passengers'    => [
                    'John Johnson',
                    'Molly Johnson',
                ],
                'AccountNumbers'  => 'T82399832, T0392820392',
                'TotalCharge'     => '2344.22',
                'BaseFare'        => '2000.22',
                'Currency'        => 'USD',
                'Tax'             => '200.2',
                'SpentAwards'     => '1000 miles',
                'EarnedAwards'    => '500 miles',
                'Status'          => 'confirmed',
                'ReservationDate' => time() - SECONDS_PER_DAY * 32,
                'TripSegments'    => [
                    [
                        'DepDate'       => $lastTime += SECONDS_PER_DAY * 14,
                        'DepCode'       => 'JFK',
                        'DepName'       => 'JF Kennedy Airport',
                        'ArrDate'       => $lastTime += 235 * 60,
                        'ArrCode'       => 'LAX',
                        'ArrName'       => 'Los Angeles International Airport',
                        'FlightNumber'  => 'TE223',
                        'AirlineName'   => 'Test Airlines',
                        'Aircraft'      => 'Test Aircraft 203',
                        'TraveledMiles' => 500,
                        'Cabin'         => 'Economy',
                        'BookingClass'  => 'T',
                        'Seats'         => '2G, 14F',
                        'Duration'      => '5h',
                        'Meal'          => 'vegan',
                        'Stops'         => 0,
                    ],
                ],
            ],
            [
                'Kind'          => 'T',
                'RecordLocator' => '9878957352490',
                'Cancelled'     => true,
            ],
        ];
    }
}
