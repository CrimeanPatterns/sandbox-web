<?php

namespace AwardWallet\Common\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * Aircode
 *
 * @ORM\Table(name="AirCode")
 * @ORM\Entity()
 * @JMS\ExclusionPolicy("All")
 */
class Aircode
{
    /**
     * @var integer
     *
     * @ORM\Column(name="AirCodeID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $aircodeid;

    /**
     * @var string
     *
     * @ORM\Column(name="CityCode", type="string", length=3, nullable=false)
     */
    protected $citycode;

    /**
     * @var string
     *
     * @JMS\Expose()
     * @JMS\Groups({"basic"})
     *
     * @ORM\Column(name="AirCode", type="string", length=3, nullable=false)
     */
    protected $aircode;

    /**
     * @var string
     *
     * @JMS\Expose()
     * @JMS\Groups({"basic"})
     *
     * @ORM\Column(name="CityName", type="string", length=40, nullable=false)
     */
    protected $cityname;

    /**
     * @var string
     *
     * @ORM\Column(name="CountryCode", type="string", length=3, nullable=false)
     */
    protected $countrycode;

    /**
     * @var string
     *
     * @JMS\Expose()
     * @JMS\Groups({"basic"})
     *
     * @ORM\Column(name="CountryName", type="string", length=40, nullable=false)
     */
    protected $countryname;

    /**
     * @var string
     *
     * @ORM\Column(name="State", type="string", length=4, nullable=true)
     */
    protected $state;

    /**
     * @var string
     *
     * @ORM\Column(name="StateName", type="string", length=40, nullable=true)
     */
    protected $statename;

    /**
     * @var string
     *
     * @JMS\Expose()
     * @JMS\Groups({"basic"})
     *
     * @ORM\Column(name="AirName", type="string", length=80, nullable=false)
     */
    protected $airname;

