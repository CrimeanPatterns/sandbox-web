<?php

abstract class AccountRegistrator {

	/** @var HttpBrowser */
	protected $http;

	/** @var  TAccountChecker */
	protected $checker;

	public function __construct($checker) {
		$this->http = $checker->http;
		$this->checker = $checker;
	}

	abstract public function register(array $fields);

	static public function getRegisterFields() {
		return [];
	}

	static public function inputFieldsMap() { return []; }

	protected function setInputFieldsValues($fields) {
		foreach ($this->inputFieldsMap() as $awKey => $provKeys) {
			if (!isset($fields[$awKey]) or $provKeys === false)
				continue;
			if (!is_array($provKeys)) $provKeys = [$provKeys];
			foreach ($provKeys as $provKey)
				$this->http->SetInputValue($provKey, $fields[$awKey]);
		}
	}

}
