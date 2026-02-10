<?php

namespace AwardWallet\ExtensionWorker;

interface ActiveTabInterface
{

    /**
     * @return bool - будет ли открытая для парсинга вкладка активной
     */
    public function isActiveTab(AccountOptions $options): bool;

}