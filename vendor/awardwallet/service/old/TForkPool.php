<?php

require_once __DIR__ . '/functions.php';

define('STATS_PERIOD', SECONDS_PER_DAY / 4);
define('S_ACCOUNTS', '1. Accounts checked');
define('S_ACCOUNTS_SUCCESS', '2. Accounts succesfully checked');
define('S_ACCOUNTS_ERRORS', '3. Accounts had errors');

class TForkPool extends TBaseForkPool{

	private $HaveUsers;
	private $LastDate;
	protected $SQL;
	protected $Options;
	protected $AccountFields;
	private $EngineTimes = array();
	private $Spread;

	protected $Where;
	protected $DateFilter;
	protected $Statuses;

	protected $maxFoxes;
	protected $unlimited = false;

	function __construct($haveUsers){
		parent::__construct();
		$this->HaveUsers = $haveUsers;
		$this->ProcessIdletime = 290;
		$this->UseDatabase = true;
		$this->IPCID = 12000 + rand(1, 999);
	}

	function handleCheckException($accountId, Exception $e, $fields){
		if (strpos($fields['ProviderCode'], 'testprovider') === 0) {
			return false;
		}

		DieTrace($e->getMessage(), false, 0, $e->getTrace());
	}

	function childWork($fields){
		/** @var TMySQLConnection $Connection */
		global $sPath, $arAccountErrorCode, $Connection;
		$accountId = $fields['AccountID'];
		if($this->HaveUsers){
			$checkIts = null;
			$preventLockouts = true;
		}
		else{
			$preventLockouts = false;
		}
		$rowName = $this->rowName($accountId);
		$this->addLog("checking {$rowName}");

		list($lap, $providers, $originalFields) = $this->getProviderGroupState($fields);
		$providersCount = count($providers);
		if (($providersCount == 0) || ($lap >= $providersCount)) {
			$this->addLog("{$rowName}: error, invalid provider queue state, %s", serialize([$lap, $providers]));

			return false;
		}

		if ($providersCount > 1) {
			$this->addLog(sprintf('%s: provider group check, iterating over (%s), starting from lap %d', $rowName, implode(',', $providers), $lap), 4);
		}

		$providerGroupFailed = true;
		$result = false;
		// check loop
		foreach (array_slice($providers, $lap) as $providerCode) {
			$this->switchProvider($accountId, $providerCode);

			if ($providersCount > 1) {
				$this->addLog(sprintf('%s: provider group check, lap %s', $rowName, $providerCode));
			}

			if (!$this->isCheckerUpdated($accountId, $providerCode)) {
				return false;
			}
			$this->addLog("plugin loaded for {$rowName}", 4);

			try {
				$this->doCheck($accountId, $fields, $preventLockouts);
				$result = true;
			} catch (Exception $e) {
				$result = $this->handleCheckException($accountId, $e, $fields);
			}

			if ($providersCount > 1 && $this->isGroupCheckEnded($accountId)) {
				$this->addLog("{$rowName}: provider group check finished after {$lap} lap(s)", 4);
				$providerGroupFailed = false;
				break;
			}

			$originalFields = (0 === $lap) ? $this->loadAccountOriginalFields($accountId) : $originalFields; // store original fields after first check
			$this->saveGroupState($accountId,
				[
					++$lap,
					$providers,
					$originalFields
				]
			);
		}

		$this->clearGroupCheck($accountId);

		if ($providersCount > 1) {
			if ($providerGroupFailed) {
				$this->addLog("{$rowName}: provider group check failed, rolling back", 4);
				$this->rollbackGroupCheck($accountId, $originalFields);
				$result = true;
			} else {
				$this->addLog(sprintf("%s: provider group check succeeded, changing provider from '%s' to '%s'", $rowName, $providers[0], $providers[$lap]), 4);
			}
		}

		$this->addLog("finished checking {$rowName}", 4);
		$rowName = $this->rowName($accountId);

		if($result) {
			$this->addLog("{$rowName}: {$arAccountErrorCode[$this->AccountFields['ErrorCode']]}, balance ".(is_null($this->AccountFields['Balance']) ? 'n/a' : $this->AccountFields['Balance'])."");
			getSymfonyContainer()->get("logger.stat")->info("statistic", ["Partner" => $this->AccountFields["Partner"], "Provider" => $this->AccountFields["Code"], "ErrorCode" => $this->AccountFields["ErrorCode"], "UserID" => Lookup("Account", "AccountID", "UserID", $accountId), "AccountID" => ArrayVal($this->AccountFields, "RequestAccountID")]);
		} else {
			if ($this->AccountFields['ErrorMessage'] != "") {
				$this->addLog("{$rowName}: error, {$this->AccountFields['ErrorMessage']}");
			}
		}

		return $result;
	}

