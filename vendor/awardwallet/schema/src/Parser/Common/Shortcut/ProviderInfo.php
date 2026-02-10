<?php

namespace AwardWallet\Schema\Parser\Common\Shortcut;


use AwardWallet\Schema\Parser\Common\Itinerary;

class ProviderInfo {

	/** @var Itinerary $parent */
	protected $parent;

	public function __construct(Itinerary $parent) {
		$this->parent = $parent;
	}

	/**
	 * @param $code
	 * @return ProviderInfo
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function code($code) {
		$this->parent->setProviderCode($code);
		return $this;
	}

	/**
	 * @param $key
	 * @return ProviderInfo
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function keyword($key) {
		$this->parent->setProviderKeyword($key);
		return $this;
	}

	/**
	 * @param $awards
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return ProviderInfo
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function earnedAwards($awards, $allowEmpty = false, $allowNull = false) {
		$this->parent->setEarnedAwards($awards, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $accounts
	 * @param boolean $areMasked
	 * @return ProviderInfo
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function accounts($accounts, $areMasked) {
		$this->parent->setAccountNumbers($accounts, $areMasked);
		return $this;
	}

	/**
	 * @param $account
	 * @param boolean $isMasked
	 * @return ProviderInfo
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function account($account, $isMasked) {
		$this->parent->addAccountNumber($account, $isMasked);
		return $this;
	}

	/**
	 * @param $phone
	 * @param null $desc
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return ProviderInfo
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function phone($phone, $desc = null, $allowEmpty = false, $allowNull = true) {
		$this->parent->addProviderPhone($phone, $desc, $allowEmpty, $allowNull);
		return $this;
	}

}