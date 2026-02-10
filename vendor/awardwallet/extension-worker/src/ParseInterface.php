<?php

namespace AwardWallet\ExtensionWorker;

use AwardWallet\Schema\Parser\Component\Master;

interface ParseInterface
{

    /**
     * собирает данные с сайта
     * На момент вызова пользователь уже авторизован
     */
    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions) : void;

}