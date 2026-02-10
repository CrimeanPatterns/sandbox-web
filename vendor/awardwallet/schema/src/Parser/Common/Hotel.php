<?php

namespace AwardWallet\Schema\Parser\Common;

use AwardWallet\Common\DateTimeUtils;
use AwardWallet\Schema\Parser\Common\Shortcut\Booked;
use AwardWallet\Schema\Parser\Common\Shortcut\Hotel as HotelShortcut;
use AwardWallet\Schema\Parser\Component\Base;
use AwardWallet\Schema\Parser\Component\HasDetailedAddress;
use AwardWallet\Schema\Parser\Common\Shortcut\DetailedAddress as DAShortcut;
use AwardWallet\Schema\Parser\Component\Options;
use Psr\Log\LoggerInterface;

class Hotel extends Itinerary implements HasDetailedAddress {

    public const NON_REFUNDABLE_REGEXP = '/\bno[tn][- ]?refundable\b/i';

	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=medium
	 */
	protected $hotelName;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=medium
	 */
	protected $chainName;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=long
	 */
	protected $address;
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
     * @parsed Boolean
     */
	protected $house;
    /**
     * @parsed Boolean
     */
    protected $host;
	/**
	 * @parsed DateTime
	 */
	protected $checkInDate;
	/**
	 * @parsed DateTime
	 */
	protected $checkOutDate;
	/**
	 * @parsed Field
	 * @attr type=number
	 * @attr max=100
	 */
	protected $guestCount;
	/**
	 * @parsed Field
	 * @attr type=number
	 * @attr max=20
	 */
	protected $kidsCount;
	/**
	 * @parsed Field
	 * @attr type=number
	 * @attr max=20
	 */
	protected $roomsCount;
    /**
     * @parsed Field
     * @attr type=number
     * @attr max=99
     */
	protected $freeNights;
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
     * @parsed DateTime
     */
	protected $deadline;
    /**
     * @parsed Boolean
     */
	protected $nonRefundable;
	/**
	 * @parsed Boolean
	 */
	protected $noCheckInDate;
	/**
	 * @parsed Boolean
	 */
	protected $noCheckOutDate;
	/**
	 * @parsed Boolean
	 */
	protected $noAddress;

	/** @var  DetailedAddress $detailedAddress */
	protected $detailedAddress;

	/** @var Room[] $rooms */
	protected $rooms;
	protected $_room_cnt;

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

	/** @var HotelShortcut $_hotel */
	protected $_hotel;
	/** @var  Booked $_booked */
	protected $_booked;

	public function __construct($name, LoggerInterface $logger, Options $options = null) {
		parent::__construct($name, $logger, $options);
		$this->_hotel = new HotelShortcut($this, new DAShortcut($this, ''));
		$this->_booked = new Booked($this);
		$this->rooms = [];
		$this->_room_cnt = 0;
	}

	/**
	 * @return HotelShortcut
	 */
	public function hotel() {
		return $this->_hotel;
	}

	/**
	 * @return Booked
	 */
	public function booked() {
		return $this->_booked;
	}

	/**
	 * @return mixed
	 */
	public function getHotelName() {
		return $this->hotelName;
	}

	/**
	 * @param mixed $hotelName
	 * @return Hotel
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setHotelName($hotelName) {
		$this->setProperty($hotelName, 'hotelName', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getChainName() {
		return $this->chainName;
	}

	/**
	 * @param mixed $chainName
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Hotel
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setChainName($chainName, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($chainName, 'chainName', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getAddress() {
		return $this->address;
	}

	/**
	 * @param mixed $address
	 * @return Hotel
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setAddress($address) {
		$this->setProperty($address, 'address', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getCheckInDate() {
		return $this->checkInDate;
	}

	/**
	 * @param mixed $checkInDate
	 * @return Hotel
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setCheckInDate($checkInDate) {
		$this->setProperty($checkInDate, 'checkInDate', false, false);
		return $this;
	}

	/**
	 * @param $date
	 * @param $relative
	 * @param bool $after
	 * @param string $format
	 * @return Hotel
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function parseCheckInDate($date, $relative = null, $format = '%D% %Y%', $after = true) {
		$this->parseUnixTimeProperty($date, 'checkInDate', $relative, $after, $format);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getCheckOutDate() {
		return $this->checkOutDate;
	}

	/**
	 * @param mixed $checkOutDate
	 * @return Hotel
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setCheckOutDate($checkOutDate) {
		$this->setProperty($checkOutDate, 'checkOutDate', false, false);
		return $this;
	}

	/**
	 * @param $date
	 * @param $relative
	 * @param bool $after
	 * @param string $format
	 * @return Hotel
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function parseCheckOutDate($date, $relative = null, $format = '%D% %Y%', $after = true) {
		$this->parseUnixTimeProperty($date, 'checkOutDate', $relative, $after, $format);
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
	 * @return Hotel
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
	 * @return Hotel
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setFax($fax, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($fax, 'fax', $allowEmpty, $allowNull);
		return $this;
	}

    /**
     * @return mixed
     */
	public function getHouse()
    {
        return $this->house;
    }

