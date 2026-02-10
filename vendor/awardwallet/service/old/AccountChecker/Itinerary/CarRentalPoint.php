<?php

namespace AwardWallet\MainBundle\Service\Itinerary;

class CarRentalPoint {

	use Loggable;

	/**
	 * @var string
	 */
	protected $address;

	/**
	 * @var string
	 */
	protected $localDateTime;

	/**
	 * @var string
	 */
	protected $openingHours;

	/**
	 * @var string
	 */
	protected $phone;

	/**
	 * @var string
	 */
	protected $fax;

	/**
	 * @return string
	 */
	public function getAddress()
	{
		return $this->address;
	}

	/**
	 * @param string $address
	 */
	public function setAddress($address)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->address = $address;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getLocalDateTime()
	{
		return $this->localDateTime;
	}

	/**
	 * @param string $localDateTime
	 */
	public function setLocalDateTime($localDateTime)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->localDateTime = $localDateTime;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getOpeningHours()
	{
		return $this->openingHours;
	}

	/**
	 * @param string $openingHours
	 */
	public function setOpeningHours($openingHours)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->openingHours = $openingHours;
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
	public function getFax()
	{
		return $this->fax;
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

	public function __construct($logger = null) {
		$this->logger = $logger;
	}

}