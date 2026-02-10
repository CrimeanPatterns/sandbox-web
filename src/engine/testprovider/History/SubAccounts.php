<?php

namespace AwardWallet\Engine\testprovider\History;

use AwardWallet\Engine\testprovider\Success;

class SubAccounts extends Success
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

    public function Parse()
    {
        $this->SetBalance(10);

        $subAccsHistory = $this->getSubaccHistoryRows();
        // filter by HistorySubAccountStartDates
        foreach ($subAccsHistory as $code => $rows) {
            if (empty($this->getSubAccountHistoryStartDate($code))) {
                continue;
            }

            foreach ($rows as $key => $row) {
                if ($row['Post Date'] <= $this->getSubAccountHistoryStartDate($code)) {
                    unset($subAccsHistory[$code][$key]);
                }
            }
        }

        $this->AddSubAccount([
            "Balance"     => 1,
            "Code"        => "SubAcc1",
            "DisplayName" => "SubAccount 1",
            "HistoryRows" => $subAccsHistory['SubAcc1'],
        ]);
        $this->AddSubAccount([
            "Balance"     => 2,
            "Code"        => "SubAcc2",
            "DisplayName" => "SubAccount 2",
            "HistoryRows" => $subAccsHistory['SubAcc2'],
        ]);
    }

    public function ParseHistory($startDate = null)
    {
        $history = [
            [
                'Post Date'       => 1445904000,
                'Type'            => 'Bonus',
                'Eligible Nights' => '-',
                'Bonus'           => '+2,500',
                'Description'     => 'Main acc hist 1',
            ],
            [
                'Post Date'       => 1439424000,
                'Type'            => 'Award',
                'Eligible Nights' => '-',
                'Starpoints'      => '-2,500',
                'Description'     => 'Main acc hist 2',
            ],
            [
                'Post Date'       => 1439424000,
                'Type'            => 'Award',
                'Eligible Nights' => '-',
                'Starpoints'      => '-2,400',
                'Description'     => 'Main acc hist 3',
            ],
        ];

        // filter by HistoryStartDate
        if (!empty($this->HistoryStartDate)) {
            foreach ($history as $key => $row) {
                if ($row['Post Date'] <= $this->HistoryStartDate) {
                    unset($history[$key]);
                }
            }
        }

        return $history;
    }

    private function getSubaccHistoryRows()
    {
        $subAccsHistory = [
            'SubAcc1' => [
                [
                    'Post Date'       => 1445904000,
                    'Type'            => 'Bonus',
                    'Eligible Nights' => '-',
                    'Bonus'           => '+2,500',
                    'Description'     => 'Subacc1 hist 1',
                ],
                [
                    'Post Date'       => 1439424000,
                    'Type'            => 'Award',
                    'Eligible Nights' => '-',
                    'Starpoints'      => '-2,500',
                    'Description'     => 'Subacc1 hist 2',
                ],
            ],
            'SubAcc2' => [
                [
                    'Post Date'       => 1445904000,
                    'Type'            => 'Bonus',
                    'Eligible Nights' => '-',
                    'Bonus'           => '+2,500',
                    'Description'     => 'Subacc2 hist 1',
                ],
                [
                    'Post Date'       => 1439424000,
                    'Type'            => 'Award',
                    'Eligible Nights' => '-',
                    'Starpoints'      => '-2,500',
                    'Description'     => 'Subacc2 hist 2',
                ],
            ],
        ];

        if ($this->AccountFields['Login2'] === 'invalid.parsing') {
            $subAccsHistory['SubAcc1'][0] = [
                'Post Date'       => false,
                'Type'            => 'Bonus',
                'Eligible Nights' => '-',
                'Bonus'           => '+2,500',
                'DescriptionText' => 'Subacc1 hist 1',
            ];
        }

        return $subAccsHistory;
    }
}
