<?php

namespace AwardWallet\Common\Memcached;

class Factory
{

    public function create(string $host, int $port = 11211): \Memcached
    {
        $result = new \Memcached("appCache_binary_" . $host);
        if (count($result->getServerList()) == 0) {
            $result->addServer($host, $port);
        }
        $result->setOption(\Memcached::OPT_RECV_TIMEOUT, 500);
        $result->setOption(\Memcached::OPT_SEND_TIMEOUT, 500);
        $result->setOption(\Memcached::OPT_CONNECT_TIMEOUT, 500);
        $result->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
        // this option affects performance, login speed, required for AntiBruteforceLocker
        $result->setOption(\Memcached::OPT_TCP_NODELAY, true);

        return $result;
    }

}