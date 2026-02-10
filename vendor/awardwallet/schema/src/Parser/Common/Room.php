<?php

namespace AwardWallet\Schema\Parser\Common;


use AwardWallet\Schema\Parser\Component\Base;

class Room extends Base {

	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=medium
	 */
	protected $type;
	/**
	 * @parsed Field
	 * @attr type=soft
	 * @attr length=long
	 */
	protected $description;
    /**
     * @parsed Field
     * @attr type=basic
     * @attr maxlength=400
     */
    protected $rate;
    /**
     * @parsed Arr
     * @attr item=Field
     * @attr item_type=basic
     * @attr item_length=medium
     */
    protected $rates;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=long
	 */
	protected $rateType;
	/**
	 * @parsed Field
	 * @attr type=clean
	 * @attr length=short
	 */
	protected $confirmation;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=long
	 */
	protected $confirmationDescription;

	/**
	 * @return mixed
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @param mixed $type
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Room
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setType($type, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($type, 'type', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @param mixed $description
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Room
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setDescription($description, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($description, 'description', $allowEmpty, $allowNull);
		return $this;
	}

    /**
     * @return mixed
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * @return mixed
     */
    public function getRates()
    {
        return $this->rates;
    }

	/**
	 * @param $rate
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Room
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setRate($rate, $allowEmpty = false, $allowNull = false) {
        $this->setProperty($rate, 'rate', $allowEmpty, $allowNull);
		return $this;
	}

    /**
     * @param $rate
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return Room
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function addRate($rate, $allowEmpty = false, $allowNull = false) {
        $this->addItem($rate, 'rates', $allowEmpty, $allowNull);
        return $this;
    }

    /**
	 * @param mixed $rates
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Room
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setRates($rates) {
		$this->setProperty($rates, 'rates', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getRateType() {
		return $this->rateType;
	}

	/**
	 * @param mixed $rateType
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Room
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setRateType($rateType, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($rateType, 'rateType', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getConfirmation() {
		return $this->confirmation;
	}

	/**
	 * @param mixed $confirmation
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Room
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setConfirmation($confirmation, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($confirmation, 'confirmation', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getConfirmationDescription() {
		return $this->confirmationDescription;
	}

	/**
	 * @param mixed $confirmationDescription
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Room
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setConfirmationDescription($confirmationDescription, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($confirmationDescription, 'confirmationDescription', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return Base[]
	 */
	protected function getChildren() {
		return [];
	}

	public function checkEmpty() {
	    $empty = true;
	    foreach($this->_fields as $name => $field)
	        $empty = empty($this->$name) && $empty;
	    if ($empty)
	        $this->invalid('empty room');
	    return $this->valid;
	}


}
