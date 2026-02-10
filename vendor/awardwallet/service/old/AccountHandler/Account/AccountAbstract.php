<?php

require_once __DIR__."/AccountInterface.php";

abstract class AccountAbstract implements AccountInterface {
	
	/**
	 * @var int $accountId
	 */
	protected $accountId = null;
	
	/**
	 * @var mixed $accountInfo
	 */
	protected $accountInfo = null;
	
	/**
	 * Set accountID
	 * 
	 * @param int $accountId
	 * @return void
	 */
	public function setAccountId($accountId) {
		$this->accountId = $accountId;
	}
	
	/**
	 * Get accountID
	 * 
	 * @return int
	 */
	public function getAccountId() {
		return $this->accountId;
	}
	
	/**
	 * Set account info
	 * 
	 * @param mixed $accountInfo
	 * @return void
	 */
	public function setAccountInfo($accountInfo) {
		$this->accountInfo = $accountInfo;
	}
	
	/**
	 * Get account info
	 * 
	 * @return mixed
	 */
	public function getAccountInfo() {
		return $this->accountInfo;
	}
	
}

?>