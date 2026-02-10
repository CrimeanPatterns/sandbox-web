<?php

namespace AwardWallet\Schema\Parser\Common\Shortcut;


use AwardWallet\Schema\Parser\Component\HasDetailedAddress;

class DetailedAddress {

	/** @var  HasDetailedAddress $parent */
	protected $parent;

	protected $option;

	public function __construct(HasDetailedAddress $parent, $option) {
		$this->parent = $parent;
		$this->option = $option;
	}

	/**
	 * @return \AwardWallet\Schema\Parser\Common\DetailedAddress
	 */
	protected function da() {
		return $this->parent->obtainDetailedAddress($this->option);
	}

	/**
	 * @param $s
	 * @return $this
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function address($s) {
		$this->da()->setAddressLine($s);
		return $this;
	}

	/**
	 * @param $s
	 * @return $this
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function city($s) {
		$this->da()->setCity($s);
		return $this;
	}

	/**
	 * @param $s
	 * @return $this
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function state($s) {
		$this->da()->setState($s);
		return $this;
	}

	/**
	 * @param $s
	 * @return $this
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function country($s) {
		$this->da()->setCountry($s);
		return $this;
	}

	/**
	 * @param $s
	 * @return $this
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function zip($s) {
		$this->da()->setZip($s);
		return $this;
	}

}