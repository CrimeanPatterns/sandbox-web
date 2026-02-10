<?php
namespace AwardWallet\Common\Memcached;

/*
 * some methods of this class shown as incompatible by phpstorm
 * it's ok, real signatures taken from errors like:
 *  Declaration of AwardWallet\Tests\Classes\MemcachedMock::setOptions(array $options) should be compatible with Memcached::setOptions($options)
 */
class MemcachedMock extends \Memcached
{
    private $storage = [];
    private $resultCode = \Memcached::RES_FAILURE;
    private $cas = 1;

    public function __construct()
    {
    }

    public function clear()
    {
        $this->cas = 1;
        $this->storage = [];
    }

    public function get($key, $cache_cb = NULL, $get_flags = NULL)
    {
        if (!array_key_exists($key, $this->storage)) {
            $this->resultCode = \Memcached::RES_NOTFOUND;
            return false;
        }

        list($value, $expiration, $cas) = $this->storage[$key];

        if (null !== $expiration && time() >= $expiration) {
            $this->resultCode = \Memcached::RES_NOTFOUND;
            return false;
        }

        $this->resultCode = \Memcached::RES_SUCCESS;

        if($get_flags & \Memcached::GET_EXTENDED)
            return ['value' => $value, 'cas' => $cas];
        else
            return $value;
    }

    public function set($key, $value, $expiration = null)
    {
        if($expiration === 0) {
            $expiration = null;
        }

        if($expiration !== null && $expiration < 60*60*24*30){
            $expiration = time() + $expiration;
        }

        $this->storage[$key] = [$value, $expiration, $this->cas++];
        $this->resultCode = \Memcached::RES_SUCCESS;

        return true;
    }

    public function delete($key, $time = 0)
    {
        if(isset($this->storage[$key])) {
            unset($this->storage[$key]);
            $this->resultCode = \Memcached::RES_SUCCESS;
            return true;
        }

        $this->resultCode = \Memcached::RES_NOTFOUND;
        return false;
    }

    /**
     * @return array
     */
    public function getStorage()
    {
        return $this->storage;
    }

    public function getResultCode() {
        return $this->resultCode;
    }

    public function getResultMessage() {
        if($this->resultCode !== \Memcached::RES_SUCCESS)
            return "Error " . $this->resultCode;
        else
            return "Success";
    }

    public function getByKey($server_key, $key, $cache_cb = NULL, $get_flags = NULL) { $this->methodUnsupported(); }

    public function getMulti($keys, $get_flags = NULL)
    {
        $results = [];
        foreach ($keys as $key){
            $value = $this->get($key, null, $get_flags);
            if($this->resultCode === \Memcached::RES_SUCCESS)
                $results[$key] = $value;
        }

        return $results;
    }

    public function getMultiByKey($server_key, $keys, $get_flags = NULL) { $this->methodUnsupported(); }
    public function getDelayed($keys, $with_cas = NULL, $value_cb = NULL) { $this->methodUnsupported(); }
    public function getDelayedByKey($server_key, $keys, $with_cas = NULL, $value_cb = NULL) { $this->methodUnsupported(); }
    public function fetch() { $this->methodUnsupported(); }
    public function fetchAll() { $this->methodUnsupported(); }
    public function setByKey($server_key, $key, $value, $expiration = null) { $this->methodUnsupported(); }
    public function touch($key, $expiration = NULL) { $this->methodUnsupported(); }
    public function touchByKey($server_key, $key, $expiration = NULL) { $this->methodUnsupported(); }
    public function setMulti($items, $expiration = null) { $this->methodUnsupported(); }
    public function setMultiByKey($server_key, $items, $expiration = null) { $this->methodUnsupported(); }

    public function cas($cas_token, $key, $value, $expiration = null) {
        $existing = $this->get($key, null, \Memcached::GET_EXTENDED);
        if($this->resultCode === \Memcached::RES_SUCCESS){
            if($existing['cas'] == $cas_token){
                $this->set($key, $value, $expiration);
                return true;
            }
            else {
                $this->resultCode = \Memcached::RES_DATA_EXISTS;
                return false;
            }
        }
        return false;
    }

