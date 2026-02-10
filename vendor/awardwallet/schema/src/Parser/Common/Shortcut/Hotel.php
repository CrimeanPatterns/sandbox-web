<?php

namespace AwardWallet\Schema\Parser\Common\Shortcut;


class Hotel {

	/** @var \AwardWallet\Schema\Parser\Common\Hotel $parent */
	protected $parent;

	/** @var  DetailedAddress $da */
	protected $da;

	public function __construct(\AwardWallet\Schema\Parser\Common\Hotel $hotel, DetailedAddress $da) {
		$this->parent = $hotel;
		$this->da = $da;
	}

	/**
	 * @param $name
	 * @return Hotel
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function name($name) {
		$this->parent->setHotelName($name);
		return $this;
	}

	/**
	 * @param $chain
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Hotel
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function chain($chain, $allowEmpty = false, $allowNull = false) {
		$this->parent->setChainName($chain, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $address
	 * @return Hotel
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function address($address) {
		$this->parent->setAddress($address);
		return $this;
	}

	/**
	 * @return Hotel
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function noAddress() {
		$this->parent->setNoAddress(true);
		return $this;
	}

	/**
	 * @param $phone
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Hotel
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function phone($phone, $allowEmpty = false, $allowNull = false) {
		$this->parent->setPhone($phone, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $fax
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Hotel
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function fax($fax, $allowEmpty = false, $allowNull = false) {
		$this->parent->setFax($fax, $allowEmpty, $allowNull);
		return $this;
	}

    /**
     * @return Hotel
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
	public function house()
    {
        $this->parent->setHouse(true);
        return $this;
    }

	/**
	 * @return DetailedAddress
	 */
	public function detailed() {
		return $this->da;
	}

}