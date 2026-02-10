<?php

namespace AwardWallet\Schema\Parser\Common\Shortcut;


use AwardWallet\Schema\Parser\Common\Cruise;

class CruiseDetails {

	/**
	 * @var Cruise
	 */
	protected $parent;

	public function __construct(Cruise $parent) {
		$this->parent = $parent;
	}

	/**
	 * @param $description
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return CruiseDetails
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function description($description, $allowEmpty = false, $allowNull = false) {
		$this->parent->setDescription($description, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $class
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return CruiseDetails
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function roomClass($class, $allowEmpty = false, $allowNull = false) {
		$this->parent->setClass($class, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $deck
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return CruiseDetails
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function deck($deck, $allowEmpty = false, $allowNull = false) {
		$this->parent->setDeck($deck, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $room
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return CruiseDetails
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function room($room, $allowEmpty = false, $allowNull = false) {
		$this->parent->setRoom($room, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $ship
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return CruiseDetails
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function ship($ship, $allowEmpty = false, $allowNull = false) {
		$this->parent->setShip($ship, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $code
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return CruiseDetails
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function shipCode($code, $allowEmpty = false, $allowNull = false) {
		$this->parent->setShipCode($code, $allowEmpty, $allowNull);
		return $this;
	}

    /**
     * @param $number
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return CruiseDetails
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
	public function number($number, $allowEmpty = false, $allowNull = false)
    {
        $this->parent->setVoyageNumber($number, $allowEmpty, $allowNull);
        return $this;
    }

}