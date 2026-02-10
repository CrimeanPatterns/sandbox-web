<?php

class DebugProxyResponse {

	public $SerializedResponse;

	public function __construct($serializedResponse){
		$this->SerializedResponse = $serializedResponse;
	}

} 