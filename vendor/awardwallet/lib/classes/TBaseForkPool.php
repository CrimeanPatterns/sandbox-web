<?php

define('MSGTYPE_CHILD_WORK', 1);
define('MSGTYPE_CHILD_EXIT', 2);
define('MSGTYPE_DAEMON_CONTROL', 4);
define('MSGTYPE_DAEMON_RESPONSE', 5);

class TForkProcess{
	public $StartTime;
	public $UpdateTime;
	public $Working;

	function __construct(){
		$this->StartTime = time();
		$this->UpdateTime = time();
		$this->Working = false;
	}
}

class TBaseForkPool{

	const CHILD_TIMEOUT_PREFIX = "wsdl_child_timeout_";

	public $MaxProcesses;
	protected $Processes = array();
	protected $Stats = array();
	protected $StatStartDate;
	protected $DeadProcesses = array();
	public $StatsPeriod;
	protected $UseDatabase;
	protected $ProcessTimeout = 180;
	protected $ProcessLifetime = 300;
	protected $ProcessIdletime = 30;
	protected $IPCQueue;
	protected $IPCID;
	protected $LogPrefix = "master";
	protected $Terminating;
	protected $WantRecycle = false;
	protected $ControllerIPC;
	// maximum number of seconds within one loop in continuous mode
	public $MaxLoopDuration;
	public $LogLevel = 3;
	protected $LastWarningDate;
	protected $ChildStartTime;
	protected $MasterPID;
	protected $MaxLoadAverage;
    private $statusSecond;
    protected $maxRows;
   	protected $processedRows = 0;

	function __construct(){
		$this->MaxProcesses = 1;
		$this->StatsPeriod = SECONDS_PER_DAY / 4;
		$this->UseDatabase = false;
		$this->addLog("constructing fork pool, parent pid: ".getmypid());
		$this->resetStats();
		$this->IPCID = 11000 + rand(1, 999);
		$this->LastWarningDate = time() - SECONDS_PER_DAY;
		$this->MasterPID = getmypid();
	}

	function processRow($fields){
		if((time() - $this->StatStartDate) >= $this->StatsPeriod){
			$this->showStats();
			$this->resetStats();
		}
		$this->getFreeProcess($fields);
	}

	function rowName($fields){
		DieTrace("override this function");
	}

	function listenProcesses(){
		if(msg_receive($this->IPCQueue, MSGTYPE_DAEMON_CONTROL, $msgType, 64000 , $senderIpc, true, MSG_IPC_NOWAIT, $errorCode)){
			$this->addLog("received terminate command, from {$senderIpc}");
			$this->Terminating = true;
			$this->ControllerIPC = $senderIpc;
		}
		if(($errorCode != 0) && ($errorCode != MSG_ENOMSG) && ($errorCode != 22)){
			$error = "msg_receive control failed: ".$errorCode;
			$this->addLog($error);
			DieTrace($error, false);
		}
		$deadProcesses = array();
		foreach($this->Processes as $pid => $process){
			$info = apc_fetch("fp_cs_".gethostname()."_".$this->IPCID."_".$pid);
			if(!empty($info)){
				$process->UpdateTime = $info["UpdateTime"];
				$process->Working = $info["Working"];
			}
			$waitpid = pcntl_waitpid($pid, $status, WNOHANG);
			if($waitpid == -1)
				DieTrace("failed to get child state");
			if($waitpid == $pid){
				$exitCode = pcntl_wexitstatus($status);
				if($exitCode != 0){
					$this->addLog("child $pid exitCode: $exitCode");
					if($process->Working && time() > ($this->LastWarningDate + 300)){
						$rowName = $this->rowName(intval(apc_fetch("forkpool_last_account_".$pid)));
						DieTrace(
							"wsdl worker error",
							false,
							0,
							[
								"pid" => $pid,
								"exit code" => $exitCode,
								"last account" => $rowName,
								"possible error" => $this->getLastError($rowName, $pid)
							]
						);
						$this->LastWarningDate = time();
					}
				}
				$deadProcesses[] = $pid;
			}
			else {
				$timeout = $this->getChildTimeout($pid);
				if ((time() - $process->UpdateTime) > $timeout) {
					$this->addLog("child pid $pid timed out (timeout: $timeout), and will be killed");
					$this->addStat("child killed", 1);
					posix_kill($pid, 9);
					pcntl_waitpid($pid, $status);
					$this->addLog("killed");
					$deadProcesses[] = $pid;
				}
			}
		}
		if(count($deadProcesses) > 0){
			foreach($deadProcesses as $pid)
				$this->deleteProcess($pid);
			$this->checkKilled();
			$this->createProcesses();
		}
	}

