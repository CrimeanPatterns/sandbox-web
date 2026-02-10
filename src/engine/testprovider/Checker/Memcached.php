<?php

namespace AwardWallet\Engine\testprovider\Checker;

use AwardWallet\Engine\testprovider\Success;

class Memcached extends Success
{
    public function Parse()
    {
        $cache = $this->getMemcached();
        $key = "checker_cache_test_" . microtime(true);
        $value = rand(0, 999999) . microtime(true);

        if (!$cache->set($key, $value)) {
            throw new \CheckException("Failed to store cache", ACCOUNT_PROVIDER_ERROR);
        }

        if ($value != $cache->get($key)) {
            throw new \CheckException("Failed to read cache", ACCOUNT_PROVIDER_ERROR);
        }

        $this->SetBalance(1);
    }
}
