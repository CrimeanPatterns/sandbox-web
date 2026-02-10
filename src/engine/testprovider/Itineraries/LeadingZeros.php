<?php

namespace AwardWallet\Engine\testprovider\Itineraries;

use AwardWallet\Engine\testprovider\Success;

class LeadingZeros extends Success
{
    public function ParseItineraries()
    {
        return [
            [
                'Kind'          => 'T',
                'RecordLocator' => '001203450',
                'TripSegments'  => [
                    [
                        'DepCode'      => 'CLT',
                        'ArrCode'      => 'TYS',
                        'DepDate'      => strtotime("tomorrow"),
                        'ArrDate'      => strtotime("tomorrow") + 3600,
                        'FlightNumber' => '5221',
                        'AirlineName'  => 'AA',
                    ],
                ],
            ],
            [
                'Kind'               => 'R',
                'ConfirmationNumber' => '01203460',
                'HotelName'          => 'Near JFK Hotel',
                'CheckInDate'        => strtotime("2030-02-01"),
                'CheckOutDate'       => strtotime("2030-02-02"),
                'Address'            => 'London, England',
            ],
            [
                'Kind'            => 'L',
                'Number'          => '0123400',
                'PickupDatetime'  => strtotime('2030-01-01 12:20'),
                'PickupLocation'  => 'PHL',
                'DropoffDatetime' => strtotime('2030-01-02 12:20'),
                'DropoffLocation' => 'PHL',
            ],
        ];
    }
}
