<?php

namespace AwardWallet\Engine\testprovider\Itineraries;

use AwardWallet\Engine\testprovider\Success;

class PastItineraries extends Success
{
    public function ParseItineraries()
    {
        $its = [
            [
                'Kind'          => 'T',
                'RecordLocator' => 'TRDEP3',
                'TripSegments'  => [
                    [
                        'AirlineName'  => 'Test Airlines',
                        'Duration'     => '2:10',
                        'DepDate'      => (new \DateTime("9:00"))->getTimestamp() + SECONDS_PER_DAY * 2,
                        'DepCode'      => 'JFK',
                        'DepName'      => 'JF Kennedy Airport',
                        'ArrDate'      => (new \DateTime("9:00"))->getTimestamp() + SECONDS_PER_DAY * 2 + 3600 * 2 + 600,
                        'ArrCode'      => 'LAX',
                        'ArrName'      => 'Los Angeles International Airport',
                        'FlightNumber' => 'DP125',
                        'Gate'         => '8',
                        'BaggageClaim' => '7',
                    ],
                ],
            ],
        ];

        if (!$this->ParsePastIts) {
            return $its;
        }

        $its[] = [
            'Kind'          => 'T',
            'RecordLocator' => 'TRDEP1',
            'TripSegments'  => [
                [
                    'AirlineName'  => 'Test Airlines',
                    'Duration'     => '2:00',
                    'DepDate'      => (new \DateTime("9:00"))->getTimestamp() - SECONDS_PER_DAY * 40,
                    'DepCode'      => 'LAX',
                    'DepName'      => 'Los Angeles International Airport',
                    'ArrDate'      => (new \DateTime("9:00"))->getTimestamp() - SECONDS_PER_DAY * 40 + 3600 * 2,
                    'ArrCode'      => 'JFK',
                    'ArrName'      => 'JF Kennedy Airport',
                    'FlightNumber' => 'DP123',
                    'Gate'         => '17',
                    'BaggageClaim' => '7',
                ],
            ],
        ];
        $its[] = [
            'Kind'                => 'R',
            'ConfirmationNumber'  => '1252463788',
            'HotelName'           => 'National',
            'CheckInDate'         => (new \DateTime("9:00"))->getTimestamp() - SECONDS_PER_DAY * 40 + 3600 * 4,
            'CheckOutDate'        => (new \DateTime("9:00"))->getTimestamp() - SECONDS_PER_DAY * 37,
            'Address'             => '123, Street, Miami',
            'Phone'               => '123-745-856',
            'Guests'              => 3,
            'Kids'                => 0,
            'Rooms'               => 2,
            'Rate'                => '9600 starpoints and USD 180.00',
            'RateType'            => 'Spg Cash & Points Only To Be Booked With A Spg Award Stay. Guest Must Be A Spg.member. Must Redeem Starpoints For Cash And Points Award.',
            'CancellationPolicy'  => '',
            'RoomType'            => 'Superior Romantic Glimmering Room - Earlybird',
            'RoomTypeDescription' => 'Non-Smoking Room Confirmed',
            'Cost'                => 280.60,
            'Taxes'               => 35.10,
            'Total'               => 315.70,
            'Currency'            => 'USD',
        ];

        return $its;
    }
}
