<?php

namespace AwardWallet\MainBundle\Service\Itinerary;

class CarRental extends Itinerary {

	/**
	 * @var CarRentalPoint
	 */
	protected $pickup;

	/**
	 * @var CarRentalPoint
	 */
	protected $dropoff;

	use CarRentalPointsShortMethods;

	/**
	 * @var Person
	 */
	protected $driver;
	/**
	 * @var Car
	 */
	protected $car;
	use CarShortMethods;

	/**
	 * @var Fee[]
	 */
	protected $pricedEquipment;

	/**
	 * @return CarRentalPoint
	 */
	public function getPickup()
	{
		return $this->pickup;
	}

	/**
	 * @param CarRentalPoint $pickup
	 */
	public function setPickup($pickup)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->pickup = $pickup;
		return $this;
	}

	/**
	 * @return CarRentalPoint
	 */
	public function getDropoff()
	{
		return $this->dropoff;
	}

	/**
	 * @param CarRentalPoint $dropoff
	 */
	public function setDropoff($dropoff)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->dropoff = $dropoff;
		return $this;
	}

	/**
	 * @return Person
	 */
	public function getDriver()
	{
		return $this->driver;
	}

	/**
	 * @param Person $driver
	 */
	public function setDriver($driver)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->driver = $driver;
		return $this;
	}

	/**
	 * @return Car
	 */
	public function getCar()
	{
		return $this->car;
	}

	/**
	 * @param Car $car
	 */
	public function setCar($car)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->car = $car;
		return $this;
	}

	/**
	 * @return Fee[]
	 */
	public function getPricedEquipment()
	{
		return $this->pricedEquipment;
	}

	/**
	 * @param Fee[] $pricedEquipment
	 */
	public function setPricedEquipment($pricedEquipment)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->pricedEquipment = $pricedEquipment;
		return $this;
	}

	public function getDriverName()
	{
		return $this->getDriver()->getFullName();
	}

	public function setDriverName($name)
	{
		return $this->getDriver()->setFullName($name);
	}

	/** @param \Psr\Log\LoggerInterface */
	public function __construct($logger = null) {
		parent::__construct($logger);
		$this->pickup = new CarRentalPoint($this->logger);
		$this->pickup->prefix = 'pickup';
		$this->dropoff = new CarRentalPoint($this->logger);
		$this->dropoff->prefix = 'dropoff';
		$this->car = new Car(null);
		$this->driver = new Person(null, $this->logger);
	}

	public function convertToOldArrayFormat() {
		$result = [];
		$result['Kind'] = 'L';
		$result['Number'] = $this->getProviderDetails()->getConfirmationNumber();
		// TripNumber
        $result['TripNumber'] = $this->getProviderDetails()->getTripNumber();
		$result['Status'] = $this->getProviderDetails()->getStatus();
        $result['Cancelled'] = $this->getProviderDetails()->getCancelled();
		$result['ReservationDate'] = $this->getProviderDetails()->getReservationDate();
		$result['PickupDatetime'] = $this->getPickup()->getLocalDateTime();
		$result['PickupLocation'] = $this->getPickup()->getAddress();
		$result['DropoffDatetime'] = $this->getDropoff()->getLocalDateTime();
		$result['DropoffLocation'] = $this->getDropoff()->getAddress();
		$result['PickupPhone'] = $this->getPickup()->getPhone();
		$result['PickupFax'] = $this->getPickup()->getFax();
		$result['PickupHours'] = $this->getPickup()->getOpeningHours();
		$result['DropoffPhone'] = $this->getDropoff()->getPhone();
		$result['DropoffFax'] = $this->getDropoff()->getFax();
		$result['DropoffHours'] = $this->getDropoff()->getOpeningHours();
		$result['CarType'] = $this->getCar()->getType();
		$result['CarModel'] = $this->getCar()->getModel();
		$result['CarImageUrl'] = $this->getCar()->getImageUrl();
		$result['RenterName'] = $this->getDriver()->getFullName();
		$result['TotalCharge'] = $this->getTotalPrice()->getTotal();
		$result['Currency'] = $this->getTotalPrice()->getCurrencyCode();
		$result['TotalTaxAmount'] = $this->getTotalPrice()->getTax();
		$result['RentalCompany'] = $this->getProviderDetails()->getCompanyName();
        $result['SpentAwards'] = $this->getTotalPrice()->getSpentAwards();
        $result['EarnedAwards'] = $this->getTotalPrice()->getEarnedAwards();
		return $result;
	}

}