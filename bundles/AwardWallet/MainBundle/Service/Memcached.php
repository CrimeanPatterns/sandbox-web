<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Service\Memcached\Noop;

class Memcached extends \Memcached {
    const UPDATE_MAX_RETRIES = 20;

    public $prefix;

	public function __construct($host, $prefix){
	    if($prefix === "random")
	        $prefix = bin2hex(random_bytes(10));
	    $this->prefix = $prefix;
		parent::__construct("appCache_binary_" . $host . getmypid());
		if(count($this->getServerList()) == 0){
			$this->addServer($host, 11211);
		}
        $this->setOption(\Memcached::OPT_RECV_TIMEOUT, 500);
        $this->setOption(\Memcached::OPT_SEND_TIMEOUT, 500);
        $this->setOption(\Memcached::OPT_CONNECT_TIMEOUT, 500);
        $this->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
        // this option affects performance, login speed, required for AntiBruteforceLocker
        $this->setOption(\Memcached::OPT_TCP_NODELAY, true);
        $this->setOption(\Memcached::OPT_PREFIX_KEY, $prefix);
	}

    /**
     * callback function should return MemcachedItem
     *
     * @param $key
     * @param callable $callback
     * @return mixed
     */
	public function getThrough($key, Callable $callback){
	    $result = $this->get($key);
        if($result === false && $this->getResultCode() == self::RES_NOTFOUND){
            /** @var MemcachedItem $item */
            $item = call_user_func($callback);
            if($item->cache)
                $this->set($key, $item->data, $item->expiration);
            $result = $item->data;
        }
        return $result;
    }

    /**
     * @param callable $updater function (mixed $data, bool $firstTime) -> mixed|\AwardWallet\MainBundle\Service\Memcached\Noop
     *    Called to provide value for cache item.
     *
     *    $data: If something has already stored in cache it will be passed
     *           via $data argument, otherwise null will be passed.
     *
     *    $firstTime: whether this is the first (when no existed values found) call to updater.
     *
     *    return: value to store in cache or
     *            \AwardWallet\MainBundle\Service\Memcached\Noop instance to stop iterations
     *
     * @return bool whether key was updated
     */
    public function update(string $key, callable $updater, int $expiration = 0, int $retries = self::UPDATE_MAX_RETRIES) : bool
    {
        for ($n = 1; $n <= $retries; $n++) {
            $data = $this->get($key, null, self::GET_EXTENDED);
            $memcachedResultCode = $this->getResultCode();

            if (self::RES_SUCCESS === $memcachedResultCode) {
                ['value' => $value, 'cas' => $casToken] = $data;
                $newValue = $updater($value, false);

                if ($newValue instanceof Noop) {
                    return false;
                }

                $this->cas($casToken, $key, $newValue, $expiration);

                if (\in_array($this->getResultCode(), [self::RES_STORED, self::RES_SUCCESS], true)) {
                    return true;
                } else {
                    continue;
                }
            } elseif (self::RES_NOTFOUND === $memcachedResultCode) {
                $newValue = $updater(null, true);

                if ($newValue instanceof Noop) {
                    return false;
                }

                $this->add($key, $newValue, $expiration);

                if (\in_array($this->getResultCode(), [self::RES_STORED, self::RES_SUCCESS], true)) {
                    return true;
                } else {
                    continue;
                }
            }
        }

        return false;
    }
}