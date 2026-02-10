<?php

namespace AwardWallet\MainBundle\Service\Itinerary;

trait CarRentalPointsShortMethods {

	/**
	 * @return string
	 */
	public function getPickupAddress()
	{
		return $this->getPickup()->getAddress();
	}

	public function setPickupAddress($address)
	{
		return $this->getPickup()->setAddress($address);
	}

	/**
	 * @return string
	 */
	public function getPickupLocalDateTime()
	{
		return $this->getPickup()->getLocalDateTime();
	}

	/**
	 * @param string $localDateTime
	 */
	public function setPickupLocalDateTime($localDateTime)
	{
		return $this->getPickup()->setLocalDateTime($localDateTime);
	}

	/**
	 * @return string
	 */
	public function getPickupOpeningHours()
	{
		return $this->getPickup()->getOpeningHours();
	}

	/**
	 * @param string $openingHours
	 */
	public function setPickupOpeningHours($openingHours)
	{
		return $this->getPickup()->setOpeningHours($openingHours);
	}

	/**
	 * @return string
	 */
	public function getPickupPhone()
	{
		return $this->getPickup()->getPhone();
	}

	/**
	 * @param string $phone
	 */
	public function setPickupPhone($phone)
	{
		return $this->getPickup()->setPhone($phone);
	}

	/**
	 * @return string
	 */
	public function getPickupFax()
	{
		return $this->getPickup()->getFax();
	}

	/**
	 * @param string $fax
	 */
	public function setPickupFax($fax)
	{
		return $this->getPickup()->setFax($fax);
	}

	/**
	 * @return string
	 */
	public function getDropoffAddress()
	{
		return $this->getDropoff()->getAddress();
	}

	public function setDropoffAddress($address)
	{
		return $this->getDropoff()->setAddress($address);
	}

	/**
	 * @return string
	 */
	public function getDropoffLocalDateTime()
	{
		return $this->getDropoff()->getLocalDateTime();
	}

	/**
	 * @param string $localDateTime
	 */
	public function setDropoffLocalDateTime($localDateTime)
	{
		return $this->getDropoff()->setLocalDateTime($localDateTime);
	}

	/**
	 * @return string
	 */
	public function getDropoffOpeningHours()
	{
		return $this->getDropoff()->getOpeningHours();
	}

	/**
	 * @param string $openingHours
	 */
	public function setDropoffOpeningHours($openingHours)
	{
		return $this->getDropoff()->setOpeningHours($openingHours);
	}

	/**
	 * @return string
	 */
	public function getDropoffPhone()
	{
		return $this->getDropoff()->getPhone();
	}

	/**
	 * @param string $phone
	 */
	public function setDropoffPhone($phone)
	{
		return $this->getDropoff()->setPhone($phone);
	}

	/**
	 * @return string
	 */
	public function getDropoffFax()
	{
		return $this->getDropoff()->getFax();
	}

	/**
	 * @param string $fax
	 */
	public function setDropoffFax($fax)
	{
		return $this->getDropoff()->setFax($fax);
	}
	
}