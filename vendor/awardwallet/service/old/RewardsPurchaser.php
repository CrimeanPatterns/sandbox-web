<?php

abstract class RewardsPurchaser {

	/** @var HttpBrowser */
	protected $http;

	/** @var  TAccountChecker */
	protected $checker;

	public function __construct($checker) {
		$this->http = $checker->http;
		$this->checker = $checker;
	}

	abstract public function purchase(array $fields, $numberOfMiles, $creditCard);

	static public function getPurchaseRewardsFields() {
		return [];
	}

}
