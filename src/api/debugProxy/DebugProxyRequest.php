<?php

class DebugProxyRequest {

	public $AccountID;
	public $SerializedRequest;

	public function __construct($accountId, $serializedRequest){
		$this->AccountID = $accountId;
		$this->SerializedRequest = $serializedRequest;
	}

}
