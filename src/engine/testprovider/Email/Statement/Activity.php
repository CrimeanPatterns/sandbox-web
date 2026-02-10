<?php

namespace AwardWallet\Engine\testprovider\Email\Statement;

class Activity extends \TAccountChecker
{
    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        return stripos($parser->getHTMLBody(), 'New Tools, Profitable Tips, And Fresh Opportunities') !== false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return false;
    }

    public function ParsePlanEmail(\PlancakeEmailParser $parser)
    {
        $result = [];

        for ($n = 0; $n < 10; $n++) {
            $date = strtotime("2012-01-01") + SECONDS_PER_DAY * 30 * $n;
            $result[] = [
                "No."           => $n,
                "Activity Date" => $date,
                "Activity"      => "Activity $n",
                "Description"   => "Description $n",
                "Award Miles"   => $n * 100,
            ];
        }

        return [
            'parsedData' => [
                'Properties' => [
                    'Balance' => intval(date('Hi')),
                ],
                'Activity' => $result,
            ],
            'emailType' => 'History',
        ];
    }
}
