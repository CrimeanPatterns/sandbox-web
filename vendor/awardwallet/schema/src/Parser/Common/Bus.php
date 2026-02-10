<?php

namespace AwardWallet\Schema\Parser\Common;


use AwardWallet\Schema\Parser\Component\Base;
use AwardWallet\Schema\Parser\Component\Options;
use Psr\Log\LoggerInterface;

class Bus extends Itinerary {

	/** @var BusSegment[] $segments */
	protected $segments;
	protected $_seg_cnt;
	/**
	 * @parsed KeyValue
	 * @attr unique=true
	 * @attr key=Field
	 * @attr key_type=basic
	 * @attr key_length=short
	 * @attr val=Boolean
	 */
	protected $ticketNumbers;
	/**
	 * @parsed Boolean
	 */
	protected $areTicketsMasked;
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

	public function __construct($name, LoggerInterface $logger, Options $options = null) {
		parent::__construct($name, $logger, $options);
		$this->segments = [];
		$this->_seg_cnt = 0;
	}

	/**
	 * @return BusSegment[]
	 */
	public function getSegments() {
		return $this->segments;
	}

	/**
	 * @return BusSegment
	 */
	public function addSegment() {
		$segment = new BusSegment(sprintf('%s-%d', $this->_name, $this->_seg_cnt), $this->logger, $this->_options);
		$this->_seg_cnt++;
		$this->segments[] = $segment;
		$this->logDebug($this->getId() . ': created new bus segment ' . $segment->getId());
		return $segment;
	}

	/**
	 * @param BusSegment $segment
	 * @return Bus
	 */
	public function removeSegment(BusSegment $segment) {
		$idx = null;
		foreach($this->segments as $key => $seg)
			if (strcmp($seg->getId(), $segment->getId()) === 0) {
				$idx = $key;
				break;
			}
		if (isset($idx)) {
			unset($this->segments[$idx]);
			$this->logDebug($this->getId() . ': removed segment ' . $segment->getId());
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function getTicketNumbers() {
		return $this->ticketNumbers;
	}

	/**
	 * @param mixed $ticketNumbers
	 * @param boolean $areMasked
	 * @return Bus
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setTicketNumbers($ticketNumbers, $areMasked) {
		if (!is_array($ticketNumbers))
			$this->invalid('setTicketNumbers expects array');
		else
			foreach($ticketNumbers as $num)
				$this->addTicketNumber($num, $areMasked);
		return $this;
	}

	/**
	 * @param $number
	 * @param boolean $isMasked
	 * @return Bus
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function addTicketNumber($number, $isMasked ) {
		$this->addKeyValue($number, $isMasked, 'ticketNumbers', false, true, []);
		return $this;
	}

	/**
	 * @param string $number
	 * @return Bus
	 */
	public function removeTicketNumber($number) {
		$this->removeItem($number, 'ticketNumbers');
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getAreTicketsMasked() {
		return $this->areTicketsMasked;
	}

	/**
	 * @param boolean $areTicketsMasked
	 * @return Bus
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setAreTicketsMasked($areTicketsMasked) {
		$this->setProperty($areTicketsMasked, 'areTicketsMasked', false, false);
		return $this;
	}

	/**
	 * @return Base[]
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
	    usort($this->segments, function(BusSegment $a, BusSegment $b){
	        $date1 = $a->getDepDate() ?? $a->getArrDate() ?? 0;
	        $date2 = $b->getDepDate() ?? $b->getArrDate() ?? 0;
	        return $date1 - $date2;
        });
    }
}