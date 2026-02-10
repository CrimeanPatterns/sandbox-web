<?php

namespace AwardWallet\Engine\testprovider\History;

use AwardWallet\Engine\testprovider\Success;

class Bonus extends Success
{
    public function GetHistoryColumns()
    {
        return [
            "Type"            => "Info",
            "Eligible Nights" => "Info",
            "Post Date"       => "PostingDate",
            "Description"     => "Description",
            "Starpoints"      => "Miles",
            "Bonus"           => "Bonus",
        ];
    }

    public function ParseHistory($startDate = null)
    {
        return [
            [
                'Post Date'       => 1445904000,
                'Type'            => 'Bonus',
                'Eligible Nights' => '-',
                'Bonus'           => '+2,500',
                'Description'     => 'PURCHASED POINTS-MEMBER SELF',
            ],
            [
                'Post Date'       => 1439424000,
                'Type'            => 'Award',
                'Eligible Nights' => '-',
                'Starpoints'      => '-2,500',
                'Description'     => 'SINGAPORE AIRLINES KRISFLYER 2',
            ],
            [
                'Post Date'       => 1439424000,
                'Type'            => 'Award',
                'Eligible Nights' => '-',
                'Starpoints'      => '-2,400',
                'Description'     => 'SINGAPORE AIRLINES KRISFLYER 1',
            ],
        ];
    }

    public function combineHistoryBonusToMiles()
    {
        return true;
    }
}
