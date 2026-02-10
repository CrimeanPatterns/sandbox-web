<?php

namespace AwardWallet\Schema\Parser\Common;


use AwardWallet\Common\Parser\Util\NameHelper;
use AwardWallet\Schema\Parser\Common\Shortcut\General;
use AwardWallet\Schema\Parser\Common\Shortcut\ProviderInfo;
use AwardWallet\Schema\Parser\Component\Base;
use AwardWallet\Schema\Parser\Common\Shortcut\Price as PriceShortcut;
use AwardWallet\Schema\Parser\Common\Shortcut\TravelAgency as OtaShortcut;
use AwardWallet\Schema\Parser\Component\HasPrice;
use AwardWallet\Schema\Parser\Component\HasTravelAgency;
use AwardWallet\Schema\Parser\Component\Options;
use Psr\Log\LoggerInterface;

abstract class Itinerary extends Base implements HasTravelAgency, HasPrice {

	protected $type;
	protected $confirmationNumbers;
	protected $noConfirmationNumber;
	protected $primaryConfirmationKey;
	protected $reservationDate;
	protected $status;
	protected $travellers;
	protected $infants;
	protected $areNamesFull;
	protected $cancelled;
	protected $cancellation;
	protected $cancellationNumber;

	protected $providerKeyword;
	protected $providerCode;
	protected $providerPhones;
	protected $accountNumbers;
	protected $areAccountMasked;
	protected $earnedAwards;
    protected $notes;
	/** @var  TravelAgency $travelAgency */
	protected $travelAgency;

	/** @var Price $price */
	protected $price;

	/** @var ProviderInfo $_program */
	protected $_program;
	/** @var PriceShortcut $_price */
	protected $_price;
	/** @var General $_general */
	protected $_general;
	/** @var  OtaShortcut $_ota; */
	protected $_ota;

	public function __construct($name, LoggerInterface $logger, Options $options = null) {
		parent::__construct($name, $logger, $options);
		$this->_program = new ProviderInfo($this);
		$this->_price = new PriceShortcut($this);
		$this->_general = new General($this);
		$this->_ota = new OtaShortcut($this);
		$p = explode('\\', get_class($this));
		$this->type = strtolower(array_pop($p));
	}

	/**
	 * @return PriceShortcut
	 */
	public function price() {
		return $this->_price;
	}

	/**
	 * @return ProviderInfo
	 */
	public function program() {
		return $this->_program;
	}

	/**
	 * @return General
	 */
	public function general() {
		return $this->_general;
	}

	/**
	 * @return OtaShortcut
	 */
	public function ota() {
		return $this->_ota;
	}

