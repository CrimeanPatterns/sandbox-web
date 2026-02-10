<?php

namespace AwardWallet\Schema\Parser\Common;

use AwardWallet\Schema\Parser\Component\Base;
use AwardWallet\Schema\Parser\Component\Options;
use Psr\Log\LoggerInterface;


class Transfer extends Itinerary {

	/** @var TransferSegment[] $segments */
	protected $segments;
	protected $_seg_cnt;
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
    /**
     * @parsed Boolean
     */
    protected $allowTzCross;
    /**
     * @parsed Boolean
     */
    protected $host;

	public function __construct($name, LoggerInterface $logger, Options $options = null) {
		parent::__construct($name, $logger, $options);
		$this->segments = [];
		$this->_seg_cnt = 0;
	}

	/**
	 * @return TransferSegment[]
	 */
	public function getSegments() {
		return $this->segments;
	}

	/**
	 * @return TransferSegment
	 */
	public function addSegment() {
		$segment = new TransferSegment(sprintf('%s-%d', $this->_name, $this->_seg_cnt), $this->logger, $this->_options);
		$this->_seg_cnt++;
		$this->segments[] = $segment;
		$this->logDebug('created new transfer segment ' . $segment->getId());
		return $segment;
	}

    /**
     * @return boolean
     */
    public function getAllowTzCross()
    {
        return $this->allowTzCross;
    }

    /**
     * @param boolean $allowTzCross
     * @return Transfer
     */
    public function setAllowTzCross($allowTzCross)
    {
        $this->setProperty($allowTzCross, 'allowTzCross', false, false);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param bool $host
     * @return Transfer
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setHost($host)
    {
        $this->setProperty($host, 'host', false, false);
        return $this;
    }

	/**
	 * @param TransferSegment $segment
	 * @return Transfer
	 */
	public function removeSegment(TransferSegment $segment) {
		$idx = null;
		foreach($this->segments as $key => $seg)
			if (strcmp($seg->getId(), $segment->getId()) === 0) {
				$idx = $key;
				break;
			}
		if (isset($idx)) {
			unset($this->segments[$idx]);
			$this->logDebug('removed segment ' . $segment->getId());
		}
		return $this;
	}

    public function validate(bool $hasUpperConfNo)
    {
        $this->validateArrays();
        foreach($this->segments as $segment) {
            $this->valid = $this->valid && $segment->validateBasic($this->allowTzCross);
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

	/**
	 * @return Base[]
	 */
	protected function getChildren() {
		return array_merge(parent::getChildren(), $this->segments);
	}

    public function sortSegments()
    {
        $this->segments = array_values($this->segments);
        usort($this->segments, function(TransferSegment $a, TransferSegment $b){
            $date1 = $a->getDepDate() ?? $a->getArrDate() ?? 0;
            $date2 = $b->getDepDate() ?? $b->getArrDate() ?? 0;
            return $date1 - $date2;
        });
    }
	
}