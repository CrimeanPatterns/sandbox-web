<?php

require_once __DIR__."/AccountHandlerInterface.php";

abstract class AccountHandlerAbstract implements AccountHandlerInterface {
	
	public function setAccount(AccountInterface $account) {
		throw new \RuntimeException('The method "setAccount" should be overridden');
	}
	
	public function getAccount() {
		throw new \RuntimeException('The method "getAccount" should be overridden');
	}

}

?>