<?php

require_once __DIR__."/AccountAuditorCheckStrategyInterface.php";
require_once __DIR__."/../AccountCheckReport.php";

use AwardWallet\Common\PasswordCrypt\PasswordDecryptor;

class LocalCheckStrategy implements AccountAuditorCheckStrategyInterface {
	
	public $connection;
	
	public function __construct ($connection = null) {
		global $Connection;
		if (isset($connection))
			$this->connection = $connection;
		else
			$this->connection = $Connection;
	}
	
	public function check(Account $account, AuditorOptions $options) {
		global $sPath;
        global $arAccountErrorCode;

		$_SESSION['DownloadedTraffic'] = 0;
        \AwardWallet\Engine\Settings::setAwUrl(getSymfonyContainer()->getParameter('requires_channel') . '://' . getSymfonyContainer()->getParameter("host"));
        \StatLogger::setLogger(getSymfonyContainer()->get("logger"));

		# Account info
		$accountInfo = $account->getAccountInfo();
		$accountInfo['Pass'] = getSymfonyContainer()->get(PasswordDecryptor::class)->decrypt($accountInfo['Pass']);
		# Report
		$report = new AccountCheckReport();
        $report->source = $options->source;
		$report->properties['ParseIts'] = $options->checkIts;
		
		if (($accountInfo["Login2Caption"] != "") || (isset($accountInfo["Region"]) && ($accountInfo["Region"] != ""))){
			$login = array($accountInfo["Login"], $accountInfo["Login2"]);
			if (isset($accountInfo["Region"]))
				$login[] = $accountInfo["Region"];
		} else
			$login = $accountInfo["Login"];
		# detect api interface
        if (!empty($options->transferFields))
            $checker = GetTransferChecker($accountInfo['ProviderCode'], $options->transferFields[0], true, $accountInfo);
        else
            $checker = GetAccountChecker($accountInfo['ProviderCode'], false, $accountInfo);
        $checker->SetAccount($accountInfo);
        $doctrineConnection = getSymfonyContainer()->get('database_connection');
        $checker->db = new DatabaseHelper($doctrineConnection);
        $checker->onBrowserReady = $options->onBrowserReady;
        $checker->ParseIts = $options->checkIts;
        $checker->WantHistory = $options->checkHistory;
        $checker->HistoryStartDate = $options->historyStartDate;
        $checker->WantFiles = $options->checkFiles;
        $checker->FilesStartDate = $options->filesStartDate;
        $checker->KeepLogs = $options->saveLog;
        $checker->TransferMilesRequest = $options->transferMiles;
        if (!empty($options->transferFields)) {
            $checker->TransferMethod = $options->transferFields[0];
            unset($options->transferFields[0]);
            $checker->TransferFields = $options->transferFields;
            if ($checker->TransferMethod == 'register')
                $fields = $checker->getRegisterFields();
            elseif ($checker->TransferMethod == 'purchase')
                $fields = $checker->getPurchaseMilesFields();
            if (isset($fields) && !empty($checker->AccountFields['Pass']))
                foreach(['Pass', 'Password'] as $passKey)
                    if (isset($fields[$passKey])) {
                        $checker->TransferFields[$passKey] = $checker->AccountFields['Pass'];
                        break;
                    }
            if ('purchase' === $checker->TransferMethod) {
                foreach(['Login', 'Login2', 'Login3'] as $k)
                    if (isset($checker->TransferFields[$k]))
                        $checker->AccountFields[$k] = $checker->TransferFields[$k];
            }
        }

        if (ConfigValue(CONFIG_TRAVEL_PLANS) && class_exists("\AwardWallet\Schema\Parser\Component\Master")) {
            $masterOptions = new \AwardWallet\Schema\Parser\Component\Options();
            $masterOptions->throwOnInvalid = false;
            $masterOptions->logDebug = true;
            $masterOptions->logContext['class'] = get_class($checker);
            $checker->itinerariesMaster = new \AwardWallet\Schema\Parser\Component\Master(
                'itineraries',
                $masterOptions
            );
        }

        $checkAttemptsCount = 1;
        for ($attempt = 1; $attempt <= $checkAttemptsCount && $attempt <= 7; $attempt++) {
            try {
                $checker->attempt = $attempt - 1;
                $checker->InitBrowser();
                $checker->Check(false);
                break;
            } catch (CheckRetryNeededException $e) {
                $checkAttemptsCount = $e->checkAttemptsCount;
                $retryTimeout = $e->retryTimeout;
                // reset properties during retries
                $checker->Properties = [];

                $checker->logger->notice("[Attempt {$checker->attempt}]: Checker signalized that retry is needed from {$e->getFile()}:{$e->getLine()}");

                if ($attempt <= $checkAttemptsCount - 1) {
                    $msg = "$attempt/{$checkAttemptsCount} attempt failed, sleeping "
                        ."{$retryTimeout} seconds and trying again";
                    $checker->logger->notice($msg);
                    sleep($retryTimeout);
                    // close Selenium browser
                    if ($checker->http instanceof HttpBrowser)
                        $checker->http->cleanup();
                } else {
                    $msg = "Max attempts count ({$checkAttemptsCount}) exceeded (with interval {$retryTimeout}), no more retries";
                    $checker->logger->notice($msg);
                    if ($e->errorMessageWhenAttemptsExceeded !== null) {
                        $checker->ErrorMessage = $e->errorMessageWhenAttemptsExceeded;
                        $checker->ErrorCode = $e->errorCodeWhenAttemptsExceeded;
                        $checker->logger->error("error: " . $checker->ErrorMessage . " [" . $arAccountErrorCode[$checker->ErrorCode] . "]");
                    }
                }
                $checker->http->LogSplitter();
                continue;
            }
        }
        if ($checker->ErrorCode != ACCOUNT_CHECKED and !$checker->DebugInfo)
            $checker->DebugInfo = $checker->http->Error;

        if ($checker->Cancelled)
            throw new \AccountException('Cancelled');
        $report->checker = $checker;
        $report->isTransfer = !empty($options->transferFields);
        $report->warnings = $checker->ArchiveLogs;
        $report->errorCode = $checker->ErrorCode;
        $report->errorMessage = $checker->ErrorMessage;
        $report->errorReason = $checker->ErrorReason;
        $report->debugInfo = $checker->DebugInfo;
        $report->question = $checker->Question;
        $report->properties = $checker->Properties;
        $report->logPath = $checker->http->LogDir;
        if (is_array($checker->Itineraries) && count($checker->Itineraries) > 0){
            foreach($checker->Itineraries as $key => $val){
                if(isset($val['Kind'])){
                    if($val['Kind'] == 'T'){
                        unset($val['Kind']);
                        $report->properties['Itineraries'][] = $val;
                    }
                    else if($val['Kind'] == 'L'){
                        unset($val['Kind']);
                        $report->properties['Rentals'][] = $val;
                    }
                    else if($val['Kind'] == 'R'){
                        unset($val['Kind']);
                        $report->properties['Reservations'][] = $val;
                    }
                    else if($val['Kind'] == 'E'){
                        unset($val['Kind']);
                        $report->properties['Restaurants'][] = $val;
                    }
                }
                else{
                    switch($accountInfo['ProviderKind']){
                        case PROVIDER_KIND_AIRLINE:
                            $report->properties['Itineraries'][$key] = $val;
                            break;
                        case PROVIDER_KIND_CAR_RENTAL:
                            $report->properties['Rentals'][$key] = $val;
                            break;
                        case PROVIDER_KIND_HOTEL:
                            $report->properties['Reservations'][$key] = $val;
                            break;
                        //case PROVIDER_KIND_OTHER:
                        case PROVIDER_KIND_DINING:
                            $report->properties['Restaurants'][$key] = $val;
                            break;
                        default:
                            if (isset($val['NoItineraries']) && $val['NoItineraries']){
                                $report->properties['Itineraries'][$key] = $val;
                                $report->properties['Rentals'][$key] = $val;
                                $report->properties['Reservations'][$key] = $val;
                                $report->properties['Restaurants'][$key] = $val;
                            } else {
    //										DieTrace("Itineraries for this provider kind not supported", false, 0, var_export($val, true));
                            }
                    }
                }
            }
        }
        $report->properties['HistoryRows'] 	= $checker->History;
        $report->properties['HistoryColumns'] = $checker->GetHistoryColumns();
        if($options->checkFiles)
            $report->files = $checker->Files;
        $report->balance = $checker->Balance;
        $report->browserState = $checker->GetState();
        $report->invalidAnswers = $checker->InvalidAnswers;
        if (!empty($options->onComplete))
            call_user_func($options->onComplete, $checker);
        if (!ConfigValue(CONFIG_TRAVEL_PLANS)) {
            $checker->Cleanup();
        }

		$report->traffic = intval($_SESSION['DownloadedTraffic']);
		unset($report->properties['TrafficDownloaded']);

		return $report;
	}
}

?>
