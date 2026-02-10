<?php

namespace AwardWallet\MainBundle\Service\Itinerary;

trait AddressShortMethods
{
//	/**
//	 * @param string $addressLine
//	 */
//	public function setAddressLine($addressLine)
//	{
//		$this->logPropertySetting(__METHOD__, func_get_args());
//		$this->addressLine = $addressLine;
//		return $this;
//	}
//
//	/**
//	 * @return string
//	 */
//	public function getAddressLine()
//	{
//		return $this->addressLine;
//	}
//
//	/**
//	 * @param string $airportCode
//	 */
//	public function setAirportCode($airportCode)
//	{
//		$this->logPropertySetting(__METHOD__, func_get_args());
//		$this->airportCode = $airportCode;
//		return $this;
//	}
//
//	/**
//	 * @return string
//	 */
//	public function getAirportCode()
//	{
//		return $this->airportCode;
//	}
//
//	/**
//	 * @param string $city
//	 */
//	public function setCity($city)
//	{
//		$this->logPropertySetting(__METHOD__, func_get_args());
//		$this->city = $city;
//		return $this;
//	}
//
//	/**
//	 * @return string
//	 */
//	public function getCity()
//	{
//		return $this->city;
//	}
//
//	/**
//	 * @param string $countryName
//	 */
//	public function setCountryName($countryName)
//	{
//		$this->logPropertySetting(__METHOD__, func_get_args());
//		$this->countryName = $countryName;
//		return $this;
//	}
//
//	/**
//	 * @return string
//	 */
//	public function getCountryName()
//	{
//		return $this->countryName;
//	}
//
//	/**
//	 * @param string $lat
//	 */
//	public function setLat($lat)
//	{
//		$this->logPropertySetting(__METHOD__, func_get_args());
//		$this->lat = $lat;
//		return $this;
//	}
//
//	/**
//	 * @return string
//	 */
//	public function getLat()
//	{
//		return $this->lat;
//	}
//
//	/**
//	 * @param string $lng
//	 */
//	public function setLng($lng)
//	{
//		$this->logPropertySetting(__METHOD__, func_get_args());
//		$this->lng = $lng;
//		return $this;
//	}
//
//	/**
//	 * @return string
//	 */
//	public function getLng()
//	{
//		return $this->lng;
//	}
//
//	/**
//	 * @param string $postalCode
//	 */
//	public function setPostalCode($postalCode)
//	{
//		$this->logPropertySetting(__METHOD__, func_get_args());
//		$this->postalCode = $postalCode;
//		return $this;
//	}
//
//	/**
//	 * @return string
//	 */
//	public function getPostalCode()
//	{
//		return $this->postalCode;
//	}
//
//	/**
//	 * @param string $stateName
//	 */
//	public function setStateName($stateName)
//	{
//		$this->logPropertySetting(__METHOD__, func_get_args());
//		$this->stateName = $stateName;
//		return $this;
//	}
//
//	/**
//	 * @return string
//	 */
//	public function getStateName()
//	{
//		return $this->stateName;
//	}

	/**
	 * @param string $text
	 */
	public function setAddressText($text)
	{
		return $this->getAddress()->setText($text);
	}

	/**
	 * @return string
	 */
	public function getAddressText()
	{
		return $this->getAddress()->getText();
	}

//	/**
//	 * @param string $timezone
//	 */
//	public function setTimezone($timezone)
//	{
//		$this->logPropertySetting(__METHOD__, func_get_args());
//		$this->timezone = $timezone;
//		return $this;
//	}
//
//	/**
//	 * @return string
//	 */
//	public function getTimezone()
//	{
//		return $this->timezone;
//	}

}
