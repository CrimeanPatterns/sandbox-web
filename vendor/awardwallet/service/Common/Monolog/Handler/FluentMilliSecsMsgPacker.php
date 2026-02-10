<?php

namespace AwardWallet\Common\Monolog\Handler;

use Fluent\Logger\Entity;
use Fluent\Logger\PackerInterface;
use MessagePack\Packer;

class FluentMilliSecsMsgPacker implements PackerInterface
{

    /**
     * @var Packer
     */
    private $packer;

    public function __construct()
    {
        $this->packer = new Packer();
    }

    public function pack(Entity $entity)
    {
        return $this->packer->pack([$entity->getTag(), new MilliSecsExt($entity->getTime(), $this->packer), $entity->getData()]);
    }

}
