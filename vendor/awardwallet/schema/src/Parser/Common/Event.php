<?php

namespace AwardWallet\Schema\Parser\Common;


use AwardWallet\Schema\Parser\Common\Shortcut\EventBooked;
use AwardWallet\Schema\Parser\Common\Shortcut\EventPlace;
use AwardWallet\Schema\Parser\Common\Shortcut\EventType;
use AwardWallet\Schema\Parser\Component\Base;
use AwardWallet\Schema\Parser\Component\Options;
use Psr\Log\LoggerInterface;

class Event extends Itinerary {

	const TYPE_RESTAURANT = 1;
	const TYPE_MEETING = 2;
	const TYPE_SHOW = 3;
	const TYPE_EVENT = 4;

	protected $_types = [self::TYPE_RESTAURANT, self::TYPE_MEETING, self::TYPE_SHOW, self::TYPE_EVENT];

	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=long
	 */
	protected $address;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=medium
	 */
	protected $name;
    /**
     * @parsed Field
     * @attr type=number
     * @attr enum=[1,2,3,4]
     */
	protected $eventType;
	/**
	 * @parsed DateTime
	 */
	protected $startDate;
	/**
	 * @parsed DateTime
	 */
	protected $endDate;
	/**
	 * @parsed Boolean
	 */
	protected $noStartDate;
	/**
	 * @parsed Boolean
	 */
	protected $noEndDate;
	/**
	 * @parsed Field
	 * @attr type=phone
	 */
	protected $phone;
	/**
	 * @parsed Field
	 * @attr type=phone
	 */
	protected $fax;
	/**
	 * @parsed Field
	 * @attr type=number
	 * @attr max=100
	 */
	protected $guestCount;
    /**
     * @parsed Field
     * @attr type=number
     * @attr max=100
     */
    protected $kidsCount;
	/**
	 * @parsed Arr
	 * @attr item=Field
	 * @attr unique=true
	 * @attr item_type=basic
	 * @attr item_length=short
	 *
	 */
	protected $seats;
    /**
     * @parsed Boolean
     */
    protected $host;
	/**
	 * @parsed KeyValue
	 * @attr unique=strict
	 * @attr key=Field
	 * @attr key_type=confno
	 * @attr key_length=short
	 * @attr key_minlength=1
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

	/** @var EventPlace $_place */
	protected $_place;
	/** @var EventBooked $_booked */
	protected $_booked;
	/** @var EventType $_type */
	protected $_type;

	public function __construct($name, LoggerInterface $logger, Options $options = null) {
		parent::__construct($name, $logger, $options);
		$this->_place = new EventPlace($this);
		$this->_booked = new EventBooked($this);
		$this->_type = new EventType($this);
	}

	public function place() {
		return $this->_place;
	}

	public function booked() {
		return $this->_booked;
	}

	public function type() {
		return $this->_type;
	}

	/**
	 * @return mixed
	 */
	public function getAddress() {
		return $this->address;
	}