	private function getChildTimeout($pid)
	{
		$timeout = apc_fetch(self::CHILD_TIMEOUT_PREFIX . $pid);
		if(!empty($timeout))
			return $timeout;
		else
			return $this->ProcessTimeout;
	}

	public static function setChildTimeout($seconds)
	{
		if(!empty($seconds))
			apcu_store(self::CHILD_TIMEOUT_PREFIX . getmypid(), $seconds, $seconds * 2);
		else
			apcu_delete(self::CHILD_TIMEOUT_PREFIX . getmypid());
	}

	protected function getLastError($rowName, $pid){
		$command = "grep -P -i 'Error|fatal|notice|exception|".preg_quote($rowName)."|".preg_quote($pid)."' /var/log/www/wsdlawardwallet/checkAll.log | tail -n 50";
		//$this->addLog($command);
		exec($command, $lines, $exitCode);
		if($exitCode != 0)
			$this->addLog("failed to get logs, code: $exitCode");
		return $lines;
	}

	function deleteProcess($pid){
		unset($this->Processes[$pid]);
		if(!in_array($pid, $this->DeadProcesses))
			$this->DeadProcesses[] = $pid;
		while(count($this->DeadProcesses) > ($this->MaxProcesses * 2))
			array_shift($this->DeadProcesses);
	}

	function checkKilled(){

	}

	function getFreeProcess($fields){
	    $startTime = time();
		while(true){
			$this->listenProcesses();
			if((time() - $startTime) > 300){
			    $this->addLog("can'get free process within 5 minutes, terminating");
			    $this->Terminating = true;
            }
			if($this->Terminating)
				break;
			$queueStatus = msg_stat_queue($this->IPCQueue);
			$this->createProcesses();
			if($queueStatus['msg_qnum'] < $this->MaxProcesses * 2){
				if(@msg_send($this->IPCQueue, MSGTYPE_CHILD_WORK, $fields, true, false)) {
					apc_delete('wsdl_ipc_empty');
					break;
				}
				else {
					usleep(100000);
			}
			if($this->MaxProcesses > 0)
				usleep(1000000 / ($this->MaxProcesses * 4));
			}
		}
	}

	function waitEmptyQueue($desiredQueue = 0){
		$logged = false;
		$loggedQueue = -1;
//		$loggedProcesses = -1;
		while(true){
			$this->listenProcesses();
			$queueStatus = msg_stat_queue($this->IPCQueue);
			if($queueStatus['msg_qnum'] <= $desiredQueue){
				$this->addLog("empty queue: ".$queueStatus['msg_qnum'], 3);
				break;
//				$freeProcesses = 0;
//				foreach($this->Processes as $process)
//					if(!$process->Working)
//						$freeProcesses++;
//				if($freeProcesses == count($this->Processes))
//					break;
//				else{
//					if($loggedProcesses != $freeProcesses){
//						$this->addLog("waiting processes to finish: ".$freeProcesses." of ".count($this->Processes));
//						$loggedProcesses = $freeProcesses;
//					}
//					$logged = true;
//					usleep(100000);
//				}
			}
			else{
				if($loggedQueue != $queueStatus['msg_qnum']){
					$this->addLog("waiting empty queue, current queue: ".$queueStatus['msg_qnum'], 3);
					$logged = true;
					$loggedQueue = $queueStatus['msg_qnum'];
				}
				usleep(100000);
			}
		}
		if($logged)
			$this->addLog("queue is empty", 3);
	 }

	function createChild(){
		global $Connection;
		if($this->UseDatabase)
			$Connection->Close();
		$pid = pcntl_fork();
		if($pid == -1)
			DieTrace("failed to fork");
		if($pid == 0){
			$this->LogPrefix = getmypid();
			$this->openIPC();
			srand($this->LogPrefix);
			$this->childWorkCycle();
			$this->addLog("exit", 5);
			exit();
		}
		else{
			if($this->UseDatabase)
				$Connection->Open(null, true);
		}
		$this->Processes[$pid] = new TForkProcess();
		return $pid;
	}

