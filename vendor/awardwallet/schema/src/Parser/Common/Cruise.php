<?php

namespace AwardWallet\Schema\Parser\Common;


use AwardWallet\Schema\Parser\Common\Shortcut\CruiseDetails;
use AwardWallet\Schema\Parser\Component\Options;
use Psr\Log\LoggerInterface;

class Cruise extends Itinerary {

	/** @var CruiseSegment[] */
	protected $segments;
	protected $_seg_cnt;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=long
	 */
	protected $description;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=medium
	 */
	protected $class;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=short
	 */
	protected $deck;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=short
	 */
	protected $room;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=medium
	 */
	protected $ship;
	/**
	 * @parsed Field
	 * @attr type=clean
	 * @attr length=short
	 */
	protected $shipCode;
    /**
     * @parsed Field
     * @attr type=clean
     * @attr length=short
     */
    protected $voyageNumber;


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
	protected $noConfirmationNumber;
	protected $primaryConfirmationKey;
	/**
	 * @parsed DateTime
	 * @attr seconds=true
	 */
	protected $reservationDate;
	/**
	 * @parsed Field
	 * @attr type=sentence
	 * @attr length=medium
	 */
	protected $status;
	/**
	 * @parsed KeyValue
	 * @attr unique=false
	 * @attr key=Field
	 * @attr key_type=basic
	 * @attr key_length=medium
	 * @attr key_minlength=2
	 * @attr val=Boolean
	 */
	protected $travellers;
	/**
	 * @parsed Boolean
	 */
	protected $areNamesFull;
	/**
	 * @parsed Boolean
	 */
	protected $cancelled;
    /**
     * @parsed Field
     * @attr type=soft
     * @attr length=long
     */
    protected $cancellation;

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
    /**
     * @parsed Field
     * @attr type=basic
     * @attr length=extra
     */
    protected $notes;

	/** @var CruiseDetails $_details */
	protected $_details;

	public function __construct($name, LoggerInterface $logger, Options $options = null) {
		parent::__construct($name, $logger, $options);
		$this->segments = [];
		$this->_seg_cnt = 0;
		$this->_details = new CruiseDetails($this);
	}

	public function details() {
		return $this->_details;
	}

	/**
	 * @return CruiseSegment[]
	 */
	public function getSegments() {
		return $this->segments;
	}

	/**
	 * @return CruiseSegment
	 */
	public function addSegment() {
		$segment = new CruiseSegment(sprintf('%s-%d', $this->_name, $this->_seg_cnt), $this->logger, $this->_options);
		$this->_seg_cnt++;
		$this->segments[] = $segment;
		$this->logDebug(sprintf('%s: created new segment %s', $this->_name, $segment->getId()));
		return $segment;
	}

	/**
	 * @param CruiseSegment $segment
	 * @return Cruise
	 */
	public function removeSegment(CruiseSegment $segment) {
		$idx = null;
		foreach($this->segments as $key => $seg)
			if (strcmp($seg->getId(), $segment->getId()) === 0) {
				$idx = $key;
				break;
			}
		if (isset($idx)) {
			unset($this->segments[$idx]);
			$this->logDebug(sprintf('%s: removed segment %s', $this->_name, $segment->getId()));
		}
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
	 * @return Cruise
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setDescription($description, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($description, 'description', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getClass() {
		return $this->class;
	}

	/**
	 * @param mixed $class
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Cruise
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setClass($class, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($class, 'class', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getDeck() {
		return $this->deck;
	}

	/**
	 * @param mixed $deck
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Cruise
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setDeck($deck, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($deck, 'deck', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getRoom() {
		return $this->room;
	}

	/**
	 * @param mixed $room
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Cruise
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setRoom($room, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($room, 'room', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getShip() {
		return $this->ship;
	}

	/**
	 * @param mixed $ship
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Cruise
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setShip($ship, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($ship, 'ship', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getShipCode() {
		return $this->shipCode;
	}

	/**
	 * @param mixed $shipCode
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Cruise
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setShipCode($shipCode, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($shipCode, 'shipCode', $allowEmpty, $allowNull);
		return $this;
	}

    /**
     * @return mixed
     */
    public function getVoyageNumber() {
        return $this->voyageNumber;
    }

    /**
     * @param mixed $voyageNumber
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return Cruise
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setVoyageNumber($voyageNumber, $allowEmpty = false, $allowNull = false) {
        $this->setProperty($voyageNumber, 'voyageNumber', $allowEmpty, $allowNull);
        return $this;
    }

	/**
	 * @return array
	 */
	protected function getChildren() {
		return array_merge(parent::getChildren(), $this->segments);
	}

    public function validate(bool $hasUpperConfNo)
    {
        $this->validateArrays();
        foreach($this->segments as $segment) {
            $this->valid = $this->valid && $segment->validateBasic();
            if (!$this->cancelled)
                $this->valid = $this->valid && $segment->validateData();
        }
        if (!$this->cancelled) {
            if (count($this->segments) === 0)
                $this->invalid('missing segments');
            if (empty($this->confirmationNumbers) && !$this->noConfirmationNumber)
                $this->invalid('missing confirmation number');
        }
        else {
            if (!$hasUpperConfNo && !$this->hasConfNo())
                $this->invalid('missing confirmation number');
        }
        if ($this->travelAgency)
            $this->valid = $this->travelAgency->validate() && $this->valid;
        if ($this->price)
            $this->valid = $this->price->getValid() && $this->valid;
        $this->checkTravellers();
        return $this->valid;
    }

    public function sortSegments()
    {
        $this->segments = array_values($this->segments);
        usort($this->segments, function(CruiseSegment $a, CruiseSegment $b){
            $date1 = $a->getAboard() ?? $a->getAshore() ?? 0;
            $date2 = $b->getAboard() ?? $b->getAshore() ?? 0;
            return $date1 - $date2;
        });
    }

}