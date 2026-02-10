<?php

namespace AwardWallet\MainBundle\Service\Itinerary;

class HotelReservation extends Itinerary {

	/**
	 * @var string
	 */
	protected $hotelName;

	/**
	 * @var Address
	 */
	protected $address;
	use AddressShortMethods;

	/**
	 * @var string
	 */
	protected $checkInDate;

	/**
	 * @var string
	 */
	protected $checkOutDate;

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
	protected $kidsCount;

	/**
	 * @var int
	 */
	protected $roomsCount;

	/**
	 * @var string
	 */
	protected $cancellationPolicy;

    /**
	 * @var string
	 */
	protected $rate;

    /**
	 * @var string
	 */
	protected $roomType;

    /**
	 * @var string
	 */
	protected $roomTypeDescription;

	/**
	 * @param \AwardWallet\MainBundle\Service\Itinerary\Address $address
	 */
	public function setAddress($address)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->address = $address;
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
	 * @param string $cancellationPolicy
	 */
	public function setCancellationPolicy($cancellationPolicy)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->cancellationPolicy = $cancellationPolicy;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCancellationPolicy()
	{
		return $this->cancellationPolicy;
	}

	/**
	 * @param string $checkInDate
	 */
	public function setCheckInDate($checkInDate)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->checkInDate = $checkInDate;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCheckInDate()
	{
		return $this->checkInDate;
	}

	/**
	 * @param string $checkOutDate
	 */
	public function setCheckOutDate($checkOutDate)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->checkOutDate = $checkOutDate;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCheckOutDate()
	{
		return $this->checkOutDate;
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
	 * @param string $hotelName
	 */
	public function setHotelName($hotelName)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->hotelName = $hotelName;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getHotelName()
	{
		return $this->hotelName;
	}

	/**
	 * @param int $kidsCount
	 */
	public function setKidsCount($kidsCount)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->kidsCount = $kidsCount;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getKidsCount()
	{
		return $this->kidsCount;
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
	 * @param string $roomType
	 */
	public function setRoomType($roomType)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->roomType = $roomType;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getRoomType()
	{
		return $this->roomType;
	}

    /**
	 * @param string $rate
	 */
	public function setRate($rate)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->rate = $rate;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getRate()
	{
		return $this->rate;
	}

    /**
	 * @param string $roomTypeDescription
	 */
	public function setRoomTypeDescription($roomTypeDescription)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->roomTypeDescription = $roomTypeDescription;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getRoomTypeDescription()
	{
		return $this->roomTypeDescription;
	}

	/**
	 * @param int $roomsCount
	 */
	public function setRoomsCount($roomsCount)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->roomsCount = $roomsCount;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getRoomsCount()
	{
		return $this->roomsCount;
	}

	public function __construct($logger = null) {
		parent::__construct($logger);
		$this->providerDetails = new ProviderDetails();
		$this->providerDetails->logger = $this->logger;
		$this->address = new Address();
		$this->address->logger = $this->logger;
	}

	public function convertToOldArrayFormat() {
        $r = [];
		$r['Kind'] = 'R';
		$r['ConfirmationNumber'] = $this->providerDetails->getConfirmationNumber();
		$r['ConfirmationNumbers'] = $this->providerDetails->getConfirmationNumbers();
		$r['TripNumber'] = $this->providerDetails->getTripNumber();
        $r['ReservationDate'] = $this->getProviderDetails()->getReservationDate();
		$r['HotelName'] = $this->getHotelName();
		$r['2ChainName'] = $this->getProviderDetails()->getCompanyName();
		$r['CheckInDate'] = $this->getCheckInDate();
		$r['CheckOutDate'] = $this->getCheckOutDate();
		$r['Address'] = $this->address->getText();
		if ($this->address->getAddressLine())
			$r['DetailedAddress']['AddressLine'] = $this->address->getAddressLine();
		if ($this->address->getCity())
			$r['DetailedAddress']['City'] = $this->address->getCity();
		if ($this->address->getCountryName())
			$r['DetailedAddress']['Country'] = $this->address->getCountryName();
		if ($this->address->getPostalCode())
			$r['DetailedAddress']['PostalCode'] = $this->address->getPostalCode();
		if ($this->address->getStateName())
			$r['DetailedAddress']['StateProv'] = $this->address->getStateName();
		$r['Phone'] = $this->getPhone();
		$r['Fax'] = $this->getFax();
		$r['Guests'] = $this->getGuestCount();
		$r['GuestNames'] = $this->getGuests();
		$r['Kids'] = $this->getKidsCount();
        $r['Rooms'] = $this->getRoomsCount();
        $r['RoomTypeDescription'] = $this->getRoomTypeDescription();
        $r['RoomType'] = $this->getRoomType();
        $r['Rate'] = $this->getRate();
		// ??? $r['RateType'] => 'Spg Cash & Points Only To Be Booked With A Spg Award Stay. Guest Must Be A Spg.member. Must Redeem Starpoints For Cash And Points Award.',
		$r['CancellationPolicy'] = $this->getCancellationPolicy();
		$r['Cost'] = $this->getTotalPrice()->getCost();
		$r['Taxes'] = $this->getTotalPrice()->getTax();;
		$r['Total'] = $this->getTotalPrice()->getTotal();
		$r['Currency'] = $this->getTotalPrice()->getCurrencyCode();
		$r['Status'] = $this->getProviderDetails()->getStatus();
		$r['Cancelled'] = $this->getProviderDetails()->getCancelled();
		$r['SpentAwards'] = $this->getTotalPrice()->getSpentAwards();
		$r['EarnedAwards'] = $this->getTotalPrice()->getEarnedAwards();
		// ??? $r['ExtProperties'] => ["Property1" => "Value1", "Property2" => "Value2"],
		return $r;
	}

}