    /**
     * @var float
     *
     * @ORM\Column(name="Lat", type="float", nullable=false)
     */
    protected $lat = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="Lng", type="float", nullable=false)
     */
    protected $lng = 0;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="LastUpdateDate", type="datetime", nullable=true)
     */
    protected $lastupdatedate;

    /**
     * @var string
     *
     * @ORM\Column(name="TimeZoneLocation", type="string", length=64, nullable=false)
     */
    protected $timeZoneLocation = 'UTC';

    /**
     * @ORM\Column(name="IcaoCode", type="string", length=4, nullable=true)
     */
    protected $icaoCode;

    /**
     * @ORM\Column(name="Fs", type="string", length=4, nullable=false)
     */
    protected $fs;

    /**
     * @ORM\Column(name="Faa", type="string", length=4, nullable=true)
     */
    protected $faa;

    /**
     * @ORM\Column(name="Classification", type="integer")
     */
    protected $classification;

    /**
     * @ORM\Column(name="Popularity", type="integer")
     */
    protected $popularity;

    /**
     * @ORM\Column(name="AddressLine", type="string")
     */
    protected $addressline;

    /**
     * Get aircodeid
     *
     * @return integer
     */
    public function getAircodeid()
    {
        return $this->aircodeid;
    }

    /**
     * Set citycode
     *
     * @param string $citycode
     * @return Aircode
     */
    public function setCitycode($citycode)
    {
        $this->citycode = $citycode;
    
        return $this;
    }

    /**
     * Get citycode
     *
     * @return string
     */
    public function getCitycode()
    {
        return $this->citycode;
    }

    /**
     * Set aircode
     *
     * @param string $aircode
     * @return Aircode
     */
    public function setAircode($aircode)
    {
        $this->aircode = $aircode;
    
        return $this;
    }

    /**
     * Get aircode
     *
     * @return string
     */
    public function getAircode()
    {
        return $this->aircode;
    }

    /**
     * Set cityname
     *
     * @param string $cityname
     * @return Aircode
     */
    public function setCityname($cityname)
    {
        $this->cityname = $cityname;
    
        return $this;
    }

    /**
     * Get cityname
     *
     * @return string
     */
    public function getCityname()
    {
        return $this->cityname;
    }

    /**
     * Set countrycode
     *
     * @param string $countrycode
     * @return Aircode
     */
    public function setCountrycode($countrycode)
    {
        $this->countrycode = $countrycode;
    
        return $this;
    }

    /**
     * Get countrycode
     *
     * @return string
     */
    public function getCountrycode()
    {
        return $this->countrycode;
    }

    /**
     * Set countryname
     *
     * @param string $countryname
     * @return Aircode
     */
    public function setCountryname($countryname)
    {
        $this->countryname = $countryname;
    
        return $this;
    }

    /**
     * Get countryname
     *
     * @return string
     */
    public function getCountryname()
    {
        return $this->countryname;
    }

    /**
     * Set state
     *
     * @param string $state
     * @return Aircode
     */
    public function setState($state)
    {
        $this->state = $state;
    
        return $this;
    }

    /**
     * Get state
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set statename
     *
     * @param string $statename
     * @return Aircode
     */
    public function setStatename($statename)
    {
        $this->statename = $statename;
    
        return $this;
    }

    /**
     * Get statename
     *
     * @return string
     */
    public function getStatename()
    {
        return $this->statename;
    }

    /**
     * Set airname
     *
     * @param string $airname
     * @return Aircode
     */
    public function setAirname($airname)
    {
        $this->airname = $airname;
    
        return $this;
    }

    /**
     * Get airname
     *
     * @return string
     */
    public function getAirname()
    {
        return $this->airname;
    }

    /**
     * Set lat
     *
     * @param integer $lat
     * @return Aircode
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
     * @return Aircode
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
     * Set lastupdatedate
     *
     * @param \DateTime $lastupdatedate
     * @return Aircode
     */
    public function setLastupdatedate($lastupdatedate)
    {
        $this->lastupdatedate = $lastupdatedate;
    
        return $this;
    }

    /**
     * Get lastupdatedate
     *
     * @return \DateTime
     */
    public function getLastupdatedate()
    {
        return $this->lastupdatedate;
    }

    /**
     * @return string
     */
    public function getIcaoCode()
    {
        return $this->icaoCode;
    }

    /**
     * @param string $icaoCode
     * @return $this
     */
    public function setIcaoCode($icaoCode)
    {
        $this->icaoCode = $icaoCode;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFs()
    {
        return $this->fs;
    }

    /**
     * @param mixed $fs
     * @return $this
     */
    public function setFs($fs)
    {
        $this->fs = $fs;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFaa()
    {
        return $this->faa;
    }

    /**
     * @param mixed $faa
     * @return $this
     */
    public function setFaa($faa)
    {
        $this->faa = $faa;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getClassification()
    {
        return $this->classification;
    }

    /**
     * @param mixed $classification
     * @return $this
     */
    public function setClassification($classification)
    {
        $this->classification = $classification;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPopularity()
    {
        return $this->popularity;
    }

    /**
     * @param mixed $popularity
     * @return $this
     */
    public function setPopularuty($popularity)
    {
        $this->popularity = $popularity;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAddressline()
    {
        return $this->addressline;
    }

    /**
     * @param mixed $addressline
     * @return $this
     */
    public function setAddressline($addressline)
    {
        $this->addressline = $addressline;
        return $this;
    }

    /**
     * @return string
     */
    public function getFormattedName()
    {
        $formattedName = "$this->airname ($this->cityname)";
        if (!empty($this->state)) {
            $formattedName .= ", $this->state";
        }
        $formattedName .= ", $this->countryname";
        return $formattedName;
    }

    public function getAirportName(bool $withCode = false)
    {
        $parts = $withCode ? [$this->aircode] : [];
        $parts[] = $this->getCityname();

        if ($this->getCountryCode() === 'US') {
            // us format: New York, NY
            $state = $this->getState();
            if ($this->getCityname() !== $state && !is_numeric($state)) {
                $parts[] = $state;
            }
            if (empty($state)) {
                $parts[] = $this->getCountryname();
            }
        } else {
            // overseas format: Vienna, Austria
            $country = $this->getCountryname();
            if (!empty($country)) {
                $parts[] = $country;
            }
        }

        $parts = array_filter($parts, function ($val) {
            return !empty($val);
        });

        if (count($parts) <= 1) {
            $parts[] = $this->getAirname();
            $parts = array_unique($parts);
        }

        return implode(", ", $parts);
    }

    public function haveFlightHistory() : bool
    {
        return $this->classification <= 4;
    }
}
