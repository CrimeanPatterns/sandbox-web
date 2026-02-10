<?php

namespace AwardWallet\Schema\Parser\Common;


use AwardWallet\Schema\Parser\Common\Shortcut\FlightIssued;
use AwardWallet\Schema\Parser\Component\Base;
use AwardWallet\Schema\Parser\Component\Field\Validator;
use AwardWallet\Schema\Parser\Component\Options;
use Psr\Log\LoggerInterface;

class Flight extends Itinerary {

	/** @var FlightSegment[] $segments */
	protected $segments;
	protected $_seg_cnt;

	/**
	 * @parsed KeyValue
	 * @attr unique=true
	 * @attr key=Field
	 * @attr key_regexp=/^[A-Z\d\s\-\*Xx\\\/\|]+$/
	 * @attr key_length=short
	 * @attr val=Boolean
	 */
	protected $ticketNumbers;
	/**
	 * @parsed Boolean
	 */
	protected $areTicketsMasked;
	/**
	 * @parsed Field
	 * @attr regexp=/^[A-Z\d]{5,10}$/
	 */
	protected $issuingConfirmation;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=short
	 */
	protected $issuingAirlineName;
	/**
	 * @parsed Field
	 * @attr type=provider
	 */
	protected $issuingAirlineCode;
	protected $airlinePhones;
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
     * @parsed KeyValue
     * @attr unique=false
     * @attr key=Field
     * @attr key_type=basic
     * @attr key_length=medium
     * @attr key_minlength=2
     * @attr val=Boolean
     */
    protected $infants;
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
     * @attr type=confno
     * @attr length=short
     */
    protected $cancellationNumber;

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

	/** @var FlightIssued $_issued */
	protected $_issued;


	public function __construct($name, LoggerInterface $logger, Options $options = null) {
		parent::__construct($name, $logger, $options);
		$this->segments = [];
		$this->airlinePhones = [];
		$this->_seg_cnt = 0;
		$this->ticketNumbers = [];
		$this->_issued = new FlightIssued($this);
	}

	public function issued() {
		return $this->_issued;
	}

	/**
	 * @return FlightSegment[]
	 */
	public function getSegments() {
		return $this->segments;
	}

	/**
	 * @return FlightSegment
	 */
	public function addSegment() {
		$segment = new FlightSegment(sprintf('%s-%d', $this->_name, $this->_seg_cnt), $this->logger, $this->_options);
		$this->_seg_cnt++;
		$this->segments[] = $segment;
		$this->logDebug($this->getId() . ': created new flight segment ' . $segment->getId());
		return $segment;
	}

	/**
	 * @param FlightSegment $segment
	 * @return Flight
	 */
	public function removeSegment(FlightSegment $segment, ?string $reason = null) {
		$idx = null;
		foreach($this->segments as $key => $seg)
			if (strcmp($seg->getId(), $segment->getId()) === 0) {
				$idx = $key;
				break;
			}
		if (isset($idx)) {
			unset($this->segments[$idx]);
			$this->logDebug($this->getId() . ': removed segment ' . $segment->getId() . ($reason ? (': ' . $reason) : ''));
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
	 * @return Flight
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
	 * @return Flight
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function addTicketNumber($number, $isMasked) {
		$this->addKeyValue($number, $isMasked, 'ticketNumbers', false, true, []);
		return $this;
	}

	/**
	 * @param string $number
	 * @return Flight
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
	 * @return Flight
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setAreTicketsMasked($areTicketsMasked) {
		$this->setProperty($areTicketsMasked, 'areTicketsMasked', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getIssuingConfirmation() {
		return $this->issuingConfirmation;
	}

	/**
	 * @param mixed $issuingConfirmation
	 * @return Flight
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setIssuingConfirmation($issuingConfirmation) {
		$this->setProperty($issuingConfirmation, 'issuingConfirmation', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getIssuingAirlineName() {
		return $this->issuingAirlineName;
	}

	/**
	 * @param mixed $issuingAirlineName
	 * @return Flight
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setIssuingAirlineName($issuingAirlineName) {
		$this->setProperty($issuingAirlineName, 'issuingAirlineName', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getIssuingAirlineCode() {
		return $this->issuingAirlineCode;
	}

	/**
	 * @param mixed $issuingAirlineCode
	 * @return Flight
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setIssuingAirlineCode($issuingAirlineCode) {
		$this->setProperty($issuingAirlineCode, 'issuingAirlineCode', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getAirlinePhones() {
		return $this->airlinePhones;
	}

	/**
	 * @param $airline
	 * @param $phone
	 * @param null $description
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Flight
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function addAirlinePhone($airline, $phone, $description = null, $allowEmpty = false, $allowNull = true) {
		$error = Validator::validateField($airline, 'Field', null, ['type' => 'basic', 'length' => 'short'], false, false);
		if (empty($error))
			$error = Validator::validateField($phone, 'Field', null, ['type' => 'phone'], false, false);
		if (empty($error))
			$error = Validator::validateField($description, 'Field', null, ['type' => 'basic', 'length' => 'long'], $allowEmpty, $allowNull);
		if (!empty($error)) {
			$this->invalid('cannot add airline phone: ' . $error);
		}
		else {
			if (!array_key_exists($airline, $this->airlinePhones))
				$this->airlinePhones[$airline] = [];
			$this->airlinePhones[$airline][] = [$phone, $description];
			$this->logDebug(sprintf('%s: added phone [`%s`, `%s`] for airline `%s`', $this->_name, $phone, $description, $airline));
		}
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
        if ($this->travelAgency)
            $this->valid = $this->travelAgency->validate() && $this->valid;
        if ($this->price)
            $this->valid = $this->price->getValid() && $this->valid;
        $toDelete = [];
        $hasCompleteSegment = $hasLocatorSegment = false;
        foreach($this->segments as $segment) {
            $this->valid = $this->valid && $segment->validateBasic();
            if (!$this->cancelled)
                $this->valid = $this->valid && $segment->validateData();
            if ($segment->hasFakeCodes()) {
                $toDelete[] = $segment;
            }
            else {
                if (!empty($segment->getFlightNumber()) && !empty($segment->getAirlineName())
                    && (!empty($segment->getDepCode()) && !empty($segment->getDepDate()) || !empty($segment->getArrCode()) && !empty($segment->getArrDate())))
                    $hasCompleteSegment = true;
                if (!empty($segment->getConfirmation()))
                    $hasLocatorSegment = true;
            }
        }
        foreach($toDelete as $segment) {
            $this->removeSegment($segment, 'fake air code');
        }
        if (!$this->cancelled) {
            if (empty($this->confirmationNumbers) && !$this->noConfirmationNumber)
                $this->invalid('missing confirmation number');
            if (count($this->segments) === 0)
                $this->invalid('empty segments');
        }
        else {
            if (!$hasCompleteSegment && !($hasLocatorSegment || $hasUpperConfNo || $this->hasConfNo()))
                $this->invalid('not enough info for cancelled');
        }
        $this->checkTravellers();
        if (isset($this->price) && 0 === $this->price->getTotal())
            $this->logNotice('flight total is zero, should check');
        return $this->valid;
    }

    public function sortSegments()
    {
        $this->segments = array_values($this->segments);
        usort($this->segments, function(FlightSegment $a, FlightSegment $b){
            $date1 = $a->getDepDate() ?? $a->getArrDate() ?? 0;
            $date2 = $b->getDepDate() ?? $b->getArrDate() ?? 0;
            return $date1 - $date2;
        });
    }

}