	/**
	 * @param mixed $address
	 * @return Event
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setAddress($address) {
		$this->setProperty($address, 'address', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param mixed $name
	 * @return Event
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setName($name) {
		$this->setProperty($name, 'name', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getEventType() {
		return $this->eventType;
	}

	/**
	 * @param mixed $type
	 * @return Event
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setEventType($type) {
	    $this->setProperty($type, 'eventType', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getStartDate() {
		return $this->startDate;
	}

	/**
	 * @param mixed $startDate
	 * @return Event
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setStartDate($startDate) {
		$this->setProperty($startDate, 'startDate', false, false);
		return $this;
	}

	/**
	 * @param $date
	 * @param $relative
	 * @param string $format
	 * @param bool $after
	 * @return Event
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function parseStartDate($date, $relative = null, $format = '%D% %Y%', $after = true) {
		$this->parseUnixTimeProperty($date, 'startDate', $relative, $after, $format);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getEndDate() {
		return $this->endDate;
	}

	/**
	 * @param mixed $endDate
	 * @return Event
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setEndDate($endDate) {
		$this->setProperty($endDate, 'endDate', false, false);
		return $this;
	}

	/**
	 * @param $date
	 * @param $relative
	 * @param string $format
	 * @param bool $after
	 * @return Event
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function parseEndDate($date, $relative = null, $format = '%D% %Y%', $after = true) {
		$this->parseUnixTimeProperty($date, 'endDate', $relative, $after, $format);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getNoStartDate() {
		return $this->noStartDate;
	}

	/**
	 * @param mixed $noStartDate
	 * @return Event
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setNoStartDate($noStartDate) {
		$this->setProperty($noStartDate, 'noStartDate', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getNoEndDate() {
		return $this->noEndDate;
	}

	/**
	 * @param mixed $noEndDate
	 * @return Event
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setNoEndDate($noEndDate) {
		$this->setProperty($noEndDate, 'noEndDate', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getPhone() {
		return $this->phone;
	}

	/**
	 * @param mixed $phone
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Event
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setPhone($phone, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($phone, 'phone', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getFax() {
		return $this->fax;
	}

	/**
	 * @param mixed $fax
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Event
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setFax($fax, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($fax, 'fax', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getGuestCount() {
		return $this->guestCount;
	}

	/**
	 * @param mixed $guestCount
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Event
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setGuestCount($guestCount, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($guestCount, 'guestCount', $allowEmpty, $allowNull);
		return $this;
	}

    /**
     * @return mixed
     */
    public function getKidsCount()
    {
        return $this->kidsCount;
    }

    /**
     * @param mixed $kidsCount
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return Event
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setKidsCount($guestCount, $allowEmpty = false, $allowNull = false)
    {
        $this->setProperty($guestCount, 'kidsCount', $allowEmpty, $allowNull);
        return $this;
    }

	/**
	 * @return mixed
	 */
	public function getSeats() {
		return $this->seats;
	}

	/**
	 * @param mixed $seats
	 * @return Event
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setSeats($seats) {
		$this->setProperty($seats, 'seats', false, false);
		return $this;
	}

	/**
	 * @param $seat
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Event
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function addSeat($seat, $allowEmpty = false, $allowNull = false) {
		$this->addItem($seat, 'seats', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $seat
	 * @return Event
	 */
	public function removeSeat($seat) {
		$this->removeItem($seat, 'seats');
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
     * @return Event
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setHost($host)
    {
        $this->setProperty($host, 'host', false, false);
        return $this;
    }

	/**
	 * @return Base[]
	 */
	protected function getChildren() {
		return parent::getChildren();
	}

	public function validate(bool $hasUpperConfNo)
    {
        $this->validateArrays();
        if ($this->travelAgency)
            $this->valid = $this->travelAgency->validate() && $this->valid;
        if ($this->price)
            $this->valid = $this->price->getValid() && $this->valid;
        $confNo = $hasUpperConfNo || $this->hasConfNo();
        if (!$this->cancelled) {
            if (empty($this->confirmationNumbers) && !$this->noConfirmationNumber)
                $this->invalid('missing confirmation number');
            if (empty($this->address))
                $this->invalid('missing address');
            if (empty($this->name))
                $this->invalid('missing name');
            if (empty($this->eventType))
                $this->invalid('missing type');
            if (empty($this->startDate) && $this->noStartDate !== true)
                $this->invalid('missing startDate');
            if (empty($this->endDate) && $this->noEndDate !== true)
                $this->invalid('missing endDate');
        }
        else {
            if ((empty($this->name) || empty($this->startDate)) && !$confNo)
                $this->invalid('not enough info for cancelled');
        }
        if (null !== $this->startDate && null !== $this->endDate && $this->startDate > $this->endDate)
            $this->invalid('invalid dates');
        $this->checkTravellers();
        return $this->valid;
    }

}