    /**
     * @param bool $house
     * @return Hotel
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setHouse($house)
    {
        $this->setProperty($house, 'house', false, false);
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
     * @return Hotel
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setHost($host)
    {
        $this->setProperty($host, 'host', false, false);
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
	 * @return Hotel
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setGuestCount($guestCount, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($guestCount, 'guestCount', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getKidsCount() {
		return $this->kidsCount;
	}

	/**
	 * @param mixed $kidsCount
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Hotel
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setKidsCount($kidsCount, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($kidsCount, 'kidsCount', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getRoomsCount() {
		return $this->roomsCount;
	}

	/**
	 * @param mixed $roomsCount
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Hotel
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setRoomsCount($roomsCount, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($roomsCount, 'roomsCount', $allowEmpty, $allowNull);
		return $this;
	}

    /**
     * @return mixed
     */
    public function getFreeNights()
    {
        return $this->freeNights;
    }

    /**
     * @param mixed $freeNights
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return Hotel
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setFreeNights($freeNights, $allowEmpty = false, $allowNull = false) {
        $this->setProperty($freeNights, 'freeNights', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDeadline()
    {
        return $this->deadline;
    }

    /**
     * @param mixed $deadline
     * @return Hotel
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setDeadline($deadline): Hotel
    {
        $this->setProperty($deadline, 'deadline', false, false);
        return $this;
    }

    /**
     * @param $deadline
     * @return Hotel
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function parseDeadline($deadline): Hotel
    {
        $this->parseUnixTimeProperty($deadline, 'deadline', null, null, null);
        return $this;
    }

    /**
     * @param $prior string must be understood by strtotime, e.g. '2 days', '48 hours'
     * @param $hour string must by understood by strtotime, e.g. '13 PM', '14:00'
     * @return Hotel
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function parseDeadlineRelative($prior, $hour): Hotel
    {
        if (empty($this->checkInDate))
            $this->invalid('checkInDate has to be set to parse relative deadline');
        $this->logDebug(sprintf('%s: parsing deadline relative to check-in: [%s, %s]', $this->_name, $this->str($prior), $this->str($hour)));
        $base = $this->checkInDate;
        if (!empty($hour))
            $base = strtotime($hour, $base);
        if (empty($base))
            $this->invalid('invalid hour supplied to parseDeadlineRelative');
        $this->setProperty(strtotime('-'.$prior, $base), 'deadline', false, false);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getNonRefundable()
    {
        return $this->nonRefundable;
    }

    /**
     * @param mixed $nonRefundable
     * @return Hotel
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setNonRefundable($nonRefundable): Hotel
    {
        $this->setProperty($nonRefundable, 'nonRefundable', false, false);
        return $this;
    }

    /**
     * @param string search
     * @return Hotel
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function parseNonRefundable($search = self::NON_REFUNDABLE_REGEXP): Hotel
    {
        if (empty($this->cancellation))
            $this->invalid('cancellation is required to parse nonRefundable');
        else
            if (in_array($search[0], ['/', '#'])) {
                if (preg_match($search, $this->cancellation) > 0) {
                    $this->setNonRefundable(true);
                }
            }
            else {
                if (stripos($this->cancellation, $search) !== false) {
                    $this->setNonRefundable(true);
                }
            }
        return $this;
    }

	/**
	 * @return Room[]
	 */
	public function getRooms() {
		return $this->rooms;
	}

    /**
     * @return Room
     */
	public function addRoom() {
		$n = new Room($this->_name.'-room-'.$this->_room_cnt, $this->logger, $this->_options);
		$this->_room_cnt++;
		$this->rooms[] = $n;
		$this->logDebug(sprintf('%s: added room %s', $this->_name, $n->getId()));
		return $n;
	}

