<?php

namespace AwardWallet\Common\Memcached;

class Item
{

    public $data;
    public $expiration;
    /**
     * @var bool
     */
    public $cache;

    public function __construct($data, $expiration = 0, $cache = true)
    {
        $this->data = $data;
        $this->expiration = $expiration;
        $this->cache = $cache;
    }

}