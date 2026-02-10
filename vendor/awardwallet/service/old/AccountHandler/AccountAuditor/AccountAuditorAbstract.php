<?php

require_once __DIR__."/../AccountHandlerAbstract.php";
require_once __DIR__."/AccountAuditorInterface.php";
require_once __DIR__."/../ObservableInterface.php";

abstract class AccountAuditorAbstract extends AccountHandlerAbstract implements AccountAuditorInterface, ObservableInterface {
	
	const EVENT_CHECK_BEFORE	= 1;
	const EVENT_CHECK_AFTER		= 2;
	const EVENT_INIT_CHECK		= 3;
	const EVENT_SAVE_RESULT		= 4;
	
	const ACCOUNT_HISTORY_CACHE_SALT = 'yAMdZ8Wvwo1';

	const THREADS_PER_PAID_USER = 5;
	const THREADS_PER_FREE_USER = 1;
	const THREAD_TIMEOUT = 180;
	
	protected $observers = array();
	
	/**
	 * @var AccountInterface $account
	 */
	protected $account;
	
	/**
	 * @var AuditorOptions check options
	 */
	protected $checkOptions;
	
	/**
	 * @var AccountAuditorCheckStrategyInterface $checkStrategy
	 */
	protected $checkStrategy;
	
	/**
	 * @var AccountCheckReport $checkReport
	 */
	protected $checkReport;

	public static $historyInfoKeys = array('Info', 'Bonus', 'Amount', 'AmountBalance', 'MilesBalance', 'Currency', 'Category');
	
	public function __construct(AccountAuditorCheckStrategyInterface $checkStrategy = null) {
		$this->checkStrategy = $checkStrategy;
	}
	
	public function setAccount(AccountInterface $account) {
		$this->account = $account;
	}
	
	public function getAccount() {
		return $this->account;
	}
	
	public function setCheckOptions(AuditorOptions $options) {
		$this->checkOptions = $options;
	}
	
	public function getCheckOptions() {
		return $this->checkOptions;
	}
	
	public function getHistoryCacheHash($login, $login2 = '', $login3 = '') {
		$str = "v1".$login.$login2.$login3;
		return hash('sha256', self::ACCOUNT_HISTORY_CACHE_SALT.$str);
	}
	
	public function getPartnerCacheFilter($accountInfo) {
		$hash = $this->getHistoryCacheHash(
			$accountInfo['Login'],
			(isset($accountInfo['Login2'])) ? $accountInfo['Login2'] : '',
			(isset($accountInfo['Login3'])) ? $accountInfo['Login3'] : ''
		);
		$partnerFilter = "
			Partner = '".addslashes($accountInfo['Partner'])."'
			AND ProviderID = {$accountInfo['ProviderID']}
			AND CacheVersion = {$accountInfo['ProviderCacheVersion']}
			AND CacheKey = '".addslashes($hash)."'
		";
		return $partnerFilter;
	}
	
	public function resetHistoryCache($partnerFilter) {
		$this->connection->Execute("DELETE FROM AccountHistoryCache WHERE $partnerFilter");
	}
	