	/**
	 * Check account
	 *
	 * @param int $accountId
	 * @param array $accountFilds
	 * @param true $preventLockouts
	 */
	protected function doCheck($accountId, array $accountFilds, $preventLockouts)
	{
		$options = CommonCheckAccountFactory::getDefaultOptions();
		$options->checkIts = $accountFilds['ParseItineraries'] == '1';
		$options->checkHistory = $accountFilds['ParseHistory'] == '1';
		$options->checkFiles = $accountFilds['ParseFiles'] == '1';
		$options->preventLockouts = $preventLockouts;
		$options->keepLogs = $accountFilds['KeepLogs'] == 1;
		$this->setupCheckOptions($accountId, $options, $accountFilds);
		CommonCheckAccountFactory::checkAndSave($accountId, $options);
	}

	/**
	 * Whether to reload checker from file
	 *
	 * @param int $accountId
	 * @param string $providerCode
	 *
	 * @return bool
	 */
	protected function isCheckerUpdated($accountId, $providerCode)
	{
		/** @var TAbstractConnection $Connection */
		global $Connection, $sPath;

		$file = $sPath . "/engine/" . $providerCode . "/functions.php";
		if (file_exists($file)) {
			$mtime = filemtime($file);
			if (!isset($this->EngineTimes[$providerCode])) {
				$this->EngineTimes[$providerCode] = $mtime;
			} else {
				if ($mtime > $this->EngineTimes[$providerCode]) {
					$this->addLog("provider {$providerCode} was updated. thread will exit.");
					$Connection->Execute("update Account set ScheduledDate = date_add(now(), interval 3 second) where AccountID = {$accountId}");
					$this->WantRecycle = true;
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Clear provider queue state
	 *
	 * @param int $accountId
     */
	protected function clearGroupCheck($accountId)
	{
		/** @var TAbstractConnection $Connection */
		global $Connection;
		$Connection->Execute(sprintf('UPDATE `Account` SET `ProviderGroup` = NULL WHERE `AccountID` = %d', $accountId));
	}

	/**
	 * Switch provider from queue
	 *
	 * @param int $accountId
	 * @param string $providerCode
     */
	protected function switchProvider($accountId, $providerCode)
	{
		/** @var TAbstractConnection $Connection */
		global $Connection;
		$Connection->Execute(sprintf("UPDATE `Account` SET ProviderID = (SELECT ProviderID FROM Provider WHERE Code = '%s') WHERE AccountID = %d", $providerCode, $accountId));
		$this->rowName($accountId); // implicitly load account info
	}

	/**
	 * Rollback account fields in case of failure
	 *
	 * @param int $accountId
	 * @param array $originalFields
     */
	protected function rollbackGroupCheck($accountId, array $originalFields)
	{
		/** @var TAbstractConnection $Connection */
		global $Connection;

		$Connection->Execute(
			sprintf("
				UPDATE `Account`
				SET
					`ProviderID` = %d,
					`ErrorCode` = %d,
					`ErrorMessage` = %s,
					`Question` = %s,
					`BrowserState` = %s
				WHERE `AccountID` = %d",
				$originalFields['ProviderID'],
				null === $originalFields['ErrorCode']    ? 'NULL' : $originalFields['ErrorCode'],
				null === $originalFields['ErrorMessage'] ? 'NULL' : "'" . addslashes($originalFields['ErrorMessage']) . "'",
				null === $originalFields['Question'] ? 'NULL' : "'" . addslashes($originalFields['Question']) . "'",
				null === $originalFields['BrowserState'] ? 'NULL' : "'" . addslashes($originalFields['BrowserState']) . "'",
				$accountId
			)
		);
		$this->rowName($accountId); // implicitly load account info
	}

	/**
	 * Whether to stop iterating over providers
	 *
	 * @param int $accountId
	 *
	 * @return bool
     */
    protected function isGroupCheckEnded($accountId)
	{
		return (bool) count(SQLToSimpleArray(
			sprintf('
				SELECT `AccountID`
				FROM `Account`
				WHERE
					`AccountID` = %d AND
					`ErrorCode` IN (%s)',
				$accountId,
				implode(',', [ACCOUNT_CHECKED, ACCOUNT_WARNING])
			),
			'AccountID'
		));
	}

	/**
	 * Load provider queue
	 *
	 * @param array $accountFields
	 *
	 * @return array tuple (current position, provider queue, original fields)
     */
    protected function getProviderGroupState(array $accountFields)
	{
		$originalFields = $this->loadAccountOriginalFields($accountFields['AccountID']);

		if (!$this->isGroupCheckRequired($accountFields)) {
			$oneProviderGroupState = [0, [$this->AccountFields['Code']], $originalFields];

			return $oneProviderGroupState;
		}

		if (isset($accountFields['ProviderGroup'])) {
			list($lap, $providers, $originalFields) = unserialize($accountFields['ProviderGroup']);
		} else {
			$lap = 0;
			$providers = array_merge([$accountFields['ProviderCode']],
				SQLToSimpleArray(sprintf("
					SELECT DISTINCT pG.Code
					FROM Provider p
					JOIN Provider pG ON pG.ProviderGroup = p.ProviderGroup
					WHERE
						p.Code = '%s'
						AND pG.Code <> 'usbank' /* exclude USBANK because it will ask security questions for any login */
						AND pG.Code <> '%s'
						AND (pG.State >= %d OR pG.State = %d)
					ORDER BY pG.ProviderID",
					$accountFields['ProviderCode'], $accountFields['ProviderCode'], PROVIDER_ENABLED, PROVIDER_TEST)
				, 'Code'));
		}

		return [$lap, $providers, $originalFields];
	}

	/**
	 * Loads data from account
	 *
	 * @param int $accountId
	 *
	 * @return array
     */
    protected function loadAccountOriginalFields($accountId)
	{
		$q = new TQuery(sprintf('
			SELECT a.`ErrorCode`, a.`ErrorMessage`, a.`ProviderID`, p.`Code` as `ProviderCode`, a.Question, a.BrowserState
			FROM `Account` a
			JOIN `Provider` p ON p.`ProviderID` = a.`ProviderID`
			WHERE a.`AccountID` = %d
		', $accountId));

		if (!$q->EOF)
			return $q->Fields;

		return [];
	}

	/**
	 *
	 *
	 * @param int $accountId
	 * @param array $group
     */
	protected function saveGroupState($accountId, array $group)
	{
		/** @var TAbstractConnection $Connection*/
		global $Connection;

		if (!$group) {
			return;
		}

		$Connection->Execute(sprintf("UPDATE `Account` SET `ProviderGroup` = '%s' WHERE `AccountID` = %d",
			addslashes(serialize($group)),
			$accountId
		));
	}

	/**
	 * @param array $fields
	 *
	 * @return bool
	 */
	protected function isGroupCheckRequired(array $fields)
	{
		if ('awardwallet' === $fields['Partner'] && '' !== $fields['Options'] && null !== $fields['Options']) {
			return in_array(ACCOUNT_REQUEST_OPTION_PROVIDER_GROUP_CHECK, explode(',', $fields['Options']), true);
		}

		return false;
	}

	function setupCheckOptions($accountId, AuditorOptions &$options, $fields){

	}

	function buildSQL($sWhere, $sDateFilter, $arStatuses){
		if(in_array(PROVIDER_CHECKING_AWPLUS_ONLY, $arStatuses))
			$awPlusOnlyFilter = " and (p.State <> ".PROVIDER_CHECKING_AWPLUS_ONLY." or u.AccountLevel <> ".ACCOUNT_LEVEL_FREE.")";
		else
			$awPlusOnlyFilter = "";
		return "select
			a.AccountID,
			a.ProviderID,
			5 as Priority,
			a.ErrorCode,
			a.SuccessCheckDate,
			a.PassChangeDate,
			a.UpdateDate,
			a.ModifyDate,
			a.PassChangeDate,
			p.Code as ProviderCode,
			a.SavePassword,
			p.Code,
			a.Login,
   			a.Login2,
   			a.Login3,
   			a.Pass,
   			a.UserID,
   			a.BrowserState,
   			a.HistoryVersion,
   			(u.AutoGatherPlans = 1 AND p.CanCheckItinerary = 1) as AutoGatherPlans
		from
			Account a
			left join Provider p on a.ProviderID = p.ProviderID
			left join Usr u on a.UserID = u.UserID
		where
			a.ErrorCode in( ".implode(", ", $arStatuses)." )
			and (p.State >= ".PROVIDER_ENABLED." or p.State = ".PROVIDER_TEST.")
			and p.State <> ".PROVIDER_CHECKING_EXTENSION_ONLY."
			$awPlusOnlyFilter
			and p.State <> ".PROVIDER_CHECKING_OFF."
			and p.State <> ".PROVIDER_FIXING."
			and p.CanCheck = 1
			and a.BackgroundCheck = 1
			$sDateFilter
			and (
				(a.SavePassword = ".SAVE_PASSWORD_DATABASE." and a.Pass <> '')
				or
				p.State = ".PROVIDER_TEST."
				or
				p.PasswordRequired = 0
				or
				(p.Code = 'aa' and a.ErrorCode in (".ACCOUNT_CHECKED.", ".ACCOUNT_ENGINE_ERROR.") and a.SuccessCheckDate > a.PassChangeDate and a.UpdateDate > '2014-04-16')
			)
			$sWhere
		order by
			u.UpdateDate, u.UserID";
	}

	function rowName($accountId){
		$q = new TQuery("select p.Code, a.Login, a.ErrorMessage, a.Balance, a.ErrorCode from
		Account a
		join Provider p on a.ProviderID = p.ProviderID
		where a.AccountID = $accountId");
		$this->originalAccountFields = $q->Fields;
		$this->AccountFields = $q->Fields;
		return $accountId."-".$q->Fields['Code']."-".$q->Fields['Login'];
	}

	function setOptions($arOptions, &$sWhere, &$sDateFilter, &$arStatuses){
		global $Connection;
		$this->Options = $arOptions;
		$sWhere = "";
		$sDateFilter = "and a.UpdateDate < date_sub(now(), interval 1 day)";
		if(isset($arOptions['v'])){
			$this->LogLevel = intval($arOptions['v']);
			$this->addLog("log level set to: ".$this->LogLevel);
		}
		if(isset($arOptions['i']) || isset($arOptions['l']) || isset($arOptions['e']) || isset($arOptions['q']) || isset($arOptions['r']))
			$arStatuses = array();
		if(isset($arOptions['i']) || isset($arOptions['a']))
			$arStatuses[] = ACCOUNT_INVALID_PASSWORD;
		if(isset($arOptions['l']) || isset($arOptions['a']))
			$arStatuses[] = ACCOUNT_LOCKOUT;
		if(isset($arOptions['a']) || isset($arOptions['q']))
			$arStatuses[] = ACCOUNT_QUESTION;
		if(isset($arOptions['e']))
			$arStatuses[] = ACCOUNT_ENGINE_ERROR;
		if(isset($arOptions['r']))
			$arStatuses[] = ACCOUNT_PROVIDER_ERROR;
		if(isset($arOptions['k'])) {
            $this->maxRows = round(intval($arOptions['k']) * rand(0.7, 1.3));
            $this->addLog("set max rows to {$this->maxRows}");
        }
		if(isset($arOptions['f'])){
			$sWhere .= " and a.UpdateDate > date_sub(now(), interval ".intval($arOptions['f'])." minute)";
			$this->addLog("limited to ".intval($arOptions['f'])." minutes ago, date filter omitted");
			$arOptions['d'] = true;
		}
		if(isset($arOptions['d']))
			$sDateFilter = "";
		if(isset($arOptions['g'])){
			$d = strtotime($arOptions['g']);
			if($d === false)
				die("invalid date filter\n");
			$sDateFilter = "and a.UpdateDate > date_sub(now(), interval 1 day)
			and a.ExpirationDate >= ".$Connection->DateTimeToSQL($d)." and a.ExpirationDate < ".$Connection->DateTimeToSQL($d + SECONDS_PER_DAY);
		}
		if(isset($arOptions['n']))
			$arStatuses = array(ACCOUNT_CHECKED);
		if(isset($arOptions['p'])){
			$sWhere .= " and a.Partner = '" . addslashes($arOptions['p']) . "'";
			$this->addLog("limited to partner {$arOptions['p']}");
		}
		$this->unlimited = isset($arOptions['u']);
		if($this->unlimited)
			$this->addLog("unlimited mode");
		if(isset($arOptions['o'])){
			$sWhere .= " and a.AccountID = ".intval($arOptions['o']);
			$this->addLog("limited to account id = {$arOptions['o']}");
		}
		if(array_key_exists('c', $arOptions)){
			$sWhere .= " and (a.QueueDate > now())";
			$this->addLog("exlcuding accounts queued less than 8 hours ago");
		}
		if(isset($arOptions['n']))
			$sWhere .= " and a.Balance is null";
		if(isset($arOptions['b'])){
			$sWhere .= " and a.TotalBalance >= ".floatval($arOptions['b']);
			$this->addLog("looking for accounts with balance greater or equal: ".floatval($arOptions['b']));
		}
		if(isset($arOptions['t']))
			$nThreads = intval($arOptions['t']);
		else
			$nThreads = 1;
		if(($nThreads < 0) || ($nThreads > 500))
			die("thread count should be from 1 to 500\n");
		if(isset($arOptions['s']))
			$this->Spread = $arOptions['s'];
		$this->MaxProcesses = $nThreads;
		if(isset($arOptions['z'])){
			$this->MaxLoadAverage = floatval($arOptions['z']);
			$this->addLog("max load average set to ".round($this->MaxLoadAverage, 2));
		}
//
//		SeleniumDriver::$onSessionStarted = function($host, $port, $sessionId, $isChrome){
//			apc_store("selenium_pid_" . getmypid(), ["host" => $host, "sessionId" => $sessionId, "isChrome" => $isChrome, 'port' => $port], $this->ProcessTimeout);
//		};
	}

	function init(){
		$this->Statuses = array(ACCOUNT_PREVENT_LOCKOUT, ACCOUNT_CHECKED, ACCOUNT_WARNING, ACCOUNT_PROVIDER_ERROR, ACCOUNT_ENGINE_ERROR, ACCOUNT_PROVIDER_DISABLED, ACCOUNT_UNCHECKED);
		$arOptions = getopt("nqwv:ihxedlacrp:t:m:k:s:o:ub:g:f:yz:");
		if(($arOptions === false) || isset($arOptions['h']))
			die("Usage: checkBalances.php
			-a: check accounts in any state
			-b <min balance>: check accounts with balance greater or equal this value
			-c: continuos check, in loop, for daemon mode
			-d: do not apply date filter, check all
			-e: check only accounts in engine error state
			-f <minutes>: check accounts with update date <minutes> ago
			-g <yyyy-mm-dd>: check only accounts with this expiration date
			-h: this help
			-i: check invalid logons
			-k: exit after this number of rows to recycle resources
			-l: check locked out
			-m <max>: maximum accounts to check in one cycle, default 100
			-n: check accounts with n/a balance
			-o <account id>: check only this account
			-p <partner code>: check only specified partner
			-q: check questions
			-r: check provider error state
			-s <spread>: how many accounts to load from heap in one cycle, default 1000
			-t <threads count>: check N accounts in parallell. default 1.
			-u: unlimited, unthrottled mode. disable throttling. used in pair with -p for points.com
			-v: verbosity, log level, default 3
			-w: write pid of main process to /var/log/www/wsdlawardwallet/checkAll.pid
			-x: terminate daemon
			-y: send only callbacks
			-z <load average>: sleep when system load average greater than this value
");
		if(!isset($arOptions['m']) && isset($arOptions['c']))
			$arOptions['m'] = 100;
		if(!isset($arOptions['s']) && isset($arOptions['c']))
			$arOptions['s'] = 1000;
		$this->setOptions($arOptions, $this->Where, $this->DateFilter, $this->Statuses);
		$this->addLog('checking balances with statuses: '.implode(", ", $this->Statuses)." in {$this->MaxProcesses} threads");
		$this->addLog('date filter: '.$this->DateFilter);

		$this->SQL = $this->buildSQL($this->Where, $this->DateFilter, $this->Statuses);
		$pidFile = "/var/log/www/wsdlawardwallet/checkAll.pid";
		$ipcFile = "/var/log/www/wsdlawardwallet/checkAll.ipc";
		if(isset($this->Options['x'])){
			$this->addLog("terminating daemon");
			if(!file_exists($pidFile)){
				$this->addLog("pid file not found: $pidFile");
				$this->addLog("assuming daemon not running");
			}
			else{
				$terminated = false;
				if(!file_exists($ipcFile))
					$this->addLog("ipc file not found: $ipcFile");
				else{
					$ipc = file_get_contents($ipcFile);
					$this->addLog("daemon ipc: $ipc, sending message to it");
					if(!msg_queue_exists($ipc)){
						$this->addLog("ipc not exists");
					}
					else{
						$this->openIPC();
						//$queueStatus = msg_stat_queue($this->IPCQueue);
						//if($queueStatus['msg_qnum'] == 0){
						$ipcHandle = msg_get_queue($ipc);
						if(msg_send($ipcHandle, MSGTYPE_DAEMON_CONTROL, $this->IPCID, true, true)){
							$maxWait = 30;
							$this->addLog("waiting {$maxWait} seconds, for daemon to respond");
							$startTime = time();
							while((time() - $startTime) < $maxWait){
								if(msg_receive($ipcHandle, MSGTYPE_DAEMON_RESPONSE, $msgType, 64000 , $status, true, MSG_IPC_NOWAIT, $errorCode)){
									$this->addLog("termination confirmed");
									sleep(1);
									$terminated = true;
									break;
								}
								else
									if($errorCode != MSG_ENOMSG){
										if($errorCode != 22)
											$this->addLog("failed to wait response: ".$errorCode);
										else
											$this->addLog("seems like terminated");
										break;
									}
									else
										sleep(1);
							}
						}
						else
							$this->addLog("message send failure");
						msg_remove_queue($ipcHandle);
					}
				}
				if(!$terminated){
					$pid = file_get_contents($pidFile);
					if(!empty($pid)){
						$this->addLog("killing process $pid, and all childs");
						$childs = shell_exec('ps -o pid,ppid ax | awk \'$2 == \''.$pid.'\' { print $1 }\'');
						$childs = explode("\n", $childs);
						posix_kill($pid, 9);
						foreach($childs as $child){
							$childpid = intval($child);
							if($childpid > 0)
								$this->killPid($childpid);
						}
						$this->killPid($pid);
					}
					else
						$this->addLog("pidfile is empty");
				}
			}
			exit();
		}
		if(isset($this->Options['w'])){
			$pid = getmypid();
			$this->addLog('writing pid: '.$pid." to ".$pidFile);
			file_put_contents($pidFile, $pid);
			$this->addLog('writing ipc: '.$this->IPCID." to ".$ipcFile);
			file_put_contents($ipcFile, $this->IPCID);
		}
		if(isset($_SERVER['USER']) && ($_SERVER['USER'] != 'www-data')){
			die("You should run this script as www-data, not {$_SERVER['USER']}\n");
		}
		if(getenv('MAX_FOXES') != '')
			$this->maxFoxes = intval(getenv('MAX_FOXES'));
		else
			$this->maxFoxes = 1;
		$this->addLog("max foxes: " . $this->maxFoxes);
		parent::init();
	}

	function killPid($pid){
		$this->addLog("killing $pid");
		posix_kill($pid, 9);
		$error = posix_get_last_error();
		if(($error != 0) && ($error != 3))
			die("error: ($error) ".posix_strerror($error)."\n");
	}

	function checkAccounts(){
		if(isset($this->Options['c']))
			$this->processLoop();
		else{
			$this->processRows($this->getRows());
			$this->waitEmptyQueue();
		}
	}

	function addSQLLimit(){
		if(isset($this->Spread))
			return " limit ".$this->Spread;
		else
			return "";
	}

	function getRows(){
		$this->waitEmptyQueue($this->MaxProcesses);
		usleep(rand(200000, 600000));
		$rows = array();
		$limit = $this->addSQLLimit();
		if($limit != " limit 0"){
			$q = new TQuery($this->SQL.$limit);
			$priorities = array();
			while(!$q->EOF){
				$priorities[$q->Fields['Priority']][$q->Fields['ProviderID']][] = $q->Fields['AccountID'];
				$q->Next();
			}
			while((count($priorities) > 0) && (!isset($this->Options['m']) || (count($rows) < $this->Options['m']))){
				$providers = array_shift($priorities);
				$total = 0;
				foreach($providers as $accounts)
					$total += count($accounts);
				while((count($providers) > 0) && (!isset($this->Options['m']) || (count($rows) < $this->Options['m']))){
					foreach(array_keys($providers) as $providerId){
						if(rand(1, $total) <= count($providers[$providerId])){
							$rows[] = array_shift($providers[$providerId]);
							if(count($providers[$providerId]) == 0)
								unset($providers[$providerId]);
							if(isset($this->Options['m']) && (count($rows) >= $this->Options['m']))
								break;
							$total--;
						}
					}
				}
			}
		}
		return $rows;
	}
}

