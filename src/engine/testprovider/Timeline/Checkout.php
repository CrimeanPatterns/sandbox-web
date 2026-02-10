<?php

namespace AwardWallet\Engine\testprovider\Timeline;

use AwardWallet\Engine\testprovider\Success;

class Checkout extends Success
{
    public function ParseItineraries()
    {
        return [
            [
                'Kind'               => 'R',
                'ConfirmationNumber' => 'CHECKOUT1',
                'HotelName'          => 'Near JFK Hotel',
                'CheckInDate'        => $this->clipSecondsFromTimeStamp(strtotime("2037-08-01")),
                'CheckOutDate'       => $this->clipSecondsFromTimeStamp(strtotime("2037-08-03")),
                'Address'            => 'JFK Airport',
            ],
            [
                'Kind'          => 'T',
                'RecordLocator' => 'FLYOUT1',
                'TripSegments'  => [
                    [
                        'AirlineName'  => 'Timeline Airlines',
                        'Duration'     => '2:00',
                        'DepDate'      => $this->clipSecondsFromTimeStamp(strtotime("2037-08-03 10:00")),
                        'DepCode'      => 'JFK',
                        'DepName'      => 'JF Kennedy Airport',
                        'ArrDate'      => $this->clipSecondsFromTimeStamp(strtotime("2037-08-03 11:30")),
                        'ArrCode'      => 'LAX',
                        'ArrName'      => 'Los Angeles International Airport',
                        'FlightNumber' => '44562',
                    ],
                ],
            ],
        ];
    }
}
