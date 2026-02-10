<?php

namespace AwardWallet\Engine\testprovider\Itineraries;

use AwardWallet\Engine\testprovider\Success;

class Meeting extends Success
{
    public function ParseItineraries()
    {
        return [
            [
                'Kind'        => 'E',
                'ConfNo'      => '123456789',
                'Name'        => 'Landing on Mars with Musk',
                'StartDate'   => strtotime('12 may 2030, 12:00'),
                'EndDate'     => strtotime('12 may 2030, 14:00'),
                'Address'     => 'Mars',
                'Phone'       => '122-236-785',
                'DinerName'   => 'Elon Musk',
                'Guests'      => 2,
                'TotalCharge' => 8000000,
                'Currency'    => 'USD',
                'EventType'   => EVENT_MEETING,
            ],
        ];
    }
}
