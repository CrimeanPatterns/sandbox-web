<?php

namespace AwardWallet\Engine\testprovider\History;

use AwardWallet\Engine\testprovider\Success;

class PostingDateError extends Success
{
    public function GetHistoryColumns()
    {
        return [
            "Activity Date"        => "PostingDate",
            "Description"          => "Description",
            "Elite Sectors Earned" => "Info",
            "Elite Miles Earned"   => "Info",
            "Bonus"                => "Bonus",
            "Enrich Miles"         => "Miles",
        ];
    }

    public function ParseHistory($startDate = null)
    {
        return [
            [
                'Activity Date'        => 1346025600, // 00:00 27 Aug 2012
                'Description'          => 'ENRICH PARTNER',
                'Elite Miles Earned'   => '0',
                'Elite Sectors Earned' => '0',
                'Enrich Miles'         => '1409',
            ],
            [
                'Activity Date'        => false, // 00:00 27 Aug 2012
                'Description'          => 'MAS',
                'Elite Miles Earned'   => '0',
                'Elite Sectors Earned' => '0',
                'Enrich Miles'         => '2844',
            ],
            [
                'Activity Date'        => 1346025600, // 00:00 27 Aug 2012
                'Description'          => 'CIMB',
                'Elite Miles Earned'   => '0',
                'Elite Sectors Earned' => '0',
                'Enrich Miles'         => '26336',
            ],
            [
                'Activity Date'        => 1343606400, // 00:00 30 Jul 2012
                'Description'          => 'CIMB',
                'Elite Miles Earned'   => '0',
                'Elite Sectors Earned' => '0',
                'Enrich Miles'         => '80',
            ],
            [
                'Activity Date'        => 'false', // 00:00 27 Jul 2012
                'Description'          => 'CIMB',
                'Elite Miles Earned'   => '0',
                'Elite Sectors Earned' => '0',
                'Enrich Miles'         => '876',
            ],
            [
                'Activity Date'        => 1343347200, // 00:00 27 Jul 2012
                'Description'          => 'ENRICH PARTNER',
                'Elite Miles Earned'   => '0',
                'Elite Sectors Earned' => '0',
                'Enrich Miles'         => '2084',
            ],
        ];
    }
}
