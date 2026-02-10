<?php

namespace AwardWallet\Common\Monolog\Handler;

use MessagePack\CanBePacked;
use MessagePack\Packer;

class MilliSecsExt implements CanBePacked
{

    /**
     * @var \DateTime
     */
    private $time;
    /**
     * @var Packer
     */
    private $packer;

    public function __construct(\DateTime $time, Packer $packer)
    {
        $this->time = $time;
        $this->packer = $packer;
    }

    public function pack(Packer $packer) : string
    {
        $secs = $this->time->getTimestamp();
        $nanos = (int) $this->time->format('u') * 1000;
        $data = \pack('NN', (int) $secs, (int) $nanos);
        // https://github.com/fluent/fluentd/wiki/Forward-Protocol-Specification-v0#eventtime-ext-format
        return $packer->packExt(0xc7, $data);
    }

}
