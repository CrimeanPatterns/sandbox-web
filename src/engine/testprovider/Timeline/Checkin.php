<?php

namespace AwardWallet\Engine\testprovider\Timeline;

use AwardWallet\Engine\testprovider\Success;

class Checkin extends Success
{
    public function ParseItineraries()
    {
        return [
            [
                'Kind'               => 'R',
                'ConfirmationNumber' => 'CHECKIN1',
                'HotelName'          => 'Near JFK Hotel',
                'CheckInDate'        => $this->clipSecondsFromTimeStamp(strtotime($this->Answers["CheckinDate"])),
                'CheckOutDate'       => $this->clipSecondsFromTimeStamp(strtotime("+3 days", strtotime($this->Answers["CheckinDate"]))),
                'Address'            => 'JFK Airport',
            ],
            [
                'Kind'          => 'T',
                'RecordLocator' => 'FLYIN1',
                'TripSegments'  => [
                    [
                        'AirlineName'  => 'Timeline Airlines',
                        'Duration'     => '2:00',
                        'DepDate'      => $this->clipSecondsFromTimeStamp(strtotime("-8 hours", strtotime($this->Answers["ArrivalDate"]))),
                        'DepCode'      => 'LAX',
                        'DepName'      => 'Los Angeles International Airport',
                        'ArrDate'      => $this->clipSecondsFromTimeStamp(strtotime($this->Answers["ArrivalDate"])),
                        'ArrCode'      => 'JFK',
                        'ArrName'      => 'JF Kennedy Airport',
                        'FlightNumber' => '55678',
                    ],
                ],
            ],
        ];
    }
}
