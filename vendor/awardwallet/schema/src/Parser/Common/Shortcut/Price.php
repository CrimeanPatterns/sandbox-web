<?php

namespace AwardWallet\Schema\Parser\Common\Shortcut;


use AwardWallet\Schema\Parser\Component\HasPrice;

class Price {

	/** @var  HasPrice $parent */
	protected $parent;

	public function __construct(HasPrice $parent) {
		$this->parent = $parent;
	}

	/**
	 * @return \AwardWallet\Schema\Parser\Common\Price
	 */
	protected function price() {
		return $this->parent->obtainPrice();
	}

	/**
	 * @param $total
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Price
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function total($total, $allowEmpty = false, $allowNull = false) {
		$this->price()->setTotal($total, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $cost
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Price
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function cost($cost, $allowEmpty = false, $allowNull = false) {
		$this->price()->setCost($cost, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $tax
     * @param bool $allowEmpty
     * @param bool $allowNull
	 * @return Price
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function tax($tax, $allowEmpty = false, $allowNull = false) {
		$this->price()->setTax($tax, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $discount
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Price
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function discount($discount, $allowEmpty = false, $allowNull = false) {
		$this->price()->setDiscount($discount, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $spentAwards
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Price
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function spentAwards($spentAwards, $allowEmpty = false, $allowNull = false) {
		$this->price()->setSpentAwards($spentAwards, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $currencyCode
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Price
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function currency($currencyCode, $allowEmpty = false, $allowNull = false) {
	    if (!empty($currencyCode) && preg_match('/^[A-Z]{3}$/', $currencyCode) > 0)
		    $this->price()->setCurrencyCode($currencyCode, $allowEmpty, $allowNull);
	    else
	        $this->price()->setCurrencySign($currencyCode, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $name
	 * @param $charge
	 * @return Price
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function fee($name, $charge) {
		$this->price()->addFee($name, $charge);
		return $this;
	}

}