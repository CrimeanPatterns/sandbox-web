<?php

namespace AwardWallet\Engine\testprovider\Itineraries;

use AwardWallet\Engine\testprovider\Success;

class OneAccountNumber extends Success
{
    public function Parse()
    {
        $this->SetBalance(10);
        $this->SetProperty("Number", "111222333");
    }

    public function ParseItineraries()
    {
        return [
            [
                'Kind'           => 'T',
                'RecordLocator'  => 'IT1',
                'AccountNumbers' => '111222333',
                'TripSegments'   => [
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
        ];
    }
}
