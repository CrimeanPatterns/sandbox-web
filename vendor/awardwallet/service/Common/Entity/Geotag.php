<?php

namespace AwardWallet\Common\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * Geotag
 *
 * @ORM\Table(name="GeoTag")
 * @ORM\Entity
 * @JMS\ExclusionPolicy("all")
 */
class Geotag
{
    /**
     * @var integer
     *
     * @ORM\Column(name="GeoTagID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $geotagid;

    /**
     * @var string
     *
     * @ORM\Column(name="Address", type="string", length=250, nullable=false)
     */
    protected $address;

    /**
     * @var float
     *
     * @ORM\Column(name="Lat", type="float", nullable=true)
     */
    protected $lat;

    /**
     * @var float
     *
     * @ORM\Column(name="Lng", type="float", nullable=true)
     */
    protected $lng;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="UpdateDate", type="datetime", nullable=false)
     */
    protected $updatedate;

    /**
     * @var string
     *
     * @ORM\Column(name="FoundAddress", type="string", length=250, nullable=true)
     *
     * @JMS\Expose()
     */
    protected $foundaddress;

    /**
     * @var string
     *
     * @ORM\Column(name="TimeZoneLocation", type="string", length=64, nullable=false)
     * @JMS\Expose()
     */
    protected $timeZoneLocation = 'UTC';

    /**
     * @var string
     *
     * @ORM\Column(name="AddressLine", type="string", length=250, nullable=true)
     */
    protected $addressline;

    /**
     * @var string
     *
     * @ORM\Column(name="City", type="string", length=250, nullable=true)
     */
    protected $city;

    /**
     * @var string
     *
     * @ORM\Column(name="State", type="string", length=250, nullable=true)
     */
    protected $state;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="StateCode", type="string", length=80, nullable=true)
	 */
	protected $stateCode;

    /**
     * @var string
     *
     * @ORM\Column(name="Country", type="string", length=250, nullable=true)
     */
    protected $country;

    /**
     * @var string
     *
     * @ORM\Column(name="CountryCode", type="string", length=80, nullable=true)
     */
    protected $countryCode;

    /**
     * @var string
     *
     * @ORM\Column(name="PostalCode", type="string", length=250, nullable=true)
     */
    protected $postalcode;

    /**
     * @var string
     *
     * @ORM\Column(name="HostName", type="string", length=20, nullable=true)
     */
    protected $hostname;

    /**
     * Get geotagid
     *
     * @return integer 
     */
    public function getGeotagid()
    {
        return $this->geotagid;
    }

    /**
     * Set address
     *
     * @param string $address
     * @return Geotag
     */
    public function setAddress($address)
    {
        $this->address = $address;
    
        return $this;
    }

    /**
     * Get address
     *
     * @return string 
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set lat
     *
     * @param float $lat
     * @return Geotag
     */
    public function setLat($lat)
    {
        $this->lat = $lat;
    
        return $this;
    }

    /**
     * Get lat
     *
     * @return float 
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * Set lng
     *
     * @param float $lng
     * @return Geotag
     */
    public function setLng($lng)
    {
        $this->lng = $lng;
    
        return $this;
    }

    /**
     * Get lng
     *
     * @return float 
     */
    public function getLng()
    {
        return $this->lng;
    }

    /**
     * Set updatedate
     *
     * @param \DateTime $updatedate
     * @return Geotag
     */
    public function setUpdatedate($updatedate)
    {
        $this->updatedate = $updatedate;
    
        return $this;
    }

    /**
     * Get updatedate
     *
     * @return \DateTime 
     */
    public function getUpdatedate()
    {
        return $this->updatedate;
    }

    /**
     * Set foundaddress
     *
     * @param string $foundaddress
     * @return Geotag
     */
    public function setFoundaddress($foundaddress)
    {
        $this->foundaddress = $foundaddress;
    
        return $this;
    }

    /**
     * Get foundaddress
     *
     * @return string 
     */
    public function getFoundaddress()
    {
        return $this->foundaddress;
    }

    public function getTimeZoneLocation(): string
    {
        return $this->timeZoneLocation;
    }

    public function setTimeZoneLocation(string $timeZoneLocation): self
    {
        $this->timeZoneLocation = $timeZoneLocation;

        return $this;
    }

    public function getDateTimeZone(): \DateTimeZone
    {
        try {
            return new \DateTimeZone($this->getTimeZoneLocation());
        } catch (\Exception $e) {
            return new \DateTimeZone('UTC');
        }
    }

    /**
     * Set addressline
     *
     * @param string $addressline
     * @return Geotag
     */
    public function setAddressline($addressline)
    {
        $this->addressline = $addressline;
    
        return $this;
    }

    /**
     * Get addressline
     *
     * @return string 
     */
    public function getAddressline()
    {
        return $this->addressline;
    }

    /**
     * Set city
     *
     * @param string $city
     * @return Geotag
     */
    public function setCity($city)
    {
        $this->city = $city;
    
        return $this;
    }

    /**
     * Get city
     *
     * @return string 
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set state
     *
     * @param string $state
     * @return Geotag
     */
    public function setState($state)
    {
        $this->state = $state;
    
        return $this;
    }

    /**
     * @param string|null $stateCode
     */
    public function setStateCode($stateCode)
    {
        $this->stateCode = $stateCode;
    }

    /**
     * Get state
     *
     * @return string 
     */
    public function getState($short = false)
    {
		if($short && !empty($this->stateCode))
			return $this->stateCode;
		else
        	return $this->state;
    }

    /**
     * Set country
     *
     * @param string $country
     * @return Geotag
     */
    public function setCountry($country)
    {
        $this->country = $country;
    
        return $this;
    }

    /**
     * Get country
     *
     * @return string 
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set postalcode
     *
     * @param string $postalcode
     * @return Geotag
     */
    public function setPostalcode($postalcode)
    {
        $this->postalcode = $postalcode;
    
        return $this;
    }

    /**
     * Get postalcode
     *
     * @return string 
     */
    public function getPostalcode()
    {
        return $this->postalcode;
    }

    /**
     * Set hostname
     *
     * @param string $hostname
     * @return Geotag
     */
    public function setHostname($hostname)
    {
        $this->hostname = $hostname;
    
        return $this;
    }

    /**
     * Get hostname
     *
     * @return string 
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    public function getLocalDateTime(\DateTime $date)
    {
        return new \DateTime($date->format('Y-m-d H:i:s'), $this->getDateTimeZone());
    }

    /**
     * @param \DateTime $date
     * @param Geotag $geotag
     * @return \DateTime
     */
    public static function getLocalDateTimeByGeoTag(\DateTime $date = null, ?Geotag $geotag){
        if (empty($date)) {
            return null;
        }
        if (!empty($geotag)) {
            return $geotag->getLocalDateTime($date);
        } else {
            return $date;
        }
    }
    
    public function distanceFrom(Geotag $geotag){
        if($geotag->getLat() === null || $geotag->getLng() === null || $this->lat === null || $this->lng === null)
            return PHP_INT_MAX;
        if($geotag->getLat() == $this->getLat() && $geotag->getLng() == $this->getLng())
            return 0;

        $R = 3950;
        $srcLat = deg2rad($geotag->getLat());
        $srcLng = deg2rad($geotag->getLng());
        $dstLat = deg2rad($this->lat);
        $dstLng = deg2rad($this->lng);
        return acos(sin($srcLat) * sin($dstLat) + cos($srcLat) * cos($dstLat) * cos($dstLng - $srcLng)) * $R;
    }

    function getDMSformat()
    {
        return sprintf('%s, %s', $this->getLat(), $this->getLng());
    }

    /**
     * @param string $countryCode
     * @return Geotag
     */
    public function setCountryCode($countryCode)
    {
        $this->countryCode = $countryCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getCountryCode(){
        return $this->countryCode;
    }

    /**
     * @param \DateTime|null $date
     *
     * @return string
     *
     * @JMS\VirtualProperty()
     */
    public function getTimeZoneAbbreviation(?\DateTime $date = null)
    {
        $timeZone = $this->getDateTimeZone();
        $tzName = $timeZone->getName();
        $abbreviation = empty($date)
            ? (new \DateTime(null, $timeZone))->format('T')
            : $date->setTimezone($timeZone)->format('T');
        if (preg_match('/^[a-z]+$/i', $abbreviation)) {
            return $abbreviation;
        }
        $transitions = $timeZone->getTransitions(time());
        if (!empty($transitions)) {
            $dst = $transitions[0]['isdst'];
            $offset = $transitions[0]['offset'];
        } else {
            $dst = null;
            $offset = null;
        }
        $abbreviationsFromList = [];
        foreach (\DateTimeZone::listAbbreviations() as $nextAbbreviation => $entries) {
            foreach ($entries as $entry) {
                if ($tzName === $entry['timezone_id'] && $entry['offset'] === $offset && $entry['dst'] === $dst) {
                    $abbreviationsFromList[] = $nextAbbreviation;
                    continue 2;
                }
            }
        }
        if (1 === count($abbreviationsFromList)) {
            return $abbreviationsFromList[0];
        }

        return $abbreviation;
    }
}