<?php

class CommonCheckAccountFactory {
	
	const STRATEGY_CHECK_WSDL 	= 'WsdlCheckStrategy';
	const STRATEGY_CHECK_LOCAL	= 'LocalCheckStrategy';
	const STRATEGY_CHECK_PHANTOM = 'PhantomCheckStrategy';
	const STRATEGY_CHECK_SELENIUM = 'SeleniumCheckStrategy';

	protected static $strategyCache;

	/**
	 * Get Account auditor by Environment
	 * 
	 * @param int|array $accountId
	 * @param AuditorOptions $options
	 * @return AccountAuditorAbstract
	 */
	static public function getAccountAuditorByEnvironment($accountId, $options = null) {
		if(isset($options->checkStrategy))
			$checkStrategy = $options->checkStrategy;
		else{
			$checkStrategy = self::getStrategyByAccount($accountId);
			if(!isset($checkStrategy)) {
                $checkStrategy = self::STRATEGY_CHECK_LOCAL;
            }
		}
		return self::getAccountAuditor($accountId, $checkStrategy, $options);
	}

	/**
	 * plugin can force some strategy for specific accounts, through GetCheckStragegy
	 * @static
	 * @param $accountId
	 * @return null or one of STRATEGY_CHECK_XXX constants
	 */
	static protected function getStrategyByAccount($accountId){
		global $sPath;
		$q = new TQuery("select a.*, p.Code as ProviderCode from Account a join Provider p on a.ProviderID = p.ProviderID
		where a.AccountID = $accountId");

		if ($q->EOF) {
            return null;
        }

        $class = "TAccountChecker".ucfirst(strtolower($q->Fields['ProviderCode']));

        if (!class_exists($class)) {
            $file = "$sPath/engine/{$q->Fields['ProviderCode']}/functions.php";

            if (!file_exists($file)) {
                return null;
            }

            require_once $file;
        }

        if (!class_exists($class)) {
            $class = "TAccountChecker";
        }

		return $class::GetCheckStrategy($q->Fields);
	}
	
	static public function checkAndSave($accountId, $options = null) {
		if(!isset($options))
			$options = self::getDefaultOptions();
		$saved = false;
		if(ConfigValue(CONFIG_TRAVEL_PLANS))
			$oldHash = Lookup("Account", "AccountID", "TripsHash", $accountId);

		try {
			$auditor = self::getAccountAuditorByEnvironment($accountId, $options);
			$auditor->check();
			$report = $auditor->getReport();
			// wsdl check can return true/false instead of report, in case of async check
			// report will be already saved
			if ($report instanceof AccountCheckReport) {
				$auditor->save($auditor->getAccount(), $report, $auditor->getCheckOptions());
				$saved = $report;
			} elseif (is_bool($report)) {
				$saved = $report;
			}
		}
		catch (AccountNotFoundException $e){
			// ignore deleted accounts
		}

		return $saved;
	}
	
	/**
	 * Get Account auditor
	 * 
	 * @param int|array $accountId
	 * @param int $checkStrategy
	 * @param AuditorOptions $options
	 * @return AccountAuditorAbstract
	 */
	static public function getAccountAuditor($accountId, $checkStrategy, $options = null) {
		$auditor = new AccountAuditor(new $checkStrategy());
		$auditor->setAccount(new Account($accountId));
		if(isset($options))
			$auditor->setCheckOptions($options);
		return $auditor;
	}
	
	static public function manuallySave($accountId, AccountCheckReport $report, AuditorOptions $options) {
		$auditor = self::getAccountAuditorByEnvironment($accountId, $options);
		$auditor->save($auditor->getAccount(), $report, $auditor->getCheckOptions());
	}
	
	static function getAutologinFrame($accountID, $successUrl = null) {
		$q = new TQuery("
			SELECT a.*                   ,
			       p.Code AS ProviderCode,
			       p.State as ProviderState,
				   p.Login2Caption,
				   p.AutoLogin
			FROM   Account a
			       JOIN Provider p
			       ON     a.ProviderID = p.ProviderID
			WHERE  a.AccountID         = {$accountID}
		");
		if ($q->EOF)
			return null;
		
	 	$q->Fields["Pass"] = DecryptPassword($q->Fields["Pass"]);
	 	if ((function_exists('LoadCookiePassword') && !LoadCookiePassword($q->Fields, false)) || ($q->Fields["ProviderState"] < PROVIDER_ENABLED) || !in_array($q->Fields['AutoLogin'], array(AUTOLOGIN_DISABLED, AUTOLOGIN_SERVER, AUTOLOGIN_MIXED, AUTOLOGIN_EXTENSION)))
	 		return null;
	 	
		$autologin = new AccountAutologin();
		$targetType = self::detectPlatform();
		$startUrl = null;
		return $autologin->getAutologinFrame($q->Fields['ProviderCode'], $q->Fields['Login'], $q->Fields['Login2'], $q->Fields['Login3'], $q->Fields['Pass'], $successUrl, $q->Fields['UserID'], $targetType, $startUrl);
	}

	/**
	 * @returns string
	 */
	public static function detectPlatform(){
		$result = null;
		$userAgent = ArrayVal($_SERVER, 'HTTP_USER_AGENT');
		if(preg_match("/mobile.+safari/i", $userAgent))
			$result = "mobile/ios";
		if(preg_match("/android/i", $userAgent))
			$result = "mobile/android";
		return $result;
	}
	
	/**
	 * Get default options
	 * 
	 * @return AuditorOptions
	 */
	static public function getDefaultOptions() {
		return new AuditorOptions();
	}
	
}

