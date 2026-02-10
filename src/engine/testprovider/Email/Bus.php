<?php

namespace AwardWallet\Engine\testprovider\Email;

class Bus extends \TAccountChecker
{
    public const BUS_FROM = 'bus@test.awardwallet.com';

    public function detectEmailByHeaders(array $headers)
    {
        return false;

        return !empty($headers['from']) && $headers['from'] == self::BUS_FROM;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        return false;

        if (strpos($parser->emailRawContent, self::BUS_FROM) !== false) {
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return false;
    }

    public function ParsePlanEmail(\PlancakeEmailParser $parser)
    {
        if ($parser->getHeader('from') == self::BUS_FROM || strpos($parser->emailRawContent, self::BUS_FROM) !== false) {
            return [
                "parsedData" => [
                    "Itineraries" => [
                        [
                            'Kind'            => 'T',
                            'RecordLocator'   => 'TESTBUS',
                            'Passengers'      => 'John Smith, Katy Smith',
                            'ReservationDate' => strtotime('2012-01-01'),
                            'TripCategory'    => TRIP_CATEGORY_BUS,
                            'TripSegments'    => [
                                [
                                    'Duration' => '3:55',
                                    'DepDate'  => strtotime('2030-01-01 14:55'),
                                    'DepCode'  => TRIP_CODE_UNKNOWN,
                                    'DepName'  => 'New York, 350 Fifth Avenue',
                                    'ArrDate'  => strtotime('2030-01-01 16:55'),
                                    'ArrCode'  => TRIP_CODE_UNKNOWN,
                                    'ArrName'  => 'Buffalo, NY',
                                    'Seats'    => '23, 24',
                                    'Type'     => 'Private Car',
                                ],
                            ],
                        ],
                    ],
                ],
                "emailType" => "bus",
            ];
        }

        return [];
    }
}