	function childWork($fields){
		DieTrace("override this method");
	}

	function sendChildStatus($pid, $working){
	    $second = date("s");
	    if($this->statusSecond != $second) {
            $this->addLog("sending status", 7);
            $key = "fp_cs_" . gethostname() . '_' . $this->IPCID . "_" . $pid;
            apcu_store($key, array("UpdateTime" => time(), "Working" => $working), 0);
            $this->statusSecond = $second;
        }
		return true;
	}

	function childWorkCycle(){
		global $Connection;
		$this->ChildStartTime = time();
		$idleStart = time();
		$pid = getmypid();
		$this->sendChildStatus($pid, false);
		while(true){
			if((time() - $this->ChildStartTime) > $this->ProcessLifetime){
				$this->addLog("child $pid exiting, because process lifetime exceeded", 4);
				break;
			}
			if((time() - $idleStart) > $this->ProcessIdletime){
				$this->addLog("child $pid exiting, because idle", 5);
				break;
			}
			if(msg_receive($this->IPCQueue, MSGTYPE_CHILD_WORK, $msgType, 64000 , $fields, true, MSG_IPC_NOWAIT, $errorCode)){
				$this->sendChildStatus($pid, true);
				if($this->UseDatabase && !$Connection->Active){
					$this->addLog("connecting to db", 7);
					$Connection->Open(null, true);
					$this->addLog("connected", 7);
				}
				$this->childWork($fields);
				if($this->WantRecycle)
					break;
				$this->addLog("childWork done", 7);
				$idleStart = time();
			}
			else{
				$this->sendChildStatus($pid, false);
				if($errorCode != MSG_ENOMSG){
					$this->addLog("msg_receive child_work failed");
					break;
				}
				if(msg_receive($this->IPCQueue, MSGTYPE_CHILD_EXIT, $msgType, 64000 , $fields, true, MSG_IPC_NOWAIT, $errorCode))
					break;
				else{
					if($errorCode != MSG_ENOMSG){
						$this->addLog("msg_receive child_exit failed");
						break;
					}
					else {
						usleep(100000);
					}
				}
			}
		}
		$this->addLog("exiting", 5);
		if($this->UseDatabase && $Connection->Active){
			$this->addLog("closing database", 5);
			$Connection->Close();
		}
		$this->addLog("sending status", 5);
		$this->sendChildStatus($pid, false);
	}

	function childTimeLeft(){
		return $this->ProcessLifetime - (time() - $this->ChildStartTime);
	}

	function waitAllFree(){
		$this->addLog("waiting for threads to finish");
		for($n = 0; $n < $this->MaxProcesses; $n++)
			@msg_send($this->IPCQueue, MSGTYPE_CHILD_EXIT, true, true, false);
		while(count($this->Processes) > 0){
			$this->listenProcesses();
			usleep(1000000 / $this->MaxProcesses);
		}
	}

	function addLog($s, $level = 0){
		if($level <= $this->LogLevel)
			echo date('Y-m-d H:i:s')." [".$this->LogPrefix.'] '.$s."\n";
	}

	function addStat($s, $n){
		if(!isset($this->Stats[$s]))
			$this->Stats[$s] = 0;
		$this->Stats[$s] += $n;
	}

	function resetStats(){
		$this->addLog("resetting stats");
		$this->StatStartDate = time();
		$this->Stats = array();
	}

	function showStats(){
		ksort($this->Stats);
		$sText = "Interval start date: ".date(DATE_TIME_FORMAT, $this->StatStartDate)."
Interval end date: ".date(DATE_TIME_FORMAT)."
Duration: ".(time() - $this->StatStartDate)." seconds\n";
		foreach($this->Stats as $s => $n)
			$sText .= "$s: $n\n";
		if(isset($this->Stats["Rows"])){
			$sText .= "average speed: ".round((time() - $this->StatStartDate) / $this->Stats["Rows"], 3)." seconds on row\n";
		}
		else
			$sText .= "no rows checked\n";
		echo $sText;
	}

	function createProcesses(){
		if(!$this->Terminating && (count($this->Processes) < $this->MaxProcesses)){
			while(count($this->Processes) < $this->MaxProcesses)
				$this->createChild();
			$this->addLog("processes created", 5);
		}
	}