    public function casByKey($cas_token, $server_key, $key, $value, $expiration = null) { $this->methodUnsupported(); }

    public function add($key, $value, $expiration = null) {
        $existing = $this->get($key);
        if($existing === false && $this->resultCode === \Memcached::RES_NOTFOUND) {
            $this->set($key, $value, $expiration);
            return true;
        }

        $this->resultCode = \Memcached::RES_NOTSTORED;
        return false;
    }

    public function addByKey($server_key, $key, $value, $expiration = null) { $this->methodUnsupported(); }
    public function append($key, $value, $expiration = NULL) { $this->methodUnsupported(); }
    public function appendByKey($server_key, $key, $value, $expiration = NULL) { $this->methodUnsupported(); }
    public function prepend($key, $value, $expiration = NULL) { $this->methodUnsupported(); }
    public function prependByKey($server_key, $key, $value, $expiration = NULL) { $this->methodUnsupported(); }
    public function replace($key, $value, $expiration = null) { $this->methodUnsupported(); }
    public function replaceByKey($server_key, $key, $value, $expiration = null) { $this->methodUnsupported(); }
    public function deleteMulti($keys, $time = NULL) { $this->methodUnsupported(); }
    public function deleteByKey($server_key, $key, $time = 0) { $this->methodUnsupported(); }
    public function deleteMultiByKey($server_key, $keys, $time = NULL) { $this->methodUnsupported(); }

    public function increment($key, $offset = 1, $initial_value = 0, $expiry = 0)
    {
        $value = $this->get($key);
        if($value === false && $this->resultCode == \Memcached::RES_NOTFOUND) {
            $value = $initial_value;
        }
        else{
            if (!is_int($value)){
                $this->resultCode = \Memcached::RES_NOTFOUND;
                return false;
            }
            $value += $offset;
            if($expiry === 0)
                $expiry = $this->storage[$key][1];
        }

        $this->set($key, $value, $expiry);
        return $this->storage[$key][0];
    }

    public function decrement($key, $offset = 1, $initial_value = 0, $expiry = 0)
    {
        $value = $this->get($key);
        if($value === false && $this->resultCode == \Memcached::RES_NOTFOUND) {
            $value = $initial_value;
        }
        else{
            if (!is_int($value)){
                $this->resultCode = \Memcached::RES_NOTFOUND;
                return false;
            }
            $value -= $offset;
            if($expiry === 0)
                $expiry = $this->storage[$key][1];
        }

        $this->set($key, $value, $expiry);
        return $this->storage[$key][0];
    }

    public function incrementByKey($server_key, $key, $offset = 1, $initial_value = 0, $expiry = 0) { $this->methodUnsupported(); }
    public function decrementByKey($server_key, $key, $offset = 1, $initial_value = 0, $expiry = 0) { $this->methodUnsupported(); }
    public function addServer($host, $port, $weight = 0) { $this->methodUnsupported(); }
    public function addServers($servers) { $this->methodUnsupported(); }
    public function getServerList() { $this->methodUnsupported(); }
    public function getServerByKey($server_key) { $this->methodUnsupported(); }
    public function resetServerList() { $this->methodUnsupported(); }
    public function quit() { $this->methodUnsupported(); }
    public function getStats($args = null) { $this->methodUnsupported(); }
    public function getVersion() { $this->methodUnsupported(); }
    public function getAllKeys() { $this->methodUnsupported(); }
    public function flush($delay = 0) { $this->methodUnsupported(); }
    public function getOption($option) { $this->methodUnsupported(); }
    public function setOption($option, $value) { $this->methodUnsupported(); }
    public function setOptions($options) { $this->methodUnsupported(); }
    public function isPersistent() { $this->methodUnsupported(); }
    public function isPristine() { $this->methodUnsupported(); }

    private function methodUnsupported()
    {
        throw new \RuntimeException(sprintf('Memcached mock does not implement "%s" method. Please provide implementation.', debug_backtrace()[1]['function']));
    }
}
