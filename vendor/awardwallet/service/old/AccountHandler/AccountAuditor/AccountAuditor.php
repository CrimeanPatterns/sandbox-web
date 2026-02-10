<?php

use AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface;

require_once __DIR__."/AccountAuditorAbstract.php";

class AccountAuditor extends AccountAuditorAbstract {

	const CHECK_TIMEOUT = 60 * 30;
	const CANCELLED_MESSAGE = 'Update was cancelled. Please try again later.';

	/**
	 * @var TMySQLConnection
	 */
	public $connection;
	
	public function __construct(AccountAuditorCheckStrategyInterface $checkStrategy = null, $connection = null) {
		global $Connection, $sPath;
		if (isset($connection))
			$this->connection = $connection;
		else
			$this->connection = $Connection;
		parent::__construct($checkStrategy);
		# Auto check Its
		$this->addObserver(array($this, 'autoCheckItsCallback'), AccountAuditorAbstract::EVENT_INIT_CHECK);
        # Auto check account history
        $this->addObserver(array($this, 'autoCheckAccountHistoryCallback'), AccountAuditorAbstract::EVENT_INIT_CHECK);
	}

	public function check() {
		$startTime = microtime(true);
		$this->fireEvent(AccountAuditorAbstract::EVENT_INIT_CHECK);

		# Account info
		$account = $this->getAccount();
		$accountInfo = $account->getAccountInfo();

		$this->fireEvent(AccountAuditorAbstract::EVENT_CHECK_BEFORE);
		parent::check();
		$this->fireEvent(AccountAuditorAbstract::EVENT_CHECK_AFTER);
		
		$options = $this->getCheckOptions();
		$report = $this->checkReport;
		if (!isset($report) || is_bool($report))
			return $report;

		$report->account = $account;
		$report->filter();

		$endTime = microtime(true);
		$report->duration = $endTime - $startTime;

		# Save log
		if ($options->saveLog && isset($report->logPath) && file_exists($report->logPath."/log.html")){
			$this->saveLog($report->logPath."/log.html", "Login: ".htmlspecialchars($accountInfo["Login"])."<br>
				AccountID: {$accountInfo["AccountID"]}<br>
				Balance: ".(is_null($report->balance) ? 'n/a' : $report->balance)."<br>
				ErrorCode: ".$report->errorCode."<br>
				ErrorMessage: ".htmlspecialchars($report->errorMessage)."<br>
				ErrorReason: ".htmlspecialchars($report->errorReason)."<br>
				DebugInfo: ".htmlspecialchars($report->debugInfo)."<br>
				Duration: ".$report->duration."<br>
				Properties: ".print_r($report->properties, true));
			if ($report->warnings || ($report->errorCode != ACCOUNT_CHECKED) || $options->keepLogs) {
				$fields = $accountInfo;
				if (!empty($options->transferFields)) {
					if (!empty($options->transferFields['ccFull']))
						$fields = array_merge($fields, array_intersect_key($options->transferFields['ccFull'], ['CardNumber' => '', 'SecurityNumber' => '']));
				}
				TAccountChecker::ArchiveLogs($report->logPath, file_get_contents($report->logPath."/log.html"), "account-{$accountInfo["AccountID"]}-".time()."-".rand(1, 1000), $fields);
			}
			DeleteFiles($report->logPath."/*");
			rmdir($report->logPath);
		}

		# check, may be account was deleted during timely check
		$qAcc = new TQuery("select AccountID from Account where AccountID = {$accountInfo["AccountID"]}", $this->connection);
		if ($qAcc->EOF)
			throw new \AccountException("Account was deleted");
	}

	/**
	 * @param AccountInterface $account
	 * @param AccountCheckReport $report
	 * @param AuditorOptions $options
	 * @return bool
	 * @throws AccountException
	 * @throws DieTraceException
	 */
	public function save($account, AccountCheckReport $report, AuditorOptions $options) {
		global $arCheckedBy;
		$startTime = microtime(true);

		$this->fireEvent(AccountAuditorAbstract::EVENT_SAVE_RESULT);
		if (!$account instanceof AccountInterface)
			throw new \RuntimeException('Wrong account');

		if($report->errorCode == ACCOUNT_UNCHECKED && ConfigValue(CONFIG_TRAVEL_PLANS)) // cancelled checks
			throw new AccountException(self::CANCELLED_MESSAGE);

		# Account info
		$accountInfo = $account->getAccountInfo(false);
		$logContext = [
			"AccountID" => (string)$accountInfo["AccountID"],
            "AccountUserID" => (string)$accountInfo["UserID"],
			"checkedBy" => isset($arCheckedBy[$options->checkedBy]) ? $arCheckedBy[$options->checkedBy] : 'unknown',
			"browserState" => strlen($report->browserState),
            "CheckPriority" => $options->priority,
		];

		// group check
		$currentGroupCheck = self::getGroupCheck($account->getAccountId());
		if (null !== $report->providerCode && isset($accountInfo['ProviderCode']) && $report->providerCode !== $accountInfo['ProviderCode']
		&& (in_array(ACCOUNT_REQUEST_OPTION_PROVIDER_GROUP_CHECK, $report->options) || (!empty($currentGroupCheck) && $currentGroupCheck != $accountInfo['ProviderID']))){
			if($currentGroupCheck && !in_array($report->errorCode, [ACCOUNT_CHECKED, ACCOUNT_WARNING, ACCOUNT_QUESTION])) {
				// do not save invalid group check attempts
				return false;
			}

			$account->setAccountInfo(null);
			$this->connection->Execute(sprintf("UPDATE `Account` SET ProviderID = (SELECT ProviderID FROM Provider WHERE Code = '%s') WHERE AccountID = %d", $report->providerCode, $accountInfo["AccountID"]));
			$accountInfo = $account->getAccountInfo(false);
		}

		$userID = $accountInfo['UserID'];

		$onlyItineraries = $accountInfo['CanCheck'] == 0
			&& $accountInfo['CanCheckItinerary'] == 1
			&& !in_array($options->checkedBy, [CHECKED_BY_EMAIL, CHECKED_BY_SUBACCOUNT])
			&& ConfigValue(CONFIG_TRAVEL_PLANS) && !isGranted('UPDATE', getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find($accountInfo['AccountID']));

		if(!ConfigValue(CONFIG_TRAVEL_PLANS))
			$this->connection->Execute("insert into AccountTraffic(ProviderID, CreationDate, Downloaded, Duration)
			values({$accountInfo["ProviderID"]}, now(), $report->traffic, '".$report->duration."')");

		$result = false;
        if ($accountInfo['CreationDate'] == $accountInfo['UpdateDate']
            && !empty($accountInfo['Goal']) && $accountInfo['Goal'] > 0)
            $report->properties['Goal'] = $accountInfo['Goal'];
		$countError = true;
		$this->connection->Execute("
			UPDATE
				Account
			SET
				".( !ConfigValue(CONFIG_TRAVEL_PLANS) ? "UpdateDate = Now()," : "" )."
		       	CheckedBy  = ".(isset($options->checkedBy)?$options->checkedBy:"NULL").",
		       	BrowserState = '".addslashes($report->browserState)."',
		       	".( $countError ? "ErrorCount = case when ErrorCode = {$report->errorCode} then ErrorCount + 1 else 0 end," : "")."
		       	ErrorDate =  case when ErrorCode <> {$report->errorCode} or ErrorDate is null then now() else ErrorDate end
			WHERE
				AccountID  = {$accountInfo["AccountID"]}
		");
		if(ConfigValue(CONFIG_TRAVEL_PLANS)) {
		    $q = new TQuery("select AutoGatherPlans, AccountLevel from Usr where UserID = {$accountInfo['UserID']}");
		    $logContext["AccountLevel"] = $q->Fields["AccountLevel"];
            if($options->checkIts && (int)$q->Fields["AutoGatherPlans"] === 0)
                DieTrace("Checking its for user {$accountInfo['UserID']} while AutoGatherPlans = 0", false);
            $this->disableAccount($report, $accountInfo);
		}
		// check that last balance was not 0, and now unexpected 0, retry
		if ($options->checkIts){
			$this->connection->Execute("update Trip set Parsed = 0 where AccountID = {$accountInfo["AccountID"]}");
			$this->connection->Execute("update Reservation set Parsed = 0 where AccountID = {$accountInfo["AccountID"]}");
			$this->connection->Execute("update Rental set Parsed = 0 where AccountID = {$accountInfo["AccountID"]}");
		}
		switch ($report->errorCode) {
			case ACCOUNT_CHECKED:
				if (!$report->isTransfer)
					$report->errorMessage = '';
			case ACCOUNT_WARNING:
				$durationSet = '';
				if (ConfigValue(CONFIG_TRAVEL_PLANS)){
					# LastDurationWithoutPlans && LastDurationWithPlans
					if (
                        ($report->duration > 0) &&
                        ($options->priority !== 2)
                    ) {
						$durationSet .= ($options->checkIts) ? 'LastDurationWithPlans' : 'LastDurationWithoutPlans';
						$durationSet .= " = '".  addslashes($report->duration) ."'";
						$durationSet = ", ".$durationSet;
					}
				}
				$historySet = '';
				if(array_key_exists('HistoryLastDate', $accountInfo)
				&& array_key_exists('RequestHistoryVersion', $accountInfo)){
					$historySet = ", ResponseHistoryVersion = {$accountInfo['ProviderCacheVersion']}";
					if($accountInfo['ProviderCacheVersion'] == $accountInfo['RequestHistoryVersion'] && isset($accountInfo['HistoryLastDate']))
						$historySet .= ", HistoryCacheValid = true";
					else
						$historySet .= ", HistoryCacheValid = false";
				}
				if(array_key_exists('FilesLastDate', $accountInfo)
				&& array_key_exists('RequestFilesVersion', $accountInfo)){
					$historySet .= ", ResponseFilesVersion = {$accountInfo['ProviderCacheVersion']}";
					if($accountInfo['ProviderCacheVersion'] == $accountInfo['RequestFilesVersion'] && isset($accountInfo['FilesLastDate']))
						$historySet .= ", FilesCacheValid = true";
					else
						$historySet .= ", FilesCacheValid = false";
				}
				if(ConfigValue(CONFIG_TRAVEL_PLANS) && $options->checkHistory) {
					if(isset($report->properties['HistoryVersion']))
						$version = $report->properties['HistoryVersion'];
					else
						$version = $accountInfo['ProviderCacheVersion'];
					$historySet .= ", HistoryVersion = " . $version;
					$logContext["HistoryVersion"] = $version;
				}
				$aaSet = '';
				// do not remove password until locally saved
//				if(ConfigValue(CONFIG_TRAVEL_PLANS) && $accountInfo['ProviderCode'] == 'aa' && !accountSharedWithBooker($accountInfo['AccountID'])) /* aa test */
//					$aaSet = ", SavePassword = ".SAVE_PASSWORD_LOCALLY.", Pass = ''"; // remove password on successful check, for accounts added from mobile interface
				$this->connection->Execute("
					UPDATE Account
					SET    ErrorCode         = ".$report->errorCode."                         			,
					       ErrorMessage      = '" . addslashes($report->errorMessage) . "',
					       ErrorReason      = '" . addslashes($report->errorReason) . "',
					       DebugInfo      = '" . addslashes($report->debugInfo) . "',
					       SuccessCheckDate  = now()
					       ".(($options->checkedBy == CHECKED_BY_EMAIL)?", EmailParseDate = now() ":"")."
					       ".($options->checkIts && ConfigValue(CONFIG_TRAVEL_PLANS)?", LastCheckItDate = now()":"")."
					       ".($options->checkHistory && ConfigValue(CONFIG_TRAVEL_PLANS)?", LastCheckHistoryDate = now()":"")."
						   $durationSet
						   $historySet
						   $aaSet
					WHERE  AccountID         = {$accountInfo["AccountID"]}
				");
                $subAccountsBefore = [];
				if(ConfigValue(CONFIG_TRAVEL_PLANS)) {
					if(!$onlyItineraries) {
                        // update zip code
                        if(!empty($report->properties['ZipCode']) && empty($accountInfo['UserAgentID'])){
                            $parsedAddress = empty($report->properties['ParsedAddress']) ? null : $report->properties['ParsedAddress'];
                            $this->saveAddressInfo($report->properties['ZipCode'], $parsedAddress, $accountInfo['ProviderID'], $accountInfo["AccountID"], $accountInfo['UserID']);
                        }
					}
                    $subAccountsBefore = getRepository(\AwardWallet\MainBundle\Entity\Subaccount::class)->findBy(['accountid' => $accountInfo["AccountID"]]);
                    $accountState = $this->connection->getEntityState('Account', $accountInfo["AccountID"]);
//					getSymfonyContainer()->get('aw.push_notification.listener')->setBackground(CHECKED_BY_BACKGROUND == $this->checkOptions->checkedBy);
                }
                self::getNextEliteLevel($accountInfo, $report->properties);
				if(!$onlyItineraries) {
					$noSaveWarnings = SaveAccountProperties($accountInfo["AccountID"], $report->properties, $accountInfo, $report->balance);
					$options->noWarnings = $options->noWarnings && $noSaveWarnings;
				}
				if ($options->checkIts){
					$sameIts = false;
					if(ConfigValue(CONFIG_TRAVEL_PLANS)) {
						$tracker = getSymfonyContainer()->get('aw.diff.tracker');
						$oldTrackedProperties = $tracker->getProperties($accountInfo["AccountID"]);

                        if (in_array((int) $accountInfo['UserID'], [5476, 324083], true)) {
                            getSymfonyContainer()->get('monolog.logger.stat')->warning('diff_tracker_debug', [
                                'source' => 'auditor',
                                'userid' => (int) $accountInfo['UserID'],
                                'old_properties' => array_map(
                                    function (\AwardWallet\MainBundle\Timeline\Diff\Properties $source) { return ['source' => $source->source, 'values' => $source->values]; },
                                    $oldTrackedProperties
                                )
                            ]);
                        }

						$trips = array_intersect_key($report->properties, array_flip(array('Itineraries', 'Rentals', 'Reservations', 'Restaurants')));
						$newTripsHash = hash('sha256', serialize($trips));
						$sameIts = !empty($accountInfo['TripsHash']) && $accountInfo['TripsHash'] == $newTripsHash;
					}
					$_SESSION['SaveConflicts'] = array();
                    $restoreOnUpdate = false;
                    if(ConfigValue(CONFIG_TRAVEL_PLANS)) {
                        switch ($options->source) {
                            case UpdaterEngineInterface::SOURCE_DESKTOP:
                            case UpdaterEngineInterface::SOURCE_MOBILE:
                                $restoreOnUpdate = true;
                                break;
                        }
                    }
					saveItineraries($accountInfo, $report->properties, $options->noWarnings, $this->checkOptions, $sameIts, $restoreOnUpdate);
					if (count($_SESSION['SaveConflicts']) > 0 && isset($_SESSION['UserID']) && isset($_SESSION['ConflictedItineraries'])){
						$_SESSION['ConflictedItineraries'][$accountInfo["AccountID"]] = array(
							"AccountID" => $accountInfo["AccountID"],
							"UserID" => $accountInfo["UserID"],
							"Properties" => $report->properties,
							"ProviderID" => $accountInfo['ProviderID'],
							"UserAgentID" => $accountInfo['UserAgentID'],
							"Itineraries" => $_SESSION['SaveConflicts']
						);
					}

					if(ConfigValue(CONFIG_TRAVEL_PLANS)) {
						$this->updatePlans($accountInfo["UserID"], $accountInfo['AccountID'], $accountInfo['TripsHash'], $newTripsHash, $report->properties, $options);
						$tracker->recordChanges($oldTrackedProperties, $accountInfo["AccountID"], $accountInfo['UserID']);
					}
				}
				if ($options->checkHistory) {
					if (isset($report->properties['HistoryRows']) && isset($report->properties['HistoryColumns'])) {
						$resetHistoryCache = false;
						if(isset($report->properties['HistoryCacheValid']))
							$resetHistoryCache = !$report->properties['HistoryCacheValid'];
						if(!ConfigValue(CONFIG_TRAVEL_PLANS))
							$resetHistoryCache = true;
						$logContext['ResetHistoryCache'] = $resetHistoryCache;
						$logContext['HistoryRows'] = count($report->properties['HistoryRows']);
						saveAccountHistory($accountInfo["AccountID"], $report->properties['HistoryColumns'], $report->properties['HistoryRows'], $resetHistoryCache);
						unset($report->properties['HistoryColumns'], $report->properties['HistoryRows'], $report->properties['HistoryCacheValid'], $report->properties['HistoryVersion']);
					}
				}
				if ($options->checkFiles)
					$this->saveFiles($accountInfo["AccountID"], $report->files);
				$result = true;

                if (ConfigValue(CONFIG_TRAVEL_PLANS)) {
                    $this->connection->sendUpdateEvent($accountState);
                    $rep = getRepository(\AwardWallet\MainBundle\Entity\Account::class);
                    $accountEnt = $rep->findOneByAccountid($account->getAccountId());
					getSymfonyContainer()->get('doctrine.orm.entity_manager')->refresh($accountEnt);
					if ($accountEnt) {
						$event = new \AwardWallet\MainBundle\Event\AccountUpdateEvent($accountInfo, $accountEnt, $report, $subAccountsBefore);
                        getSymfonyContainer()->get('event_dispatcher')->dispatch($event, 'aw.account.update');
                    }

					// save AA account number as login for account logged-in by Email or Login
					if($accountInfo['ProviderCode'] == 'aa' && !empty($report->properties['Number']) && strcasecmp($accountInfo['Login'], $report->properties['Number']) != 0){
						$this->connection->Execute("update Account set Login = '" . addslashes($report->properties['Number']) . "' where AccountID = {$accountInfo['AccountID']}");
					}
                }
				break;
			case ACCOUNT_INVALID_PASSWORD:
			case ACCOUNT_MISSING_PASSWORD:
			case ACCOUNT_LOCKOUT:
			case ACCOUNT_PROVIDER_DISABLED:
			case ACCOUNT_TIMEOUT:
			case ACCOUNT_PREVENT_LOCKOUT:
			case ACCOUNT_PROVIDER_ERROR:
			case ACCOUNT_ENGINE_ERROR:
			case ACCOUNT_QUESTION:
				if($report->errorCode == ACCOUNT_QUESTION)
					$this->connection->Execute("UPDATE Answer SET Valid = 0 where AccountID = {$accountInfo["AccountID"]}
 					and Question = '".addslashes($report->question)."'");

				$this->connection->Execute("
					UPDATE Account
					SET    ErrorCode    = ".$report->errorCode.",
					       ErrorMessage = '" . addslashes($report->errorMessage) . "',
					       ErrorReason = '" . addslashes($report->errorReason) . "',
					       DebugInfo = '" . addslashes($report->debugInfo) . "',
					       Question = ".(isset($report->question)?"'".addslashes($report->question)."'":"null")."
					WHERE  AccountID    = {$accountInfo["AccountID"]}
				");
				$options->noWarnings = false;
				break;
			default:
				DieTrace("unknown error code: ".$report->errorCode."");
		}

		# Invalid answers
		foreach($report->invalidAnswers as $question => $answer) {
			$this->connection->Execute("UPDATE Answer SET Valid = 0 where AccountID = {$accountInfo["AccountID"]}
				and Question = '" . addslashes($question) . "' and Answer = '" . addslashes($answer) . "'");
		}

		if (ConfigValue(CONFIG_TRAVEL_PLANS)){
            // getAccountNextCheck relies on Account.UpdateDate, we need to set UpdateDate before calclulating next check date
            $this->connection->Execute("UPDATE Account SET UpdateDate = NOW() WHERE  AccountID     = {$accountInfo["AccountID"]}");
            $logContext['UpdateDate'] = $accountInfo['UpdateDate'];
            $logContext['Time'] = date("Y-m-d H:i:s", $this->connection->GetTime());
            $updateDateTime = time();
		    $accountNextCheckData = getSymfonyContainer()->get(\AwardWallet\MainBundle\Service\BackgroundCheckScheduler::class)->schedule($accountInfo["AccountID"]);
            getSymfonyContainer()->get("event_dispatcher")->dispatch(new \AwardWallet\MainBundle\Event\AccountChangedEvent($accountInfo['AccountID']), \AwardWallet\MainBundle\Event\AccountChangedEvent::NAME);
            $logContext = array_merge($logContext, $accountNextCheckData);
            if(isset($logContext['NextCheckDate']))
                $logContext['NextCheckDateStr'] = date("Y-m-d H:i:s", $logContext['NextCheckDate']);
            if (0 === $updateDateTime - strtotime($accountInfo['UpdateDate'])) {
                getSymfonyContainer()->get('monolog.logger.stat')->warning('Account.UpdateDate collision', ['_aw_server_module' => 'accountAuditor', '_aw_account_id' => $accountInfo["AccountID"], '_aw_provider_id' => $accountInfo["ProviderID"], '_aw_userid' => $accountInfo['UserID']]);
            }
		}
		$duration = microtime(true) - $startTime;
		$logContext['duration'] = $duration;
		$logContext['errorCode'] = $report->errorCode;
		$logContext['balance'] = $report->balance;
		if(ConfigValue(CONFIG_TRAVEL_PLANS)) {
		    // WARNING: this log record used in stats calculation and fraud detection, do not modify it without good reason
			getSymfonyContainer()->get("monolog.logger.stat")->addInfo("account saved", $logContext);
		}
		return $result;
	}

	private function saveAddressInfo($zipCode, $parsedAddress, $providerId, $accountId, $userId)
    {
        $newKind = Lookup("Provider", "ProviderID", "Kind", $providerId);

        $q = new TQuery("select p.Kind, u.Zip, u.ParsedAddress from Usr u 
        join Account a on u.ZipCodeAccountID = a.AccountID
        join Provider p on p.ProviderID = a.ProviderID
        where u.UserID = $userId");
        if(!$q->EOF)
            $oldKind = $q->Fields["Kind"];

        if(empty($oldKind) || ($newKind == PROVIDER_KIND_CREDITCARD) || ($oldKind != PROVIDER_KIND_CREDITCARD)) {
            $this->connection->Execute("update Usr 
            set Zip = '" . addslashes($zipCode) . "', ZipCodeProviderID = {$providerId}, ZipCodeAccountID = {$accountId}, ZipCodeUpdateDate = now()
            where UserID = {$userId}");

            if (!empty($parsedAddress)) {
                $this->connection->Execute("update Usr set ParsedAddress = '".addslashes($parsedAddress)."'
                where UserID = {$userId}");
            }

            if (
                (trim($zipCode) !== trim(!$q->EOF ? $q->Fields['Zip'] : '')) &&
                ($user = getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($userId)) &&
                ($task = \AwardWallet\MainBundle\Worker\AsyncProcess\StoreLocationFinderTask::createFromUser($user))
            ) {
                getSymfonyContainer()->get("aw.async.process")->execute($task->setClearExistingPoints(true));
            }
        }
    }

	protected function updatePlans($userId, $accountId, $oldHash, $newHash, $properties, AuditorOptions $auditorOptions = null){
		global $Connection;
		if($newHash != $oldHash || ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_DEBUG){
			$Connection->Execute("update Account set TripsHash = '{$newHash}' where AccountID = {$accountId}");
		}
	}

	protected function saveFiles($accountId, array $files){
		$this->connection->Execute("delete from AccountFile where AccountID = $accountId");
		foreach($files as $file){
			if(!empty($file['FileDate']) && !empty($file['Name']) && !empty($file['Extension']) && !empty($file['Contents'])){
				$file = array_intersect_key($file, array_flip(array("FileDate", "Name", "Extension", "AccountNumber", "AccountName", "AccountType", "Contents")));
				foreach(array("Name", "Extension", "AccountNumber", "AccountName", "AccountType") as $key)
					if(!empty($file[$key]) && trim($file[$key]) != '')
						$file[$key] = "'".addslashes(trim($file[$key]))."'";
					else
						unset($file[$key]);
				$file['FileDate'] = $this->connection->DateTimeToSQL($file['FileDate']);
				$filename = $file['Contents'];
				$file['Contents'] = "'".addslashes(base64_encode(file_get_contents($filename)))."'";
				unlink($filename);
				$file['AccountID'] = $accountId;
				$this->connection->Execute(InsertSQL("AccountFile", $file));
			}
		}
	}

	public function autoCheckItsCallback($obj, $event) {
		global $Connection;
		$options = $obj->getCheckOptions();
		if (!is_null($options->checkIts))
			return null;
		$account = $obj->getAccount();
		$accountID = $account->getAccountId();
		$q = new TQuery("
			SELECT (u.AutoGatherPlans = 1 AND p.CanCheckItinerary = 1) as AutoGatherPlans, a.LastCheckItDate
			FROM   Usr u
			       JOIN Account a
			       ON     u.UserID = a.UserID
			       LEFT JOIN Provider p
			       ON     p.ProviderID = a.ProviderID
			WHERE  a.AccountID     = $accountID
		", $obj->connection);
		if ($q->EOF)
			throw new AccountNotFoundException("Account #{$accountID} was not found");
		
		$options->checkIts = ($q->Fields['AutoGatherPlans'] == '1') && ($q->Fields['LastCheckItDate'] == '' || ((time() - $Connection->SQLToDateTime($q->Fields['LastCheckItDate'])) > SECONDS_PER_DAY));
		$obj->setCheckOptions($options);
	}

    public function autoCheckAccountHistoryCallback($obj, $event) {
        global $Connection;
        $options = $obj->getCheckOptions();
        if (!is_null($options->checkHistory))
            return null;

        $account = $obj->getAccount();
        $accountID = $account->getAccountId();
        $q = new TQuery("
			SELECT p.CanCheckHistory, a.LastCheckHistoryDate
			FROM   Account a
			       LEFT JOIN Provider p
			       ON     p.ProviderID = a.ProviderID
			WHERE  a.AccountID     = $accountID
		", $obj->connection);
        if ($q->EOF)
            throw new AccountNotFoundException("Account #{$accountID} was not found");

        $options->checkHistory = ($q->Fields['CanCheckHistory'] == '1') && !empty($q->Fields['LastCheckHistoryDate']);
        $obj->setCheckOptions($options);
    }

	public function nullQueueDate(Account $account) {
		$this->connection->Execute("
			UPDATE Account
			SET QueueDate = null
			WHERE AccountID = {$account->getAccountId()}
		");
	}

	public function nullExpirationDate(Account $account) {
		$this->connection->Execute("
			UPDATE Account
			SET ExpirationDate = null, ExpirationAutoSet = " . EXPIRATION_UNKNOWN . "
			WHERE AccountID = {$account->getAccountId()}
		");
	}


	private function saveLog($file, $content) {
		$_file = fopen($file, "a");
		fwrite($_file, $content);
		fclose($_file);
	}

	public static function getUserThrottler($userId){
		return new ProcessThrottler(Cache::getInstance(), "user_threads_".$userId, self::THREADS_PER_PAID_USER);
	}

	public static function requestSent(CheckAccountRequest $request){
		if($request->Priority > 5 && ConfigValue(CONFIG_TRAVEL_PLANS)){
			// user initiated request, track it to throttle by threads
			self::getUserThrottler($request->UserID)->addProcess($request->AccountID, self::THREAD_TIMEOUT);
		}
	}

	public static function getUserMaxThreads($accountLevel){
		if($accountLevel == ACCOUNT_LEVEL_AWPLUS || $accountLevel == ACCOUNT_LEVEL_BUSINESS)
			$max = self::THREADS_PER_PAID_USER;
		else
			$max = self::THREADS_PER_FREE_USER;
		return $max;
	}

	public static function getUserFreeThreads($userId, $accountLevel){
		return max(self::getUserMaxThreads($accountLevel) - self::getUserThrottler($userId)->getProcessCount(), 0);
	}

	public static function setGroupCheck($accountId, $providerId){
		if(!empty($providerId) && !empty(self::getGroupCheck($accountId)))
			return false;
		\Cache::getInstance()->set("AccGroupCheck_" . $accountId, $providerId, self::CHECK_TIMEOUT);
		return true;
	}

	public static function getGroupCheck($accountId){
		return \Cache::getInstance()->get("AccGroupCheck_" . $accountId);
	}

    public static function propertyKindExists(array $properties, $needle, $returnPropertyValue = false)
    {
        $result = false;
        if(isset($needle)){
            $associative = !is_numeric($needle);
            foreach ($properties as $index => $property) {
                if ($associative) {
                    if (0 === strcmp($index, $needle)) {
                        $result = true;
                        $value = $property;
                        break;
                    }
                } elseif ($property instanceof PropertyType && $property->Kind == $needle) {
                    $result = true;
                    $value = $property->Value;
                    break;
                }
            }
        }
        if ($returnPropertyValue) {
            $result = isset($value) ? $value : null;
        }
        return $result;
    }

    public static function getNextEliteLevel($fields, array &$properties)
    {
        if(empty($properties)){
            return null;
        }
        $values = array_values($properties);
        $providerProperties = [
            PROPERTY_KIND_NEXT_ELITE_LEVEL => PROPERTY_KIND_NEXT_ELITE_LEVEL,
            PROPERTY_KIND_MILES_TO_NEXT_LEVEL => PROPERTY_KIND_MILES_TO_NEXT_LEVEL,
            PROPERTY_KIND_STATUS => PROPERTY_KIND_STATUS
        ];
        if (isset($values[0]) && $values[0] instanceof PropertyType) {
            $associative = false;
        } else {
            $associative = true;
            $providerProperties = SQLToArray("
                SELECT
                    pp.Kind,
                    pp.Code
                FROM ProviderProperty pp
                WHERE
                    pp.ProviderID = {$fields['ProviderID']} AND
                    pp.Kind IN (" . implode(', ', $providerProperties) . ")", 'Kind', 'Code');
        }
        if (!self::propertyKindExists($properties, ArrayVal($providerProperties, PROPERTY_KIND_NEXT_ELITE_LEVEL, 'NextEliteLevel'))) {
            $sql = "SELECT Name FROM EliteLevel
    					WHERE ProviderID = {$fields['ProviderID']} AND ByDefault = 1";
            $eliteLevelName = self::propertyKindExists($properties, ArrayVal($providerProperties, PROPERTY_KIND_STATUS), true);
            $orderBy = "`Rank`";
            if (isset($eliteLevelName)) {
                $sql .= " AND `Rank` > (SELECT el.Rank
                                          FROM TextEliteLevel tel
                                          JOIN EliteLevel el ON el.EliteLevelID = tel.EliteLevelID
                                          WHERE
                                            tel.ValueText = '" . addslashes($eliteLevelName) . "'
                                            AND el.ProviderID = {$fields['ProviderID']} limit 1)";
            } else {
                if (self::propertyKindExists($properties, ArrayVal($providerProperties, PROPERTY_KIND_MILES_TO_NEXT_LEVEL))) {
                    $sql = "SELECT Name, MIN(`Rank`) FROM EliteLevel WHERE ProviderID = {$fields['ProviderID']} AND ByDefault = 1 GROUP BY Name";
                    $orderBy = "MIN(`Rank`)";
                } else {
                    $sql .= " AND 0 = 1";
                }
            }
            $sql .= " order by $orderBy limit 1";
            $q = new TQuery($sql);
            if (!$q->EOF) {
                if($associative){
                    $properties['NextEliteLevel'] = $q->Fields['Name'];
                }else{
                    $properties[] = new PropertyType("NextEliteLevel", 'Next Elite Level', PROPERTY_KIND_NEXT_ELITE_LEVEL, $q->Fields['Name']);
                }
                return $q->Fields['Name'];
            }
        }
        return null;
    }

    private function disableAccount(AccountCheckReport $report, array $accountInfo) : void
    {
        # Disable account with multiple errors
        $q = new TQuery("select ErrorCount, ErrorDate, AuthInfo from Account where AccountID = " . $accountInfo["AccountID"]);

        if ($q->Fields['AuthInfo'] !== null) {
            // do not lockout oauth accounts
            return;
        }

        $errorCount = $q->Fields['ErrorCount'];
        $errorDays = 0;

        if (!empty($q->Fields['ErrorDate'])) {
            $errorDays = floor((time() - strtotime($q->Fields['ErrorDate'])) / SECONDS_PER_DAY);
        }

        $disableReason = null;

        if ($errorCount >= 2 && $report->errorCode == ACCOUNT_INVALID_PASSWORD) { // we should lock after 2 errors, on third
            $report->errorMessage = 'To prevent your account from being locked out by the provider please change the password or the user name you entered on AwardWallet.com as these credentials appear to be invalid.';
            $report->errorCode = ACCOUNT_PREVENT_LOCKOUT;
            $disableReason = \AwardWallet\MainBundle\Entity\Account::DISABLE_REASON_PREVENT_LOCKOUT;
        }

        if ($errorCount >= 9 && $report->errorCode == ACCOUNT_PROVIDER_ERROR && $errorDays >= 90) {
            $disableReason = \AwardWallet\MainBundle\Entity\Account::DISABLE_REASON_PROVIDER_ERROR;
        }

        if ($errorCount >= 9 && $report->errorCode == ACCOUNT_ENGINE_ERROR && $errorDays >= 180) {
            $disableReason = \AwardWallet\MainBundle\Entity\Account::DISABLE_REASON_ENGINE_ERROR;
        }

        if ($report->errorCode == ACCOUNT_LOCKOUT) {
            $disableReason = \AwardWallet\MainBundle\Entity\Account::DISABLE_REASON_LOCKOUT;
        }

        if (isset($disableReason)) {
            $logContext["DisableReason"] = $disableReason;
            $logContext["ErrorDays"] = $errorDays;
            $logContext["ErrorCount"] = $errorCount;
            $this->connection->Execute("update Account set Disabled = 1, DisableDate = now(), DisableReason = " . $disableReason . " where AccountID = {$accountInfo["AccountID"]}");
        }
    }

}

