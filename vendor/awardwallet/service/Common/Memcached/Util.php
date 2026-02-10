<?php

namespace AwardWallet\Common\Memcached;

class Util
{

    const UPDATE_MAX_RETRIES = 20;

    /**
     * @var \Memcached
     */
    private $memcached;

    public function __construct(\Memcached $memcached)
    {
        $this->memcached = $memcached;
    }

    /**
     * callback function should return MemcachedItem
     *
     * @param $key
     * @param callable $callback
     * @return mixed
     */
	public function getThrough($key, Callable $callback){
	    $result = $this->memcached->get($key);
        if($result === false && $this->memcached->getResultCode() == \Memcached::RES_NOTFOUND){
            /** @var Item $item */
            $item = call_user_func($callback);
            if($item->cache)
                $this->memcached->set($key, $item->data, $item->expiration);
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
            $data = $this->memcached->get($key, null, \Memcached::GET_EXTENDED);
            $memcachedResultCode = $this->memcached->getResultCode();

            if (\Memcached::RES_SUCCESS === $memcachedResultCode) {
                ['value' => $value, 'cas' => $casToken] = $data;
                $newValue = $updater($value, false);

                if ($newValue instanceof Noop) {
                    return false;
                }

                $this->memcached->cas($casToken, $key, $newValue, $expiration);

                if (\in_array($this->memcached->getResultCode(), [\Memcached::RES_STORED, \Memcached::RES_SUCCESS], true)) {
                    return true;
                } else {
                    continue;
                }
            } elseif (\Memcached::RES_NOTFOUND === $memcachedResultCode) {
                $newValue = $updater(null, true);

                if ($newValue instanceof Noop) {
                    return false;
                }

                $this->memcached->add($key, $newValue, $expiration);

                if (\in_array($this->memcached->getResultCode(), [\Memcached::RES_STORED, \Memcached::RES_SUCCESS], true)) {
                    return true;
                } else {
                    continue;
                }
            }
        }

        return false;
    }
    
}