	function openIPC(){
		$this->IPCQueue = msg_get_queue($this->IPCID);
	}

	function init(){
		global $Connection;
		$this->addLog("initializing fork pool");
		$this->Terminating = false;
		if(!$this->UseDatabase && isset($Connection))
			$Connection->Close();
		$this->openIPC();
		$removed = 0;
		while(msg_receive($this->IPCQueue, 0, $msgType, 64000 , $message, true, MSG_IPC_NOWAIT, $errorCode))
			$removed++;
		if($removed > 0)
			$this->addLog("removed $removed messages from queue");
		$this->createProcesses();
	}

	function done(){
		$this->Terminating = true;
		$this->waitAllFree();
		msg_remove_queue($this->IPCQueue);
		$this->showStats();
		$this->resetStats();
		$this->addLog("fork pool finished");
		if(isset($this->ControllerIPC)){
			$this->addLog("notifying controller");
			$ipcHandle = msg_get_queue($this->ControllerIPC);
			if(!msg_send($ipcHandle, MSGTYPE_DAEMON_RESPONSE, true, true, true))
				$this->addLog("message failed");
			$this->addLog("notified");
		}
	}

	function processRows($rows){
		$rowCount = count($rows);
		$this->addLog('processing '.$rowCount." rows in {$this->MaxProcesses} threads", 4);
		$n = 1;
		$packetStarted = time();
		foreach($rows as $fields){
			$duration = time() - $packetStarted;
			if(isset($this->MaxLoopDuration) && ($duration > $this->MaxLoopDuration)){
				$this->addLog("loop duration limit hit: $duration secs of {$this->MaxLoopDuration}", 4);
				break;
			}
			$this->addLog("row {$n} of {$rowCount}: ".$this->rowName($fields), 3);
			$this->addStat("Rows", 1);
			set_time_limit(360);
			$this->processRow($fields);
			if($this->Terminating)
				break;
			$n++;
		}
		$this->addLog('finished processing '.($n-1).' of '.$rowCount." rows", 3);
	}

	function processLoop(){
		$checkTime = time();
		while(!$this->Terminating){
			$rows = $this->getRows();
			if(count($rows) > 0)
				$this->processRows($rows);
			else{
			    if(in_array(parse_url(DEBUG_SERVICE_LOCATION, PHP_URL_HOST), ['awardwallet.dev', 'awardwallet.local'])) {
                    Cache::getInstance()->waitForKey('wsdl_request_received_' . gethostname(), 1);
                    Cache::getInstance()->delete('wsdl_request_received_' . gethostname());
                }
                else {
                    sleep(rand(1, 3));
                }
				if((time() - $checkTime) > 30){
					$this->checkKilled();
					$checkTime = time();
				}
			}
		}
	}

	protected function sleepApc($seconds, $cacheKey){
		$endTime = microtime(true) + $seconds;
		apc_add($cacheKey, true, 60);
		$total = 0;
		while(!empty(apc_fetch($cacheKey)) && microtime(true) < $endTime) {
			usleep(100000);
			$total += 0.1;
		}
	}

	public static function callForkedProcess(Callable $function){
		global $Connection;
		if(isset($Connection))
			$Connection->Close();
		$pid = pcntl_fork();
		if($pid == -1)
			throw new \Exception("failed to fork");
		if(isset($Connection))
			$Connection->Open(null, true);
		apc_delete('callForkedProcess');
		if($pid == 0){
			// child
			$result = $function();
			apcu_store('callForkedProcess', array('result' => $result), 60);
			exit();
		}
		else{
			// main
			$waitpid = pcntl_waitpid($pid, $status);
			if($waitpid == -1)
				throw new \Exception("failed to get child state");
			if($waitpid == $pid){
				$exitCode = pcntl_wexitstatus($status);
				if($exitCode != 0){
					throw new \Exception("child $pid exitCode: $exitCode");
				}
				$result = apc_fetch('callForkedProcess');
				if($result === false)
					throw new \Exception("child did not return result");
				return $result['result'];
			}
			else
				throw new \Exception("unknown pid returned: $waitpid, expected: $pid");
		}
	}

	protected function loadAverageTooHigh(){
		if(!isset($this->MaxLoadAverage))
			return false;
		$load = sys_getloadavg();
		return ($load[0] > $this->MaxLoadAverage);
	}

}

?>