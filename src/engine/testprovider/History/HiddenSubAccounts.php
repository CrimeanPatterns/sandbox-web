<?php

namespace AwardWallet\Engine\testprovider\History;

use AwardWallet\Engine\testprovider\Success;

class HiddenSubAccounts extends Success
{
    public function GetHistoryColumns()
    {
        return [
            "Post Date"               => "PostingDate",
            "Description"             => "Description",
            "Miles"                   => "Miles",
            "Category"                => "Category",
            "Amount"                  => "Amount",
            "Currency"                => "Currency",
            "Transaction Description" => "Info",
        ];
    }

    public function Parse()
    {
        $this->SetBalance(11);

        $this->AddSubAccount([
            "Balance"     => rand(1000, 9999),
            "Code"        => "SubAcc0",
            "DisplayName" => "SubAccount 0",
        ]);

        $this->AddSubAccount([
            "Balance"     => null,
            "Code"        => "SubAcc1",
            "IsHidden"    => true,
            "DisplayName" => "SubAccount 1",
            "HistoryRows" => [
                [
                    'Post Date'   => 1445904000,
                    'Amount'      => 250,
                    'Miles'       => 250,
                    'Currency'    => 'USD',
                    'Description' => 'Subacc1 hist 1',
                ],
                [
                    'Post Date'               => 1439424000,
                    'Amount'                  => 137,
                    'Miles'                   => 548,
                    'Currency'                => 'USD',
                    'Transaction Description' => '+3 Travel',
                    'Category'                => 'Travel',
                    'Description'             => 'Subacc1 hist 2',
                ],
            ],
        ]);

        $this->AddSubAccount([
            "Balance"     => null,
            "Code"        => "SubAcc2",
            "DisplayName" => "SubAccount 2",
            "IsHidden"    => true,
            "HistoryRows" => [
                [
                    'Post Date'               => 1445904000,
                    'Amount'                  => 72,
                    'Miles'                   => 144,
                    'Currency'                => 'USD',
                    'Transaction Description' => '+1 Apple Pay',
                    'Description'             => 'Subacc2 hist 1',
                ],
                [
                    'Post Date'   => 1439424000,
                    'Amount'      => 125,
                    'Miles'       => 125,
                    'Currency'    => 'USD',
                    'Description' => 'Subacc2 hist 2',
                ],
            ],
        ]);
    }
}