    /**
     * @param Room $room
     * @return Hotel
     */
	public function removeRoom(Room $room) {
		$idx = null;
		foreach($this->rooms as $i => $r)
			if (strcmp($r->getId(), $room->getId()) === 0) {
				$idx = $i;
				break;
			}
		if (isset($idx))
			unset($this->rooms[$idx]);
		$this->logDebug(sprintf('%s: removed room %s', $this->_name, $room->getId()));
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getNoCheckInDate() {
		return $this->noCheckInDate;
	}

	/**
	 * @param mixed $noCheckInDate
	 * @return Hotel
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setNoCheckInDate($noCheckInDate) {
		$this->setProperty($noCheckInDate, 'noCheckInDate', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getNoCheckOutDate() {
		return $this->noCheckOutDate;
	}

	/**
	 * @param mixed $noCheckOutDate
	 * @return Hotel
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setNoCheckOutDate($noCheckOutDate) {
		$this->setProperty($noCheckOutDate, 'noCheckOutDate', false, false);
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getNoAddress() {
		return $this->noAddress;
	}

	/**
	 * @param boolean $noAddress
	 * @return Hotel
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setNoAddress($noAddress) {
		$this->setProperty($noAddress, 'noAddress', false, false);
		return $this;
	}

	/**
	 * @param $option
	 * @return DetailedAddress
	 */
	public function obtainDetailedAddress($option = '') {
		if (!isset($this->detailedAddress))
			$this->detailedAddress = new DetailedAddress($this->getId().'-address', $this->logger, $this->_options);
		return $this->detailedAddress;
	}

	/**
	 * @return DetailedAddress
	 */
	public function getDetailedAddress() {
		return $this->detailedAddress;
	}

	/**
	 * @return Hotel
	 */
	public function removeDetailedAddress() {
		unset($this->detailedAddress);
		return $this;
	}

	protected function fromArrayChildren(array $arr)
    {
        parent::fromArrayChildren($arr);
        if (isset($arr['rooms'])) {
            foreach($arr['rooms'] as $room)
                $this->addRoom()->fromArray($room);
        }
    }

    /**
	 * @return Base[]
	 */
	protected function getChildren() {
		$r = array_merge(parent::getChildren(), $this->rooms);
		if (isset($this->detailedAddress))
			$r[] = $this->detailedAddress;
		return $r;
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
            if (empty($this->address) && $this->noAddress !== true || strcmp($this->address, $this->hotelName) === 0)
                $this->invalid('missing or invalid hotel address');
            if (empty($this->checkInDate) && $this->noCheckInDate !== true)
                $this->invalid('missing check-in date');
            if (empty($this->checkOutDate) && $this->noCheckOutDate !== true)
                $this->invalid('missing check-out date');
            if (empty($this->hotelName))
                $this->invalid('missing hotel name');
        }
        else {
            if ((empty($this->hotelName) || empty($this->checkInDate) && empty($this->checkOutDate)) && !$confNo)
                $this->invalid('not enough info for cancelled');
        }
        if (!empty($this->checkInDate) && !empty($this->checkOutDate) && $this->checkOutDate < $this->checkInDate)
            $this->invalid('invalid dates');
        foreach ($this->rooms as $room) {
            if (!$room->checkEmpty()) {
                $this->valid = false;
            }
            elseif (!empty($room->getRate()) && !empty($room->getRates())) {
                $this->invalid('both properties cannot be filled: rate & rates');
            }
            elseif (!empty($room->getRates()) && !empty($this->checkInDate) && !empty($this->checkOutDate)) {
                $dayIn = strtotime(date('Y-m-d', $this->checkInDate));
                $dayOut = strtotime(date('Y-m-d', $this->checkOutDate));
                $totalNights = max(1, (int)(($dayOut - $dayIn) / DateTimeUtils::SECONDS_PER_DAY));
                if (count($room->getRates()) !== $totalNights) {
                    $this->invalid(sprintf('the number of entries in the rates (%d) does not match the number of nights (%d)', count($room->getRates()), $totalNights));
                }
            }
        }
        $this->checkTravellers();
        return $this->valid;
    }

	/**
	 * @return void
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	protected function validateItinerary() {
		if (empty($this->address) && $this->noAddress !== true || strcmp($this->address, $this->hotelName) === 0)
			$this->invalid('missing or invalid hotel address');
		if (empty($this->checkInDate) && $this->noCheckInDate !== true)
			$this->invalid('missing check-in date');
		if (empty($this->checkOutDate) && $this->noCheckOutDate !== true)
			$this->invalid('missing check-out date');
		if ($this->noCheckInDate && !empty($this->checkInDate)) {
		    $this->invalid('invalid checkInDate/noCheckInDate');
        }
		if ($this->noCheckOutDate && !empty($this->checkOutDate)) {
		    $this->invalid('invalid checkOutDate/noCheckOutDate');
        }
		if (empty($this->hotelName))
			$this->invalid('missing hotel name');
	}

	public function validateCancelled()
    {
        if (empty($this->hotelName) || empty($this->checkInDate) && empty($this->checkOutDate))
            $this->invalid('missing data in cancelled reservation');
    }

}
