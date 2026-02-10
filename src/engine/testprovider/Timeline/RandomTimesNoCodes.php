<?php

namespace AwardWallet\Engine\testprovider\Timeline;

use AwardWallet\Engine\testprovider\Success;

class RandomTimesNoCodes extends Success
{
    public function ParseItineraries()
    {
        $result = [
            [
                'Kind'          => 'T',
                'RecordLocator' => 'BUSRAND1',
                'TripCategory'  => TRIP_CATEGORY_TRAIN,
                'TripSegments'  => [
                    [
                        'FlightNumber' => FLIGHT_NUMBER_UNKNOWN,
                        'AirlineName'  => 'Random times',
                        'Duration'     => '2:00',
                        'DepCode'      => TRIP_CODE_UNKNOWN,
                        'DepDate'      => $this->clipSecondsFromTimeStamp(strtotime("2037-08-03 9:00") + rand(0, 60 * 4) * 60),
                        'DepName'      => 'New York',
                        'ArrCode'      => TRIP_CODE_UNKNOWN,
                        'ArrDate'      => $this->clipSecondsFromTimeStamp(strtotime("2037-08-03 16:30") + rand(0, 60 * 3) * 60),
                        'ArrName'      => 'Los Angeles',
                    ],
                    [
                        'FlightNumber' => FLIGHT_NUMBER_UNKNOWN,
                        'AirlineName'  => 'Stable times',
                        'Duration'     => '2:00',
                        'DepCode'      => TRIP_CODE_UNKNOWN,
                        'DepDate'      => $this->clipSecondsFromTimeStamp(strtotime("2037-09-03 9:00")),
                        'DepName'      => 'Miami',
                        'ArrCode'      => TRIP_CODE_UNKNOWN,
                        'ArrDate'      => $this->clipSecondsFromTimeStamp(strtotime("2037-09-03 16:30")),
                        'ArrName'      => 'San Hose',
                    ],
                ],
            ],
        ];

        if ($this->AccountFields['Pass'] == 'desc') {
            $result[0]['TripSegments'] = array_reverse($result[0]['TripSegments']);
        }

        return $result;
    }
}
