<?php

namespace AwardWallet\Engine\testprovider\Checker;

use AwardWallet\Engine\testprovider\Success;

class SubAccountsBalance extends Success
{
    public function Parse()
    {
        $this->SetBalance(10);
        $this->SetProperty("CombineSubAccounts", false);

        $this->AddSubAccount([
            'Code'              => 'first',
            'Number'            => 'SubNumber 1',
            'DisplayName'       => 'First subaccount',
            'Balance'           => rand(1, 10000),
            'BalanceInTotalSum' => true,
        ]);
        $this->AddSubAccount([
            'Code'              => 'second',
            'Number'            => 'SubNumber 2',
            'DisplayName'       => 'Second subaccount',
            'Balance'           => rand(1, 10000),
            'BalanceInTotalSum' => true,
        ]);
    }
}
