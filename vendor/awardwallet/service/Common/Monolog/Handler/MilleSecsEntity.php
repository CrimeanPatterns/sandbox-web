<?php

namespace AwardWallet\Common\Monolog\Handler;

use Fluent\Logger\Entity;

class MilleSecsEntity extends Entity
{

    public function __construct($tag, $data, $dateTime)
    {
        parent::__construct($tag, $data);

        $this->time = $dateTime;
    }

}
