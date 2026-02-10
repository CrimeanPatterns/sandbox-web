<?php

namespace AwardWallet\Engine\testprovider\History;

use AwardWallet\Engine\testprovider\Success;

class BankTransactions extends Success
{
    public function GetHistoryColumns()
    {
        return [
            "Additional"    => "Info",
            "Activity Date" => "PostingDate",
            "Description"   => "Description",
            "Award Miles"   => "Miles",
            //            "Bonus" => "Bonus",
            "Amount"         => "Amount",
            "Amount Balance" => "AmountBalance",
            "Miles Balance"  => "MilesBalance",
            "Currency"       => "Currency",
            "Category"       => "Category",
        ];
    }

    public function ParseHistory($startDate = null)
    {
        return [
            [
                "Activity Date"  => 1493251200,
                "Description"    => "G2S*Hola Networks",
                "Amount"         => 100,
                "Award Miles"    => 500,
                "Currency"       => "USD",
                "Category"       => "Buy",
                "Additional"     => "Bonus earn",
                "Amount Balance" => 5200.45,
                "Miles Balance"  => 13182.7,
            ],
            [
                "Activity Date"  => 1492819200,
                "Description"    => "POINTSHHONORSREWARDS",
                "Amount"         => 10,
                "Award Miles"    => 10,
                "Currency"       => "USD",
                "Category"       => "Buy",
                "Amount Balance" => 5200.45,
                "Miles Balance"  => 12682.7,
            ],
            [
                "Activity Date"  => 1491955200,
                "Description"    => "RCN*CABLE PHONE INTERN",
                "Amount"         => 90.54,
                "Award Miles"    => 452.7,
                "Currency"       => "USD",
                "Category"       => "Buy",
                "Additional"     => "Bonus earn",
                "Amount Balance" => 5210.45,
                "Miles Balance"  => 12672.7,
            ],
            [
                "Activity Date"  => 1491436800,
                "Description"    => "POINTSHHONORSREWARDS",
                "Amount"         => 10,
                "Award Miles"    => 10,
                "Currency"       => "USD",
                "Category"       => "Buy",
                "Amount Balance" => 5300.99,
                "Miles Balance"  => 12220,
            ],
            [
                "Activity Date"  => 1491436800,
                "Description"    => "POINTSHHONORSREWARDS",
                "Amount"         => 10,
                "Award Miles"    => 10,
                "Currency"       => "USD",
                "Category"       => "Buy",
                "Amount Balance" => 5310.99,
                "Miles Balance"  => 12210,
            ],
            [
                "Activity Date"  => 1491177600,
                "Description"    => "AMAZON MKTPLACE PMTS",
                "Amount"         => 252.81,
                "Award Miles"    => 252.81,
                "Currency"       => "USD",
                "Category"       => "Buy",
                "Amount Balance" => 5320.99,
                "Miles Balance"  => 12200,
            ],
        ];
    }
}