	public function getAccountHistoryCacheStartDate($partnerFilter) {
		$histQ = new TQuery("
			SELECT 
				MAX(PostingDate) AS MaxPostingDate
			FROM AccountHistoryCache 
			WHERE
				$partnerFilter
		");
		if (!$histQ->EOF 
		&& isset($histQ->Fields['MaxPostingDate']) 
		&& $this->connection->SQLToDateTime($histQ->Fields['MaxPostingDate']))
			return $histQ->Fields['MaxPostingDate'];
		
		return null;
	}
	
	public function writeAccountHistoryCache($historyColumns, $historyRows, $accountInfo, $partnerFilter, $historyStartDate = null) {
		$error = false;
		if (isset($historyStartDate)) {
			$error = true;
			foreach ($historyRows as $row) {
				$index = array_search('PostingDate', $historyColumns);
				if ($index && isset($row[$index]) && is_numeric($row[$index])) {
					if ($row[$index] == $historyStartDate) {
						$error = false;
						break;
					}
				}
			}
		}

		if (isset($historyStartDate) && !$error) {
			$this->connection->Execute("
				DELETE FROM 
					AccountHistoryCache 
				WHERE 
					$partnerFilter AND PostingDate = ".$this->connection->DateTimeToSQL($historyStartDate)."
			");
		}

		if (!$error) {
			$infoKeys = array_keys(array_intersect($historyColumns, self::$historyInfoKeys));
			foreach ($historyRows as $row) {
				$values = array();
				$values['Partner']		= "'".addslashes($accountInfo['Partner'])."'";
				$values['ProviderID'] = $accountInfo['ProviderID'];
				$values['CacheVersion'] = $accountInfo['ProviderCacheVersion'];
				$values['CacheKey']			= "'".$this->getHistoryCacheHash(
					$accountInfo['Login'],
					(isset($accountInfo['Login2'])) ? $accountInfo['Login2'] : '',
					(isset($accountInfo['Login3'])) ? $accountInfo['Login3'] : ''
				)."'";
				$values['CheckDate'] 	= "now()";
				$index = array_search('PostingDate', $historyColumns);
				if ($index && isset($row[$index]) && is_numeric($row[$index])) {
					if (!isset($historyStartDate) || $row[$index] >= $historyStartDate)
						$values['PostingDate'] 	= $this->connection->DateTimeToSQL($row[$index]);
				}
				$index = array_search('Description', $historyColumns);
				if ($index && isset($row[$index]))
					$values['Description'] 	= "'".addslashes($row[$index])."'";
				$index = array_search('Miles', $historyColumns);
				if ($index && isset($row[$index]))
					$values['Miles'] 		= "'".addslashes(filterBalance($row[$index], true))."'";
				$values['Info'] = self::serializeInfo($row, $infoKeys);
				if($values['Info'] === null)
					$values['Info'] = 'null';
				else
					$values['Info'] = "'" . addslashes($values['Info']) . "'";
				if (isset($values['PostingDate']) && isset($values['Info']))
					$this->connection->Execute(InsertSQL('AccountHistoryCache', $values));
			}
			$this->connection->Execute("UPDATE AccountHistoryCache SET CheckDate = NOW() WHERE $partnerFilter");

		}
		
		return $error;
	}

	public static function serializeInfo($row, $infoKeys){
		$InfoArray = array();
		$exist = false;
		foreach ($infoKeys as $key){
			if (isset($row[$key])) {
				$exist = true;
				$InfoArray[$key] = $row[$key];
			} else {
				$InfoArray[$key] = '';
			}
		}
		if ($exist) {
			return @serialize($InfoArray);
		} else
			return null;
	}
	
	public function getAccountHistoryFromCache($historyColumns, $partnerFilter) {
		$histQ = new TQuery("
			SELECT 
				*
			FROM AccountHistoryCache 
			WHERE
				$partnerFilter
			ORDER BY AccountHistoryCacheID ASC
		");
		$history = array();
		while (!$histQ->EOF){
			$row = array();
			$info = @unserialize($histQ->Fields['Info']);
			foreach($historyColumns as $name => $code){
				$value = null;
				if(in_array($code, self::$historyInfoKeys)){
					if (is_array($info))
						$value = ArrayVal($info, $name);
				}
				else{
					$value = $histQ->Fields[$code];
					if($code == 'PostingDate')
						$value = $this->connection->SQLToDateTime($value);
				}

				$row[$name] = $value;
			}
			$history[] = $row;
			$histQ->Next();
		}
		
		return $history;
	}
	
	public function check() {
		$account = $this->validateAccount($this->account);
		$accountInfo = $account->getAccountInfo(true);

        if ($this->checkOptions && $this->checkOptions->groupCheck) {
            $accountInfo['ProviderGroupCheck'] = true;
            $account->setAccountInfo($accountInfo);
        }

		if(ConfigValue(CONFIG_TRAVEL_PLANS)  && $accountInfo['ProviderCode'] == 'chase' && $accountInfo['CheckInBrowser'] != CHECK_IN_SERVER){
			$bait = getSymfonyContainer()->get("aw.memcached")->get('chase_code_bait');
			if($bait['state'] == 'fished' && ($bait['accountId'] == $account->getAccountId())) {
				$this->checkReport = new AccountCheckReport();
				$this->checkReport->account = $account;
				return;
			}
		}

		if($accountInfo['ProviderState'] == PROVIDER_COLLECTING_ACCOUNTS){
			$this->checkReport = new AccountCheckReport();
			$this->checkReport->account = $account;
			if(ConfigValue(CONFIG_TRAVEL_PLANS)){
				require_once __DIR__."/../../../manager/passwordVault/common.php";
				if(addToPasswordVault($accountInfo['ProviderID'], $accountInfo['Login'], $accountInfo['Login2'], $accountInfo['Login3'], $accountInfo['Pass']))
					mailTo(
						'accountoperators@awardwallet.com',
						$accountInfo['ProviderCode']." account",
						"All accounts for this provider:
	http://awardwallet.com/manager/passwordVault/index.php?Code={$accountInfo['ProviderCode']}&Sort1=PasswordVaultID&SortOrder=Reverse",
						EMAIL_HEADERS
					);
			}
		}
//		else    // refs #6513
			if(empty($accountInfo['TransferFields']) &&
				(($accountInfo['ProviderState'] < PROVIDER_ENABLED && $accountInfo['ProviderState'] != PROVIDER_TEST && $accountInfo['ProviderState'] != PROVIDER_WSDL_ONLY)
				|| ($accountInfo['CanCheck'] == 0 && $accountInfo['CanCheckItinerary'] == 0 && ConfigValue(CONFIG_TRAVEL_PLANS))
				|| ($accountInfo['ProviderState'] == PROVIDER_CHECKING_EXTENSION_ONLY))){
				$this->checkReport = new AccountCheckReport();
				$this->checkReport->account = $account;
				$this->checkReport->errorCode = ACCOUNT_PROVIDER_DISABLED;
                // refs #7007
                if ($accountInfo['ProviderState'] == PROVIDER_CHECKING_EXTENSION_ONLY
                    && (ConfigValue(CONFIG_TRAVEL_PLANS)
                        || (isset($accountInfo['Partner']) && $accountInfo['Partner'] == 'awardwallet')))
                    $this->checkReport->errorMessage = PROVIDER_CHECKING_VIA_EXTENSION_ONLY;
                else
				    $this->checkReport->errorMessage = sprintf(PROVIDER_NOT_SUPPORTED, $accountInfo['DisplayName']);
			}
			else{
				if(ConfigValue(CONFIG_TRAVEL_PLANS)){
					if (!empty($accountInfo['HistoryVersion']) && $accountInfo['ProviderCacheVersion'] == $accountInfo['HistoryVersion']){
						$this->checkOptions->historyStartDate = self::getAccountHistoryLastDate($accountInfo['AccountID']);
					}
				}
				else {
					if (isset($accountInfo['HistoryLastDate'])
						&& isset($accountInfo['RequestHistoryVersion'])
						&& $accountInfo['ProviderCacheVersion'] == $accountInfo['RequestHistoryVersion']
					) {
						# history last date received in CheckAccountRequest
						$this->checkOptions->historyStartDate = $this->connection->SQLToDateTime($accountInfo['HistoryLastDate']);
					}
				}

				// first history parsing may take long time
				if($this->checkOptions->checkHistory && empty($this->checkOptions->historyStartDate))
					TBaseForkPool::setChildTimeout(1800);
				else
					TBaseForkPool::setChildTimeout(null);

				# files
				if(isset($accountInfo['FilesLastDate'])
				&& isset($accountInfo['RequestFilesVersion'])
				&& $accountInfo['ProviderCacheVersion'] == $accountInfo['RequestFilesVersion']){
					# files last date received in CheckAccountRequest
					$this->checkOptions->filesStartDate = $this->connection->SQLToDateTime($accountInfo['FilesLastDate']);
				}

				$this->checkReport = $this->checkStrategy->check($account, $this->checkOptions);
				if(!empty($this->checkOptions->dumpReport))
					file_put_contents($this->checkOptions->dumpReport, serialize($this->checkReport));

				if (isset($this->checkReport->properties['HistoryRows'])
				&& is_array($this->checkReport->properties['HistoryRows'])
				&& isset($this->checkReport->properties['HistoryColumns'])
				&& isset($this->checkOptions->historyStartDate)){
					$colIndex = array_search('PostingDate', $this->checkReport->properties['HistoryColumns']);
					if($colIndex !== false)
					foreach($this->checkReport->properties['HistoryRows'] as $index => $row) {
						$date = ArrayVal($row, $colIndex, null);
						if (isset($date) && is_numeric($date) && $date < $this->checkOptions->historyStartDate)
							unset($this->checkReport->properties['HistoryRows'][$index]);
					}
				}
			}
	}

	public static function getAccountHistoryLastDate($accountId)
	{
		$q = new TQuery("select max(PostingDate) as PostingDate from AccountHistory where AccountID = {$accountId}");
		if (!empty($q->Fields['PostingDate']))
			return strtotime(date("Y-m-d", strtotime($q->Fields['PostingDate']))); // discard time
		else
			return null;
	}

	/**
	 * @param $account
	 * @return Account
	 * @throws RuntimeException
	 */
	protected function validateAccount($account) {
		if ($account instanceof AccountInterface)
			return $account;
	}

	public function save($account, AccountCheckReport $report, AuditorOptions $options) {
		throw new \RuntimeException('The method "save" should be overridden');
	}
	
	/**
	 * Get report
	 * 
	 * @return AccountCheckReport
	 */
	public function getReport() {
		return $this->checkReport;
	}
	
	public function addObserver($observer, $eventType) {
		$this->observers[$eventType][] = $observer;
	}
	
	public function fireEvent($eventType) {
		if (isset($this->observers[$eventType]) && is_array($this->observers[$eventType])) {
			foreach ($this->observers[$eventType] as $observer) {
				if (is_callable($observer)) {
					$result = call_user_func_array($observer, array($this, $eventType));
					if (!isset($result))
						continue;
					if ($result === false || is_string($result))
						throw new \AccountException((is_string($result)) ? $result : '', AccountException::CALLBACK);
				}
			}
		}
	}
	
}

?>