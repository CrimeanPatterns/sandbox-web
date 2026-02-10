<?php

namespace AwardWallet\MainBundle\Service\Itinerary;

class Event extends Itinerary {

	/**
	 * @var string
	 */
	protected $eventName;

	/**
	 * @var Address
	 */
	protected $address;
	use AddressShortMethods;

	/**
	 * @var string
	 */
	protected $startDateTime;

	/**
	 * @var string
	 */
	protected $endDateTime;

	/**
	 * @var string
	 */
	protected $phone;

	/**
	 * @var string
	 */
	protected $fax;

	/**
	 * @var array
	 */
	protected $guests;

	/**
	 * @var int
	 */
	protected $guestCount;

    /**
	 * @var int
	 */
	protected $eventType;

	/**
	 * @param \AwardWallet\MainBundle\Service\Itinerary\Address $address
	 */
	public function setAddress($address)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->address = $address;
		$this->address->logger = $this->logger;
		return $this;
	}

	/**
	 * @return \AwardWallet\MainBundle\Service\Itinerary\Address
	 */
	public function getAddress()
	{
		return $this->address;
	}

	/**
	 * @param string $endDateTime
	 */
	public function setEndDateTime($endDateTime)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->endDateTime = $endDateTime;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getEndDateTime()
	{
		return $this->endDateTime;
	}

	/**
	 * @param string $eventName
	 */
	public function setEventName($eventName)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->eventName = $eventName;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getEventName()
	{
		return $this->eventName;
	}

	/**
	 * @param string $fax
	 */
	public function setFax($fax)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->fax = $fax;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getFax()
	{
		return $this->fax;
	}

	/**
	 * @param int $guestCount
	 */
	public function setGuestCount($guestCount)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->guestCount = $guestCount;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getGuestCount()
	{
		return $this->guestCount;
	}

	/**
	 * @param array $guests
	 */
	public function setGuests($guests)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->guests = $guests;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getGuests()
	{
		return $this->guests;
	}

	/**
	 * @param string $phone
	 */
	public function setPhone($phone)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->phone = $phone;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getPhone()
	{
		return $this->phone;
	}

	/**
	 * @param string $startDateTime
	 */
	public function setStartDateTime($startDateTime)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->startDateTime = $startDateTime;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getStartDateTime()
	{
		return $this->startDateTime;
	}

    /**
	 * @param int $eventType
	 */
	public function setEventType($eventType)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->eventType = $eventType;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getEventType()
	{
		return $this->eventType;
	}

	public function __construct($logger = null) {
		parent::__construct($logger);
		$this->address = new Address();
		$this->address->logger = $this->logger;
		$this->providerDetails = new ProviderDetails();
		$this->providerDetails->logger = $this->logger;
	}
	
	public function convertToOldArrayFormat() {
        $r = [];
		$r['Kind'] = 'E';
		$r['ConfNo'] = $this->providerDetails->getConfirmationNumber();
        $r['Cancelled'] = $this->getProviderDetails()->getCancelled();
        $r['TripNumber'] = $this->providerDetails->getTripNumber();
        $r['ReservationDate'] = $this->getProviderDetails()->getReservationDate();
		$r['Name'] = $this->eventName;
		$r['StartDate'] = $this->startDateTime;
		$r['EndDate'] = $this->endDateTime;
		$r['Address'] = $this->address->getText();
		$r['Phone'] = $this->phone;
		if ($this->guests)
			$r['DinerName'] = implode(', ', $this->guests);
		$r['Guests'] = $this->guestCount;
		$r['TotalCharge'] = $this->getTotalPrice()->getTotal();
		$r['Currency'] = $this->getTotalPrice()->getCurrencyCode();
		$r['Tax'] = $this->getTotalPrice()->getTax();
        $r['SpentAwards'] = $this->getTotalPrice()->getSpentAwards();
        $r['EarnedAwards'] = $this->getTotalPrice()->getEarnedAwards();
        $r['EventType'] = $this->getEventType();
		return $r;
	}

}