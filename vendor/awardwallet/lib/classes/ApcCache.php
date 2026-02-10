<?
/**
 * apc wrapper for compatibility with Cache class
 *
 * do not store false or null values
 */
class ApcCache{

	/**
	 * @var Cache
	 */
	protected static $instance;

	/**
	 * @return ApcCache
	 */
	public static function getInstance($newInstance = false){
		if(!isset(self::$instance) || $newInstance)
			self::$instance = new ApcCache();
		return self::$instance;
	}

	public function get($key){
		$result = apcu_fetch($key);
		if(is_array($result))
			// apc may return expired items, we should discard it
			if($result['expire'] == 0 || ((time() - $result['time']) < $result['expire']))
				$result = $result['data'];
			else
				$result = false;
		return $result;
	}

	public function set($key, $var, $expire = 0){
		if($var === false || $var === null)
			throw new \Exception("invalid value");
		apcu_store($key, array("data" => $var, "time" => time(), 'expire' => $expire), $expire);
	}

	public function delete($key){
		apcu_delete($key);
	}

	public function add($key, $var, $expire = 0){
		if($var === false || $var === null)
			throw new \Exception("invalid value");
		$result = apcu_add($key, array("data" => $var, "time" => time(), 'expire' => $expire), $expire);
		return $result;
	}

}