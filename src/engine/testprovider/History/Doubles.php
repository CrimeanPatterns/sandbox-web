<?php

namespace AwardWallet\Engine\testprovider\History;

use AwardWallet\Engine\testprovider\Success;

class Doubles extends Success
{
    public function GetHistoryColumns()
    {
        return [
            "No."           => "Info",
            "Activity Date" => "PostingDate",
            "Activity"      => "Info",
            "Description"   => "Description",
            "Award Miles"   => "Miles",
        ];
    }

    public function ParseHistory($startDate = null)
    {
        return [
            [
                'Activity Date' => 1445904000,
                'Activity'      => 'Bonus',
                'Description'   => 'PURCHASED POINTS-MEMBER SELF 1',
            ],
            [
                'Activity Date' => 1439424000,
                'Activity'      => 'Bonus',
                'Description'   => 'PURCHASED POINTS-MEMBER SELF 2',
            ],
            [
                'Activity Date' => 1439424000,
                'Activity'      => 'Bonus',
                'Description'   => 'PURCHASED POINTS-MEMBER SELF 2',
            ],
        ];
    }
}
