<?php

namespace AwardWallet\MainBundle\Service\Itinerary;

class Address {

	use Loggable;

	/**
	 * @var string
	 */
	protected $text;

	/**
	 * @var string
	 */
	protected $addressLine;

	/**
	 * @var string
	 */
	protected $city;

	/**
	 * @var string
	 */
	protected $stateName;

	/**
	 * @var string
	 */
	protected $countryName;

	/**
	 * @var string
	 */
	protected $postalCode;

	/**
	 * @var string
	 */
	protected $lat;

	/**
	 * @var string
	 */
	protected $lng;

	/**
	 * @var string
	 */
	protected $airportCode;

	/**
	 * @var string
	 */
	protected $timezone;

	/**
	 * @param string $addressLine
	 */
	public function setAddressLine($addressLine)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->addressLine = $addressLine;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getAddressLine()
	{
		return $this->addressLine;
	}

	/**
	 * @param string $airportCode
	 */
	public function setAirportCode($airportCode)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->airportCode = $airportCode;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getAirportCode()
	{
		return $this->airportCode;
	}

	/**
	 * @param string $city
	 */
	public function setCity($city)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->city = $city;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCity()
	{
		return $this->city;
	}

	/**
	 * @param string $countryName
	 */
	public function setCountryName($countryName)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->countryName = $countryName;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCountryName()
	{
		return $this->countryName;
	}

	/**
	 * @param string $lat
	 */
	public function setLat($lat)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->lat = $lat;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getLat()
	{
		return $this->lat;
	}

	/**
	 * @param string $lng
	 */
	public function setLng($lng)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->lng = $lng;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getLng()
	{
		return $this->lng;
	}

	/**
	 * @param string $postalCode
	 */
	public function setPostalCode($postalCode)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->postalCode = $postalCode;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getPostalCode()
	{
		return $this->postalCode;
	}

	/**
	 * @param string $stateName
	 */
	public function setStateName($stateName)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->stateName = $stateName;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getStateName()
	{
		return $this->stateName;
	}

	/**
	 * @param string $text
	 */
	public function setText($text)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->text = $text;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getText()
	{
		return $this->text;
	}

	/**
	 * @param string $timezone
	 */
	public function setTimezone($timezone)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->timezone = $timezone;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTimezone()
	{
		return $this->timezone;
	}

	public function __construct($text = null, $airportCode = null){
		$this->text = $text;
		$this->airportCode = $airportCode;
//		if(isset($text))
//			$this->parseText($text, $airportCode);
	}

	public function parseText($text, $airportCode = null){
		$this->text = $text;
		// remove duplicate city entries (City, 1234 US City)
		if (preg_match('/^([^\s,]+)(.+)$/', $text, $m) && strlen($m[1]) > 4 && stripos($m[2], $m[1]) !== false)
			$text = trim($m[2], ', ');
		// gonna see how this works out
		if (!isset($airportCode) && preg_match('/^(?<code>[A-Z]{3})\b/', $text, $matches))
			$airportCode = $matches['code'];
		if(GoogleGeoTagLimitOk()){
			$detailedAddress = FindGeoTag(empty($airportCode) ? $text : $airportCode);
			if(isset($detailedAddress['Lat'])){
				$this->lat = round($detailedAddress['Lat'], 7);
				$this->lng = round($detailedAddress['Lng'], 7);
				if (isset($detailedAddress['City']))
					$this->city = $detailedAddress['City'];
				if (isset($detailedAddress['AddressLine']))
					$this->addressLine = $detailedAddress['AddressLine'];
				if (isset($detailedAddress['State']))
					$this->stateName = $detailedAddress['State'];
				if (isset($detailedAddress['Country']))
					$this->countryName = $detailedAddress['Country'];
				if(!empty($detailedAddress['PostalCode']))
					$this->postalCode = $detailedAddress['PostalCode'];
				if (isset($detailedAddress['TimeZoneLocation'])) {
                    try {
                        $tz = new \DateTimeZone($detailedAddress['TimeZoneLocation']);
                        $offset = $tz->getOffset(new \DateTime());
                    }
                    catch(\Exception $e) {
                        $offset = 0;
                    }
                    $this->timezone = $offset;
                }
				// extract addressLine from weird addressed like 'Via Vittorio Bragadin Snc Fiumicino, Italy'
				if (empty($this->addressLine))
					$this->parseAddressLine();
			}
		}
		if(!empty($airportCode))
			$this->airportCode = $airportCode;
	}

	protected function parseAddressLine() {
		$pos = null;
		foreach (["city", "stateName", "countryName"] as $field)
			if (!empty($this->$field)) {
				$find = strrpos($this->text, $this->$field);
				if ($find !== false && (!isset($pos) || $pos > $find))
					$pos = $find;
			}
		if (isset($pos))
			$line = trim(substr($this->text, 0, $pos));
		if (!empty($line))
			$this->addressLine = $line;
	}

}
