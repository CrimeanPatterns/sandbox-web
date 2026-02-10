<?php

namespace AwardWallet\Schema\Parser\Common\Shortcut;


use AwardWallet\Schema\Parser\Common\Rental;

class Car {

	/** @var Rental $parent */
	protected $parent;

	public function __construct(Rental $parent) {
		$this->parent = $parent;
	}

	/**
	 * @param $carType
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Car
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function type($carType, $allowEmpty = false, $allowNull = false) {
		$this->parent->setCarType($carType, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $carModel
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Car
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function model($carModel, $allowEmpty = false, $allowNull = false) {
		$this->parent->setCarModel($carModel, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $carImageUrl
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Car
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function image($carImageUrl, $allowEmpty = false, $allowNull = false) {
		$this->parent->setCarImageUrl($carImageUrl, $allowEmpty, $allowNull);
		return $this;
	}

}