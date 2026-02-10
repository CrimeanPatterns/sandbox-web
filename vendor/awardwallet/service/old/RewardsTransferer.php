<?php

abstract class RewardsTransferer {

	/** @var HttpBrowser */
	protected $http;

	/** @var TAccountChecker */
	protected $checker;

	// For testing purposes. If true, do not do last submit
	public $idle = false;

	public $lastError = null;

	public function __construct($checker) {
		$this->http = $checker->http;
		$this->checker = $checker;
	}

	abstract public function transfer($targetProviderCode, $targetAccountNumber, $sourceRewardsQuantity);

}