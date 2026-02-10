<?
/**
 * track simultaneous processes, using memcached for IPC
 */
class ProcessThrottler{

	/**
	 * @var Memcached memcached instance, required for throttling
	 */
	protected $Cache;
	protected $Prefix;
	protected $MaxProcesses;

	public function __construct($cache, $prefix, $maxProcesses){
		$this->Cache = $cache;
		$this->Prefix = $prefix;
		$this->MaxProcesses = $maxProcesses;
	}

	/**
	 * add or replace process
	 * after $timeout seconds process will be removed
	 * @param $pid string
	 * @param $timeout int
	 */
	public function addProcess($pid, $timeout){
		$this->removeProcess($pid);
		for($n = 0; $n < $this->MaxProcesses; $n++)
			if($this->Cache->add("pt_{$this->Prefix}_{$n}", strval($pid), $timeout))
				break;
	}

	/**
	 * remove process
	 * @param $pid string
	 */
	public function removeProcess($pid){
		for($n = 0; $n < $this->MaxProcesses; $n++)
			if($this->Cache->get("pt_{$this->Prefix}_{$n}") === strval($pid))
				$this->Cache->delete("pt_{$this->Prefix}_{$n}");
	}

	/**
	 * how many processes is currently running
	 */
	public function getProcessCount(){
		$result = 0;
		for($n = 0; $n < $this->MaxProcesses; $n++)
			if($this->Cache->get("pt_{$this->Prefix}_{$n}") !== false)
				$result++;
		return $result;
	}

	/**
	 * remove all running processes
	 */
	public function clear(){
		for($n = 0; $n < $this->MaxProcesses; $n++)
			$this->Cache->delete("pt_{$this->Prefix}_{$n}");
	}

	public function getLock($timeout = 5){
		$startTime = microtime(true);
		do{
			$result = $this->Cache->add("pt_{$this->Prefix}_lock", microtime(true), $timeout);
			if(!$result)
				usleep(rand(1, 1000000));
		}
		while(!$result && (microtime(true) - $startTime) <= $timeout);
		return $result;
	}

	public function removeLock(){
		$this->Cache->delete("pt_{$this->Prefix}_lock");
	}


	/**
	 * checking availability to add new process
	 * @return bool
	 */
	public function canAddProcess(){
		$result = false;
		if($this->getProcessCount() < $this->MaxProcesses)
			$result = true;

		return $result;
	}

}