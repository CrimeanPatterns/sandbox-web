<?php

namespace AwardWallet\Schema\Parser\Email;


use AwardWallet\Schema\Parser\Common\Price;
use AwardWallet\Schema\Parser\Common\TravelAgency;
use AwardWallet\Schema\Parser\Component\Base;
use AwardWallet\Schema\Parser\Component\HasPrice;
use AwardWallet\Schema\Parser\Component\HasTravelAgency;
use AwardWallet\Schema\Parser\Component\Master;
use AwardWallet\Schema\Parser\Common\Shortcut\Price as PriceShortcut;
use AwardWallet\Schema\Parser\Common\Shortcut\TravelAgency as TAShortcut;
use AwardWallet\Schema\Parser\Component\Options;

class Email extends Master implements HasTravelAgency, HasPrice {

	/** @var Price $price */
	protected $price;

	/** @var TravelAgency $travelAgency */
	protected $travelAgency;

	/**
	 * @parsed Field
	 * @attr type=provider
	 */
	protected $providerCode;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=medium
	 */
	protected $providerKeyword;
	/**
	 * @parsed Field
	 * @attr regexp=/^[^@\s]+@[\w.\-]+\.\w+$/
	 * @attr length=medium
	 */
	protected $userEmail;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=short
	 */
	protected $type;
    /**
     * @parsed Boolean
     */
    protected $isJunk;

    /**
     * @parsed Field
     * @attr length=extra
     */
    protected $rawSource;

    /**
     * @parsed Boolean
     */
    protected $sentToVendor;

	/** @var PriceShortcut $_price */
	protected $_price;

	/** @var  TAShortcut $_ota */
	protected $_ota;

	public function __construct($name, Options $options = null) {
		parent::__construct($name, $options);
		$this->_price = new PriceShortcut($this);
		$this->_ota = new TAShortcut($this);
	}

	/**
	 * @return PriceShortcut
	 */
	public function price() {
		return $this->_price;
	}

	/**
	 * @return TAShortcut
	 */
	public function ota() {
		return $this->_ota;
	}

	/**
	 * @return Price
	 */
	public function obtainPrice() {
		if (!isset($this->price))
			$this->price = new Price($this->_name.'-price', $this->logger, $this->_options);
		return $this->price;
	}

	/**
	 * @return Price
	 */
	public function getPrice() {
		return $this->price;
	}

	/**
	 * @return $this
	 */
	public function removePrice() {
		$this->price = null;
		return $this;
	}

	/**
	 * @return TravelAgency
	 */
	public function obtainTravelAgency() {
		if (!isset($this->travelAgency))
			$this->travelAgency = new TravelAgency($this->_name.'-ota', $this->logger, $this->_options);
		return $this->travelAgency;
	}

	/**
	 * @return TravelAgency
	 */
	public function getTravelAgency() {
		return $this->travelAgency;
	}

	/**
	 * @return $this
	 */
	public function removeTravelAgency() {
        $this->travelAgency = null;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getProviderKeyword() {
		return $this->providerKeyword;
	}

	/**
	 * @param mixed $providerKeyword
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Email
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setProviderKeyword($providerKeyword, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($providerKeyword, 'providerKeyword', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getProviderCode() {
		return $this->providerCode;
	}

	/**
	 * @param mixed $providerCode
	 * @return Email
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setProviderCode($providerCode) {
		$this->setProperty($providerCode, 'providerCode', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getUserEmail() {
		return $this->userEmail;
	}

	/**
	 * @param mixed $userEmail
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Email
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setUserEmail($userEmail, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($userEmail, 'userEmail', $allowEmpty, $allowNull);
		return $this;
	}

    /**
     * @return bool
     */
    public function getSentToVendor()
    {
        return $this->sentToVendor;
    }

    /**
     * @param bool $sentToVendor
     * @return Email
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setSentToVendor($sentToVendor)
    {
        $this->setProperty($sentToVendor, 'sentToVendor', false, false);
        return $this;
    }

	/**
	 * @return mixed
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @param mixed $type
	 * @return Email
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setType($type) {
		$this->setProperty($type, 'type', false, false);
		return $this;
	}

    public function getRawSource()
    {
        return $this->rawSource;
    }

    public function setRawSource($rawSource)
    {
        $this->rawSource = $rawSource;
    }

	protected function hasConfNo()
    {
        return isset($this->travelAgency) && count($this->travelAgency->getConfirmationNumbers()) > 0;
    }

    public function validate()
    {
        parent::validate();
        if ($this->travelAgency)
            $this->valid = $this->travelAgency->validate() && $this->valid;
    }

    /**
	 * @return Base[]
	 */
	protected function getChildren() {
		$r = parent::getChildren();
		if (isset($this->travelAgency))
			$r[] = $this->travelAgency;
		if (isset($this->price))
			$r[] = $this->price;
		return $r;
	}

}