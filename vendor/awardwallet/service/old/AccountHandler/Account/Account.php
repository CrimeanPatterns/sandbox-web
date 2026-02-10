<?php

require_once __DIR__."/AccountAbstract.php";
require_once __DIR__."/../AccountException.php";

class Account extends AccountAbstract {
	
	/**
	 * @var object $connection DB Connection
	 */
	public $connection;
	
	public function __construct($accountId = null, $connection = null) {
		global $Connection;
		if (isset($connection))
			$this->connection = $connection;
		else
			$this->connection = $Connection;
		if (isset($accountId))
			$this->setAccountId($accountId);
	}
	
	public function getAccountInfo($loadPassword = true) {
		if (!isset($this->accountInfo)) {
			$accountId = $this->getAccountId();
			$q = new TQuery("
				SELECT a.*                     ,
					   p.Engine AS ProviderEngine,
				       p.Code  AS ProviderCode ,
				       p.State AS ProviderState,
				       p.CanCheck,
				       p.CanCheckItinerary,
				       p.CheckInBrowser,
				       p.Login2Caption         ,
				       p.AllowFloat            ,
				       p.Kind      AS ProviderKind  ,
				       p.Questions AS ProviderQuestions,
				       p.DisplayName		   ,
				       p.Name                  ,
					   p.BalanceFormat,
					   p.ExpirationAlwaysKnown,
					   p.CacheVersion AS ProviderCacheVersion,
					   p.ProviderGroup,
					   p.RequestsPerMinute,
					   ap.Val as Status
				FROM   Account a
				JOIN Provider p on a.ProviderID = p.ProviderID
				LEFT JOIN ProviderProperty pp on pp.ProviderID = p.ProviderID and pp.Kind = 3
                LEFT JOIN AccountProperty ap on ap.ProviderPropertyID = pp.ProviderPropertyID AND ap.AccountID = a.AccountID
				WHERE a.AccountID = $accountId
			", $this->connection);
			if ($q->EOF)
				throw new \InvalidArgumentException("Account $accountId not found");

			$this->setAccountInfo($q->Fields);
		}
		if ($loadPassword &&  $this->accountInfo["Pass"] == '' && function_exists('LoadCookiePassword') && !LoadCookiePassword($this->accountInfo))
			throw new \AccountException('No local password', AccountException::NO_LOCAL_PASSWORD);
		return parent::getAccountInfo();
	}

}

?>