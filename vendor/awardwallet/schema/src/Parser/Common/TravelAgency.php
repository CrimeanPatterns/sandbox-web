<?php

namespace AwardWallet\Schema\Parser\Common;


use AwardWallet\Schema\Parser\Component\Base;
use AwardWallet\Schema\Parser\Component\Options;
use Psr\Log\LoggerInterface;

class TravelAgency extends Base {

	/**
	 * @parsed KeyValue
	 * @attr unique=strict
	 * @attr key=Field
	 * @attr key_type=confno
	 * @attr key_length=short
	 * @attr key_minlength=3
	 * @attr val=Field
	 * @attr val_type=basic
	 * @attr val_length=medium
	 */
	protected $confirmationNumbers;
	/**
	 * @parsed Boolean
	 */
	protected $primaryConfirmationKey;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=medium
	 */
	protected $providerKeyword;
	/**
	 * @parsed Field
	 * @attr type=provider
	 */
	protected $providerCode;
	/**
	 * @parsed KeyValue
	 * @attr unique=true
	 * @attr key=Field
	 * @attr key_type=phone
	 * @attr val=Field
	 * @attr val_type=basic
	 * @attr val_length=medium
	 */
	protected $providerPhones;
	/**
	 * @parsed KeyValue
	 * @attr unique=true
	 * @attr key=Field
	 * @attr key_type=clean
	 * @attr key_length=short
	 * @attr val=Boolean
	 */
	protected $accountNumbers;
	/**
	 * @parsed Boolean
	 */
	protected $areAccountMasked;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=medium
	 */
	protected $earnedAwards;

	public function __construct($name, LoggerInterface $logger, Options $options = null) {
		parent::__construct($name, $logger, $options);
		$this->providerPhones = [];
		$this->confirmationNumbers = [];
		$this->accountNumbers = [];
	}

	/**
	 * @return mixed
	 */
	public function getProviderKeyword() {
		return $this->providerKeyword;
	}

	/**
	 * @param mixed $providerKeyword
	 * @return TravelAgency
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setProviderKeyword($providerKeyword) {
		$this->setProperty($providerKeyword, 'providerKeyword', false, false);
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
	 * @return TravelAgency
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setProviderCode($providerCode) {
		$this->setProperty($providerCode, 'providerCode', false, false);
		return $this;
	}

	/**
	 * @return array
	 */
	public function getProviderPhones() {
		return $this->providerPhones;
	}

	/**
	 * @param $phone
	 * @param $desc
	 * @return TravelAgency
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function addProviderPhone($phone, $desc = null) {
		$this->addKeyValue($phone, $desc, 'providerPhones', false, true, []);
		return $this;
	}

	/**
	 * @param $phone
	 * @return TravelAgency
	 */
	public function removeProviderPhone($phone) {
		$this->removeItem($phone, 'providerPhones');
		return $this;
	}

	/**
	 * @return array
	 */
	public function getAccountNumbers() {
		return $this->accountNumbers;
	}

	/**
	 * @param array $accountNumbers
	 * @param boolean $areMasked
	 * @return TravelAgency
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setAccountNumbers($accountNumbers, $areMasked) {
		if (!is_array($accountNumbers))
			$this->invalid('setAccountNumbers expecting array');
		else
			foreach($accountNumbers as $num)
				$this->addAccountNumber($num, $areMasked);
		return $this;
	}

	/**
	 * @param $number
	 * @param boolean $isMasked
	 * @return TravelAgency
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function addAccountNumber($number, $isMasked) {
		$this->addKeyValue($number, $isMasked, 'accountNumbers', false, false, []);
		return $this;
	}

	/**
	 * @param string $number
	 * @return TravelAgency
	 */
	public function removeAccountNumber($number) {
		$this->removeItem($number, 'accountNumbers');
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getAreAccountMasked() {
		return $this->areAccountMasked;
	}

	/**
	 * @param boolean $areAccountMasked
	 * @return TravelAgency
	 */
	public function setAreAccountMasked($areAccountMasked) {
		$this->areAccountMasked = $areAccountMasked;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getEarnedAwards() {
		return $this->earnedAwards;
	}

	/**
	 * @param mixed $earnedAwards
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TravelAgency
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setEarnedAwards($earnedAwards, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($earnedAwards, 'earnedAwards', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return array
	 */
	public function getConfirmationNumbers() {
		return $this->confirmationNumbers;
	}

	/**
	 * @param string $confNo
	 * @return bool
	 */
	public function isConfirmationNumberPrimary($confNo) {
		return (isset($this->primaryConfirmationKey) && is_string($confNo)) ? (strcmp($confNo, $this->primaryConfirmationKey) === 0) : null;
	}

    /**
     * @return mixed
     */
    public function getPrimaryConfirmationNumberKey()
    {
        return $this->primaryConfirmationKey;
    }

	/**
	 * @param $number
	 * @param null $description
	 * @param bool $isPrimary
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TravelAgency
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function addConfirmationNumber($number, $description = null, $isPrimary = null, $allowEmpty = false, $allowNull = true) {
		$this->addKeyValue($number, $description, 'confirmationNumbers', $allowEmpty, $allowNull, []);
		if ($isPrimary) {
			$this->logDebug('setting as primary');
			if (isset($this->primaryConfirmationKey))
				$this->invalid('only 1 primary confNo is allowed');
			else
				$this->primaryConfirmationKey = $number;
		}
		return $this;
	}

	/**
	 * @param $number
	 * @return TravelAgency
	 */
	public function removeConfirmationNumber($number) {
		$this->removeItem($number, 'confirmationNumbers');
		if (isset($this->primaryConfirmationKey) && strcmp($number, $this->primaryConfirmationKey) === 0)
			$this->primaryConfirmationKey = null;
		return $this;
	}

	public function validate()
    {
        $this->validateArrays();
        return $this->valid;
    }
	
	/**
	 * @return Base[]
	 */
	protected function getChildren() {
		return [];
	}


}