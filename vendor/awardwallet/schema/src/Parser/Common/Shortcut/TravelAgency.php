<?php

namespace AwardWallet\Schema\Parser\Common\Shortcut;

use AwardWallet\Schema\Parser\Component\HasTravelAgency;

class TravelAgency {

	/** @var HasTravelAgency $parent */
	protected $parent;
	
	public function __construct(HasTravelAgency $parent) {
		$this->parent = $parent;
	}

	/**
	 * @return \AwardWallet\Schema\Parser\Common\TravelAgency
	 */
	protected function ota() {
		return $this->parent->obtainTravelAgency();
	}

	/**
	 * @param $confirmation
	 * @param null $description
	 * @param bool $isPrimary
	 * @return TravelAgency
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function confirmation($confirmation, $description = null, $isPrimary = null) {
		$this->ota()->addConfirmationNumber($confirmation, $description, $isPrimary);
		return $this;
	}

	/**
	 * @param $code
	 * @return TravelAgency
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function code($code) {
		$this->ota()->setProviderCode($code);
		return $this;
	}

	/**
	 * @param $key
	 * @return TravelAgency
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function keyword($key) {
		$this->ota()->setProviderKeyword($key);
		return $this;
	}

	/**
	 * @param $awards
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TravelAgency
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function earnedAwards($awards, $allowEmpty = false, $allowNull = false) {
		$this->ota()->setEarnedAwards($awards, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $accounts
	 * @param boolean $areMasked
	 * @return TravelAgency
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function accounts($accounts, $areMasked) {
		$this->ota()->setAccountNumbers($accounts, $areMasked);
		return $this;
	}

	/**
	 * @param $account
	 * @param boolean $isMasked
	 * @return TravelAgency
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function account($account, $isMasked) {
		$this->ota()->addAccountNumber($account, $isMasked);
		return $this;
	}

	/**
	 * @param $phone
	 * @param null $desc
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TravelAgency
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function phone($phone, $desc = null) {
		$this->ota()->addProviderPhone($phone, $desc);
		return $this;
	}
	
	

}