	public function getType()
    {
	    return $this->type;
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
	 * @return self
	 */
	public function removePrice() {
		$this->price = null;
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
	 * @return Itinerary
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
	 * @return Itinerary
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
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Itinerary
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function addProviderPhone($phone, $desc = null, $allowEmpty = false, $allowNull = true) {
		$this->addKeyValue($phone, $desc, 'providerPhones', $allowEmpty, $allowNull, []);
		return $this;
	}

	/**
	 * @param $phone
	 * @return Itinerary
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
	 * @return Itinerary
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
	 * @return Itinerary
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function addAccountNumber($number, $isMasked) {
		$this->addKeyValue($number, $isMasked, 'accountNumbers', false, false, []);
		return $this;
	}

	/**
	 * @param string $number
	 * @return Itinerary
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
	 * @return Itinerary
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
	 * @return Itinerary
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setEarnedAwards($earnedAwards, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($earnedAwards, 'earnedAwards', $allowEmpty, $allowNull);
		return $this;
	}

    /**
     * @return mixed
     */
    public function getNotes() {
        return $this->notes;
    }

    /**
     * @param mixed $notes
     * @return Itinerary
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setNotes($notes) {
        $this->setProperty($notes, 'notes', true, true);
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
     * @param string $number
     * @param string $description
     * @param bool $isPrimary
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @param null $numberAttr
     * @return Itinerary
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
	public function addConfirmationNumber($number, ?string $description = null, ?bool $isPrimary = null, bool $allowEmpty = false, bool $allowNull = true, $numberAttr = null) {
	    if (is_string($numberAttr))
	        $numberAttr = [
	            'regexp' => $numberAttr,
            ];
        if (!is_array($numberAttr))
            $numberAttr = [];
		$this->addKeyValue($number, $description, 'confirmationNumbers', $allowEmpty, $allowNull, $numberAttr);
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
	 * @return Itinerary
	 */
	public function removeConfirmationNumber($number) {
		$this->removeItem($number, 'confirmationNumbers');
		if (isset($this->primaryConfirmationKey) && strcmp($number, $this->primaryConfirmationKey) === 0)
			$this->primaryConfirmationKey = null;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getNoConfirmationNumber() {
		return $this->noConfirmationNumber;
	}

	/**
	 * @param mixed $noConfirmationNumber
	 * @return Itinerary
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setNoConfirmationNumber($noConfirmationNumber) {
		$this->setProperty($noConfirmationNumber, 'noConfirmationNumber', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getReservationDate() {
		return $this->reservationDate;
	}

	/**
	 * @param mixed $reservationDate
	 * @return Itinerary
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setReservationDate($reservationDate) {
		$this->setProperty($reservationDate, 'reservationDate', false, false);
		return $this;
	}

	/**
	 * @param $date
	 * @param $relative
	 * @param string $format
	 * @param bool $after
	 * @return Itinerary
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function parseReservationDate($date, $relative = null, $format = '%D% %Y%', $after = true) {
		$this->parseUnixTimeProperty($date, 'reservationDate', $relative, $after, $format);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @param mixed $status
	 * @return Itinerary
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setStatus($status) {
		$this->setProperty($status, 'status', false, false);
		return $this;
	}

	/**
	 * @return array
	 */
	public function getTravellers() {
		return $this->travellers;
	}

    /**
     * @param string $name
     * @param null $isNameFull
     * @return Itinerary
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
	public function addTraveller($name, $isNameFull = null) {
		$this->addKeyValue($name, $isNameFull, 'travellers', false, true, []);
		return $this;
	}

	/**
	 * @param string $name
	 * @return Itinerary
	 */
	public function removeTraveller($name) {
		$this->removeItem($name, 'travellers');
		return $this;
	}

    /**
     * @param string[] $names
     * @param null $areNamesFull
     * @return Itinerary
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
	public function setTravellers($names, $areNamesFull = null) {
		if (!is_array($names))
			$this->invalid('setTravellers: expecting array');
		elseif (empty($names))
			$this->invalid('setTravellers: expecting not empty array');
		else
			foreach($names as $name)
				$this->addTraveller($name, $areNamesFull);
		return $this;
	}

    /**
     * @return array
     */
    public function getInfants()
    {
        return $this->infants;
    }

    /**
     * @param string $name
     * @param null $isNameFull
     * @return Itinerary
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function addInfant($name, $isNameFull = null) {
        $this->addKeyValue($name, $isNameFull, 'infants', false, true, []);
        return $this;
    }

    /**
     * @param string $name
     * @return Itinerary
     */
    public function removeInfant($name) {
        $this->removeItem($name, 'infants');
        return $this;
    }

    /**
     * @param string[] $names
     * @param null $areNamesFull
     * @return Itinerary
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setInfants($names, $areNamesFull = null) {
        if (!is_array($names))
            $this->invalid('setInfants: expecting array');
        elseif (empty($names))
            $this->invalid('setInfants: expecting not empty array');
        else
            foreach($names as $name)
                $this->addInfant($name, $areNamesFull);
        return $this;
    }

	/**
	 * @return boolean
	 */
	public function getAreNamesFull() {
		return $this->areNamesFull;
	}

	/**
	 * @param boolean $areNamesFull
	 * @return Itinerary
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setAreNamesFull($areNamesFull) {
		$this->setProperty($areNamesFull, 'areNamesFull', false, false);
		return $this;
	}

    /**
     * @return boolean
     */
    public function getCancelled() {
        return $this->cancelled;
    }

	/**
	 * @param $cancelled
	 * @return Itinerary
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setCancelled($cancelled) {
		$this->setProperty($cancelled, 'cancelled', false, false);
		return $this;
	}

    /**
     * @return mixed
     */
    public function getCancellation() {
        return $this->cancellation;
    }

    /**
     * @param mixed $cancellation
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return Itinerary
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setCancellation($cancellation, $allowEmpty = false, $allowNull = false) {
        $this->setProperty($cancellation, 'cancellation', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCancellationNumber()
    {
        return $this->cancellationNumber;
    }

    /**
     * @param mixed $cancellationNumber
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return Itinerary
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setCancellationNumber($cancellationNumber, $allowEmpty = false, $allowNull = false)
    {
        $this->setProperty($cancellationNumber, 'cancellationNumber', $allowEmpty, $allowNull);
        return $this;
    }

	/**
	 * @return TravelAgency
	 */
	public function obtainTravelAgency() {
		if (!isset($this->travelAgency))
			$this->travelAgency = new TravelAgency($this->getId().'-ota', $this->logger, $this->_options);
		return $this->travelAgency;
	}

	/**
	 * @return TravelAgency
	 */
	public function getTravelAgency() {
		return $this->travelAgency;
	}

	public function replaceTravelAgency(TravelAgency $ota) {
	    $this->logDebug(sprintf('%s: replaced travelAgency with %s', $this->_name, $ota->getId()));
	    $this->travelAgency = $ota;
    }

    protected function fromArrayChildren(array $arr)
    {
        parent::fromArrayChildren($arr);
        if (isset($arr['segments']) && method_exists($this, 'addSegment')) {
            foreach($arr['segments'] as $segment) {
                /** @var Base $s */
                $s = $this->addSegment();
                $s->fromArray($segment);
            }
        }
        if (isset($arr['primaryConfirmationKey']))
            $this->primaryConfirmationKey = $arr['primaryConfirmationKey'];
    }

    /**
	 * @return Base[]
	 */
	protected function getChildren() {
		$r = [];
		if (isset($this->price))
			$r[] = $this->price;
		if (isset($this->travelAgency))
			$r[] = $this->travelAgency;
		return $r;
	}

	protected function hasConfNo()
    {
        return !empty($this->travelAgency) && count($this->travelAgency->getConfirmationNumbers()) > 0
            || count($this->confirmationNumbers) > 0;
    }

	public abstract function validate(bool $hasUpperConfNo);

	protected function checkTravellers()
    {
        $names = [];
        foreach(['travellers', 'infants'] as $prop) {
            if ($this->$prop)
                foreach ($this->$prop as $i => list($name)) {
                    if (!empty($name) && $this->_options->clearNamePrefix)
                        $this->$prop[$i][0] = $name = NameHelper::removePrefix($name);
                    if (array_key_exists($name, $names)) {
                        $names[$name]++;
                        if ($names[$name] > 3)
                            $this->invalid("too many duplicate $prop");
                    } else
                        $names[$name] = 1;
                }
        }
    }

}