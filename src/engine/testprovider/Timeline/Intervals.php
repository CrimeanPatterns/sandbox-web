<?php

namespace AwardWallet\Engine\testprovider\Timeline;

use AwardWallet\Engine\testprovider\Success;

class Intervals extends Success
{
    public function ParseItineraries()
    {
        return [
            [
                'Kind'               => 'R',
                'ConfirmationNumber' => 'CHECKIN1',
                'HotelName'          => 'Near JFK Hotel',
                'CheckInDate'        => $this->clipSecondsFromTimeStamp(strtotime("2030-01-01")),
                'CheckOutDate'       => $this->clipSecondsFromTimeStamp(strtotime("2030-01-02")),
                'Address'            => 'London, England',
            ],
            [
                'Kind'               => 'R',
                'ConfirmationNumber' => 'CHECKIN2',
                'HotelName'          => 'Near JFK Hotel',
                'CheckInDate'        => $this->clipSecondsFromTimeStamp(strtotime("2030-02-01")),
                'CheckOutDate'       => $this->clipSecondsFromTimeStamp(strtotime("2030-02-02")),
                'Address'            => 'London, England',
            ],
            [
                'Kind'               => 'R',
                'ConfirmationNumber' => 'CHECKIN3',
                'HotelName'          => 'Near JFK Hotel',
                'CheckInDate'        => $this->clipSecondsFromTimeStamp(strtotime("2030-02-02")),
                'CheckOutDate'       => $this->clipSecondsFromTimeStamp(strtotime("2030-02-03")),
                'Address'            => 'London, England',
            ],
            [
                'Kind'               => 'R',
                'ConfirmationNumber' => 'CHECKIN4',
                'HotelName'          => 'Near JFK Hotel',
                'CheckInDate'        => $this->clipSecondsFromTimeStamp(strtotime("2030-03-01")),
                'CheckOutDate'       => $this->clipSecondsFromTimeStamp(strtotime("2030-03-02")),
                'Address'            => 'London, England',
            ],
        ];
    }
}
