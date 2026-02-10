<?php


namespace AwardWallet\Common\FlightStats;

use JMS\Serializer\Annotation as JMS;


class Airline
{
    /**
     * The FlightStats code for the carrier, globally unique across time.
     *
     * @var string
     * @JMS\Type("string")
     */
    private $fs;

    /**
     * The IATA code for the carrier.
     *
     * @var string = null
     * @JMS\Type("string")
     */
    private $iata = null;

    /**
     * The ICAO code for the carrier.
     *
     * @var string = null
     * @JMS\Type("string")
     */
    private $icao = null;

    /**
     * The name of the carrier.
     *
     * @var string
     * @JMS\Type("string")
     */
    private $name;

    /**
     * The primary customer service phone number for the carrier.
     *
     * @var string = null
     * @JMS\Type("string")
     */
    private $phoneNumber;

    /**
     * Boolean value indicating if the airline is currently active.
     *
     * @var bool
     * @JMS\Type("boolean")
     */
    private $active;

    /**
     * Airline constructor.
     * @param string $fs
     * @param string|null $iata
     * @param string|null $icao
     * @param string $name
     * @param string $phoneNumber
     * @param bool $active
     */
    public function __construct($fs, $iata = null, $icao = null, $name, $phoneNumber, $active)
    {
        $this->fs = $fs;
        $this->iata = $iata;
        $this->icao = $icao;
        $this->name = $name;
        $this->phoneNumber = $phoneNumber;
        $this->active = $active;
    }

    /**
     * The FlightStats code for the carrier, globally unique across time.
     *
     * @return string
     */
    public function getFs()
    {
        return $this->fs;
    }

    /**
     * The IATA code for the carrier.
     *
     * @return string
     */
    public function getIata()
    {
        return $this->iata;
    }

    /**
     * The ICAO code for the carrier.
     *
     * @return string
     */
    public function getIcao()
    {
        return $this->icao;
    }

    /**
     * The name of the carrier.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * The primary customer service phone number for the carrier.
     *
     * @return string
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    /**
     * Boolean value indicating if the airline is currently active.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param string $iata
     * @return Airline
     */
    public function setIata(string $iata): Airline
    {
        $this->iata = $iata;
        return $this;
    }

    /**
     * @param string $icao
     * @return Airline
     */
    public function setIcao(string $icao): Airline
    {
        $this->icao = $icao;
        return $this;
    }

    /**
     * @param string $name
     * @return Airline
     */
    public function setName(string $name): Airline
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param string $phoneNumber
     * @return Airline
     */
    public function setPhoneNumber(string $phoneNumber): Airline
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    /**
     * @param bool $active
     * @return Airline
     */
    public function setActive(bool $active): Airline
    {
        $this->active = $active;
        return $this;
    }


}