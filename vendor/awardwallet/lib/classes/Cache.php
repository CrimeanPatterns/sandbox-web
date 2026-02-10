<?
/**
 * wrapper around memcached
 * will fallback to apc in case of memcached timeout
 * adds strong checking of expiration time to apc
 *
 * do not store false or null values
 */
class Cache extends MemApcCache{

    /**
     * @var Cache
     */
    protected static $instance;

    /**
     *
     * @param bool $newInstance create new connection, usually after fork
     * @return Cache
     */
    public static function getInstance($newInstance = false, $host = null){
        if(!isset(self::$instance) || $newInstance)
            self::$instance = new Cache($host);
        return self::$instance;
    }

	public function waitForKey($cacheKey, $seconds){
		$endTime = microtime(true) + $seconds;
		$total = 0;
		while(empty($this->get($cacheKey)) && microtime(true) < $endTime) {
			usleep(100000);
			$total += 0.1;
		}
//		codecept_debug(date("Y-m-d H:i:s ") . "slept: " . round($total, 1) . " / "  .round($seconds, 1). ", with $cacheKey");
	}

}