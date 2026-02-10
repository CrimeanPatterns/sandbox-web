<?
/**
 * wrapper around memcached
 * will fallback to apc in case of memcached timeout
 * adds strong checking of expiration time to apc
 *
 * do not store false or null values
 */
class MemApcCache{

	/**
	 * @var Memcached
	 */
	public $memcached;

	private $apcExists;

	public function __construct($host = null, $persistentId = null){
		if(!isset($host))
      		$host = MEMCACHED_HOST;
		if(empty($persistentId))
			$persistentId = 'appCacheB_' . getmypid();
		$this->memcached = new Memcached($persistentId);
		if(count($this->memcached->getServerList()) == 0){
			$this->memcached->addServer($host, 11211);
			$this->memcached->setOption(Memcached::OPT_RECV_TIMEOUT, 1000);
			$this->memcached->setOption(Memcached::OPT_SEND_TIMEOUT, 1000);
			$this->memcached->setOption(Memcached::OPT_CONNECT_TIMEOUT, 1000);
            $this->memcached->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
            // this option affects performance, login speed, required for AntiBruteforceLocker
            $this->memcached->setOption(\Memcached::OPT_TCP_NODELAY, true);
		}
		$this->apcExists = function_exists('apcu_store');
	}

	public function get($key){
		$result = @$this->memcached->get($key);
		if($result === false && $this->memcached->getResultCode() != Memcached::RES_NOTFOUND && $this->apcExists){
			$result = apcu_fetch($key, $success);
            if(!$success)
                return false;
			if(is_array($result) && ($result['expire'] == 0 || ((time() - $result['time']) < $result['expire'])))
				$result = $result['data'];
			else
				$result = false;
		}
		return $result;
	}

	public function set($key, $var, $expire = 0){
		if($var === false)
			throw new \Exception("invalid value");
		$result = $this->memcached->set($key, $var, $expire);
		//file_put_contents("/var/log/www/awardwallet/progress.log", date("Y-m-d H:i:s ") . " [" . getmypid() . "] memcached set result: " . var_export($result) . ", error code: " . $this->memcached->getResultCode() . ", message: " . $this->memcached->getResultMessage() . "\n", FILE_APPEND);
		if($this->apcExists)
			apcu_store($key, array("data" => $var, "time" => time(), 'expire' => $expire), $expire);
	}

	public function delete($key){
		@$this->memcached->delete($key);
		if(function_exists('apc_fetch'))
			apcu_delete($key);
	}

	public function add($key, $var, $expire = 0){
		if($var === false || $var === null)
			throw new \Exception("invalid value");
		$result = @$this->memcached->add($key, $var, $expire);
		if($result === false && $this->apcExists && $this->memcached->getResultCode() != Memcached::RES_NOTSTORED){
			// possible memcached failure, use apc
			$result = apcu_add($key, array("data" => $var, "time" => time(), 'expire' => $expire), $expire);
		}
		return $result;
	}

}