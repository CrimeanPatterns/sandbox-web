<?php

namespace AwardWallet\Common\Mysql;

class ComebackDriver extends \Facile\DoctrineMySQLComeBack\Doctrine\DBAL\Driver\PDOMySql\Driver
{

    /** @var string[] */
    protected $goneAwayExceptions = [
        'MySQL server has gone away',
        'Lost connection to MySQL server during query',
        'Packets out of order. Expected 1 received 0',
    ];

}