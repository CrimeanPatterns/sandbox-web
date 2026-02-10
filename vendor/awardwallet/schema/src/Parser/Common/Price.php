<?php

namespace AwardWallet\Schema\Parser\Common;


use AwardWallet\Schema\Parser\Component\Base;
use AwardWallet\Schema\Parser\Component\Options;
use Psr\Log\LoggerInterface;

class Price extends Base {

	/**
	 * @parsed Field
	 * @attr type=price
	 */
	protected $total;
	/**
	 * @parsed Field
	 * @attr type=price
	 */
	protected $cost;
	/**
	 * @parsed Field
	 * @attr type=price
	 */
	protected $discount;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=short
	 */
	protected $spentAwards;
	/**
	 * @parsed Field
	 * @attr regexp=/^[A-Z]{2,5}$/
	 */
	protected $currencyCode;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * #attr length=short
	 */
	protected $currencySign;
	/**
	 * @parsed KeyValue
	 * @attr unique=false
	 * @attr key=Field
	 * @attr key_type=basic
	 * @attr key_length=medium
	 * @attr val=Field
	 * @attr val_type=price
	 */
	protected $fees;

	public function __construct($name, LoggerInterface $logger, Options $options = null) {
		parent::__construct($name, $logger, $options);
		$this->fees = [];
	}

	/**
	 * @return mixed
	 */
	public function getTotal() {
		return $this->total;
	}

	/**
	 * @param mixed $total
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Price
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setTotal($total, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($total, 'total', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getCost() {
		return $this->cost;
	}

	/**
	 * @param mixed $cost
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Price
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setCost($cost, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($cost, 'cost', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param mixed $tax
     * @param bool $allowEmpty
     * @param bool $allowNull
	 * @return Price
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setTax($tax, $allowEmpty = false, $allowNull = false) {
	    if (is_null($tax) && !$allowNull || is_scalar($tax) && strlen((string)$tax) == 0 && !$allowEmpty)
	        $this->invalid('empty tax: ' . $this->str($tax));
	    if (is_scalar($tax) && strlen((string)$tax) > 0)
		    $this->addFee('Tax', $tax);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getDiscount() {
		return $this->discount;
	}

	/**
	 * @param mixed $discount
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Price
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setDiscount($discount, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($discount, 'discount', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getSpentAwards() {
		return $this->spentAwards;
	}

	/**
	 * @param mixed $spentAwards
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Price
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setSpentAwards($spentAwards, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($spentAwards, 'spentAwards', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getCurrencyCode() {
		return $this->currencyCode;
	}

	/**
	 * @param mixed $currencyCode
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Price
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setCurrencyCode($currencyCode, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($currencyCode, 'currencyCode', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getCurrencySign() {
		return $this->currencySign;
	}

	/**
	 * @param mixed $currencySign
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Price
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setCurrencySign($currencySign, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($currencySign, 'currencySign', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return array
	 */
	public function getFees() {
		return $this->fees;
	}

	/**
	 * @param $name
	 * @param $charge
	 * @return Price
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function addFee($name, $charge) {
		$this->addKeyValue($name, $charge, 'fees', false, false, []);
		return $this;
	}

	/**
	 * @param $name
	 * @return Price
	 */
	public function removeFee($name) {
		$this->removeItem($name, 'fees');
		return $this;
	}

	/**
	 * @return Base[]
	 */
	protected function getChildren() {
		return [];
	}

}