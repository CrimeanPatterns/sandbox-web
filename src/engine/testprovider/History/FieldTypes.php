<?php

namespace AwardWallet\Engine\testprovider\History;

use AwardWallet\Engine\testprovider\Success;

class FieldTypes extends Success
{
    public function GetHistoryColumns()
    {
        return [
            "Post Date"   => "PostingDate",
            "Description" => "Description",

            "Starpoints"    => "Miles",
            "Miles Balance" => "MilesBalance",

            "Type"            => "Info",
            "Eligible Nights" => "Info",

            "Amount"         => "Amount",
            "Amount Balance" => "AmountBalance",

            "Currency" => "Currency",
            "Category" => "Category",
            "Bonus"    => "Bonus",
        ];
    }

    public function ParseHistory($startDate = null)
    {
        return [
            [
                'Post Date'   => 1445904000,
                'Description' => 'PURCHASED POINTS-MEMBER SELF',

                'Starpoints'    => 100,
                'Miles Balance' => 200,

                'Type'            => 'Bonus',
                'Eligible Nights' => '-',

                'Currency' => 'USD',
                'Category' => 'Airlines',
                'Bonus'    => '+2,500',
            ],
        ];
    }
}
