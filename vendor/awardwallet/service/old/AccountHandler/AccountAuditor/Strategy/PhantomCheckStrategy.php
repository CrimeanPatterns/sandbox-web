<?php

require_once __DIR__."/AccountAuditorCheckStrategyInterface.php";
require_once __DIR__."/../AccountCheckReport.php";

class PhantomCheckStrategy implements AccountAuditorCheckStrategyInterface {
	
	public $connection;
	
	public function __construct ($connection = null) {
		global $Connection;
		if (isset($connection))
			$this->connection = $connection;
		else
			$this->connection = $Connection;
	}
	
	public function check(Account $account, AuditorOptions $options) {
		$this->preventHighCPU($account->getAccountId(), true);
		$info = $account->getAccountInfo();
		$groupDir = TAccountChecker::$logDir."/".sprintf("%03d", floor($info['AccountID'] / 1000));
		$logDir = $groupDir."/account-{$info['AccountID']}-".date("Y-m-d_H.i.s")."-".rand();
		// split to 2 mkdir calls to prevent race conditions, when recursive mkdir fails completely when first level dir exists
		if(!file_exists($groupDir))
			@mkdir($groupDir, 0777, true);
		if(!file_exists($logDir))
			mkdir($logDir, 0777, true);
		file_put_contents($logDir.'/log.html', "<pre>\n");
		$input = array(
			'providerCode' => $info['ProviderCode'],
			'login' => $info['Login'],
			'login2' => $info['Login2'],
			'login3' => $info['Login3'],
			'password' => $info['Pass'],
		);
		$input['answers'] = SQLToArray('select Question, Answer from Answer where AccountID = '.$account->getAccountId().' and Valid = 1', "Question", "Answer");
		if($info['BrowserState'] != ''){
			$state = @unserialize($info['BrowserState']);
			if(is_array($state) && isset($state['Version']) && $state['Version'] == 1){
				if(isset($state['cookies'])){
					file_put_contents($logDir.'/log.html', "cookies loaded: {$state['cookies']}\n", FILE_APPEND);
					file_put_contents($logDir.'/cookies.txt', $state['cookies']);
				}
				if(isset($state['state'])){
					file_put_contents($logDir.'/log.html', "state loaded: ".var_export($state['state'], true)."\n", FILE_APPEND);
					$input['state'] = $state['state'];
				}
			}
		}
		file_put_contents($logDir."/input.js", 'var input = '.json_encode($input));
		exec('timelimit -t 120 -T 10 nice -n 20 phantomjs'
		//.' --disk-cache=yes --max-disk-cache-size=10000'
		.' --load-images=no'
		.' --cookies-file='.$logDir.'/cookies.txt'
		.' --ssl-protocol=any'
		//.' --proxy=192.168.0.2:8888'
		.' --ignore-ssl-errors=yes '.__DIR__.'/../../../phantomCheck.js '.$logDir." 2>&1 >>$logDir/log.html", $output, $exitCode);
		file_put_contents($logDir.'/log.html', "\n</pre>\nexitCode: $exitCode<br/>\n", FILE_APPEND);
		$report = new AccountCheckReport();
		if(in_array($exitCode, array(0, 139))){
			if(file_exists($logDir.'/output.js')){
				$output = json_decode(file_get_contents($logDir.'/output.js'), true);
				file_put_contents($logDir.'/log.html', "Output:\n<pre>\n".var_export($output, true)."\n</pre>\n", FILE_APPEND);
				if(isset($output['errorCode']))
					$report->errorCode = $output['errorCode'];
				if(isset($output['errorMessage']))
					$report->errorMessage = $output['errorMessage'];
				if(isset($output['question']))
					$report->question = $output['question'];
				if(isset($output['balance']))
					$report->balance = $output['balance'];
				if(isset($output['properties']))
					$report->properties = $output['properties'];
				if(isset($output['keepState']) && $output['keepState']){
					$state = array(
						"Version" => 1,
					);
					if(file_exists($logDir.'/cookies.txt'))
						$state["cookies"] = file_get_contents($logDir.'/cookies.txt');
					if(isset($output['state']))
						$state['state'] = $output['state'];
					$report->browserState = serialize($state);
				}
			}
			else
				file_put_contents($logDir.'/log.html', "missing output.js<br/>\n", FILE_APPEND);
		}
		else
			file_put_contents($logDir.'/log.html', "phantomjs unexpected exit: {$exitCode}<br/>\n", FILE_APPEND);
		$report->logPath = $logDir;
		$report->warnings = true;
		$this->preventHighCPU($account->getAccountId(), false);
		return $report;
	}

	protected function preventHighCPU($accountId, $start){
		if(ConfigValue(CONFIG_TRAVEL_PLANS))
			return;
		$maxInstances = apc_fetch('max_phantom_instances'); // this variable will be set in TServiceForkPool, depends on MaxProcesses
		if($maxInstances > 0){
			$throttler = new ProcessThrottler(ApcCache::getInstance(), 'phantom_instances', $maxInstances);
			if($start){
				$throttler->getLock();
				$instances = $throttler->getProcessCount();
				echo "[".getmypid()."] phantom instances: $instances/$maxInstances\n";
				if($instances >= $maxInstances){
					$throttler->removeLock();
					throw new InstanceThrottledException('too many phantom instances: '.$instances);
				}
				$throttler->addProcess($accountId, 300);
				$throttler->removeLock();
			}
			else
				$throttler->removeProcess($accountId);
		}
	}

}

?>