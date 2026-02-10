<?php


namespace AwardWallet\Common\FlightStats;

use JMS\Serializer\Annotation as JMS;


class ScheduledFlight
{
    /**
     * The FlightStats unique code for the operating carrier to use as a reference for finding the entry in the appendix
     * (unless the extended option to include inlined references is used).
     *
     * @var string = null
     * @JMS\Type("string")
     */
    private $carrierFsCode = null;

    /**
     * @var Airline
     */
    private $carrier = null;

    /**
     * The flight identification number and any additional characters
     *
     * @var string
     * @JMS\Type("string")
     */
    private $flightNumber;

    /**
     * The FlightStats unique code for the departure airport to use as a reference for finding the entry in the appendix
     * (unless the extended option to include inlined references is used).
     *
     * @var string = null
     * @JMS\Type("string")
     */
    private $departureAirportFsCode = null;

    /**
     * @var Airport|null
     */
    private $departureAirport = null;

    /**
     * The FlightStats unique code for the arrival airport to use as a reference for finding the entry in the appendix
     * (unless the extended option to include inlined references is used).
     *
     * @var string = null
     * @JMS\Type("string")
     */
    private $arrivalAirportFsCode = null;

    /**
     * @var Airport|null
     */
    private $arrivalAirport = null;

    /**
     * The number of stops between the departure and arrival airport.
     * This will always be 0 unless the extended option "includeDirects" is specified.
     *
     * @var int
     * @JMS\Type("integer")
     */
    private $stops;

    /**
     * The terminal from which the flight departed or will depart.
     *
     * @var string = null
     * @JMS\Type("string")
     */
    private $departureTerminal = null;

    /**
     * The terminal into which the flight arrived or will arrive.
     *
     * @var string = null
     * @JMS\Type("string")
     */
    private $arrivalTerminal = null;

    /**
     * The published departure time (local to the airport) for the flight provided by the airline's operating schedule.
     *
     * @var string
     * @JMS\Type("string")
     */
    private $departureTime;

    /**
     * The published arrival time (local to the airport) for the flight provided by the airline's operating schedule.
     *
     * @var string
     * @JMS\Type("string")
     */
    private $arrivalTime;

    /**
     * The IATA code for the equipment scheduled to be flown to use as a reference for finding the entry in the appendix
     * (unless the extended option to include inlined references is used)
     *
     * @var string
     * @JMS\Type("string")
     */
    private $flightEquipmentIataCode;

    /**
     * Boolean value indicating if the marketed flight is a codeshare.
     *
     * @var bool
     * @JMS\Type("boolean")
     */
    private $isCodeshare;

    /**
     * Boolean value indicating if the marketed flight is a wetlease.
     *
     * @var bool
     * @JMS\Type("boolean")
     */
    private $isWetlease;

    /**
     * The type of service offered for the flight
     *
     * @var string
     * @JMS\Type("string")
     */
    private $serviceType;

    /**
     * IATA service classes offered for the flight.
     *
     * @var array = null
     * @JMS\Type("array<string>")
     */
    private $serviceClasses = null;

    /**
     * IATA restrictions imposed on the flight.
     *
     * @var array = null
     * @JMS\Type("array<string>")
     */
    private $trafficRestrictions = null;

    /**
     * @var Operator
     * @JMS\Type("AwardWallet\Common\FlightStats\Operator")
     */
    private $operator;

    /**
     * TODO добавить класс
     * Any codeshares for this operated flight. Codeshares are only possible if this flight leg is not itself a codeshare
     * (the codeshare field must be false).
     *
     * @var Codeshare[]
     * @JMS\Exclude()
     * TODO: JMS\Type("array<AwardWallet\Common\FlightStats\Codeshare>")
     */
    private $codeshares = null;

    /**
     * Reference code for FlightStats' troubleshooting purposes.
     *
     * @var string
     * @JMS\Type("string")
     */
    private $referenceCode;

    /**
     * @var \DateTimeZone = null
     */
    private $departureTimeZone = null;

    /**
     * @var \DateTimeZone = null
     */
    private $arrivalTimeZone = null;

    /**
     * ScheduledFlight constructor.
     * @param string|null $carrierFsCode
     * @param string $flightNumber
     * @param string|null $departureAirportFsCode
     * @param string|null $arrivalAirportFsCode
     * @param int $stops
     * @param string|null $departureTerminal
     * @param string|null $arrivalTerminal
     * @param string $departureTime
     * @param string $arrivalTime
     * @param array $flightEquipmentIataCode
     * @param bool $isCodeshare
     * @param bool $isWetlease
     * @param string $serviceType
     * @param array|null $serviceClasses
     * @param array|null $trafficRestrictions
     * @param array|null $codeshares
     * @param string $referenceCode
     * @param \DateTimeZone $departureTimeZone
     * @param \DateTimeZone $arrivalTimeZone
     */
    public function __construct(
        $carrierFsCode = null,
        $flightNumber,
        $departureAirportFsCode = null,
        $arrivalAirportFsCode = null,
        $stops,
        $departureTerminal = null,
        $arrivalTerminal = null,
        $departureTime,
        $arrivalTime,
        $flightEquipmentIataCode,
        $isCodeshare,
        $isWetlease,
        $serviceType,
        array $serviceClasses = null,
        array $trafficRestrictions = null,
        array $codeshares = null,
        $referenceCode,
        \DateTimeZone $departureTimeZone = null,
        \DateTimeZone $arrivalTimeZone = null
    ) {
        $this->carrierFsCode = $carrierFsCode;
        $this->flightNumber = $flightNumber;
        $this->departureAirportFsCode = $departureAirportFsCode;
        $this->arrivalAirportFsCode = $arrivalAirportFsCode;
        $this->stops = $stops;
        $this->departureTerminal = $departureTerminal;
        $this->arrivalTerminal = $arrivalTerminal;
        $this->departureTime = $departureTime;
        $this->arrivalTime = $arrivalTime;
        $this->flightEquipmentIataCode = $flightEquipmentIataCode;
        $this->isCodeshare = $isCodeshare;
        $this->isWetlease = $isWetlease;
        $this->serviceType = $serviceType;
        $this->serviceClasses = $serviceClasses;
        $this->trafficRestrictions = $trafficRestrictions;
        $this->codeshares = $codeshares;
        $this->referenceCode = $referenceCode;
        $this->departureTimeZone = $departureTimeZone;
        $this->arrivalTimeZone = $arrivalTimeZone;
    }

    /**
     * The FlightStats unique code for the operating carrier to use as a reference for finding the entry in the appendix
     * (unless the extended option to include inlined references is used).
     *
     * @return string
     */
    public function getCarrierFsCode()
    {
        return $this->carrierFsCode;
    }

    public function getCarrier()
    {
        return $this->carrier;
    }

    /**
     * @param Airline $carrier
     * @return ScheduledFlight
     */
    public function setCarrier(Airline $carrier)
    {
        $this->carrier = $carrier;
        return $this;
    }

    /**
     * The flight identification number and any additional characters
     *
     * @return string
     */
    public function getFlightNumber()
    {
        return $this->flightNumber;
    }

    /**
     * The FlightStats unique code for the departure airport to use as a reference for finding the entry in the appendix
     * (unless the extended option to include inlined references is used).
     *
     * @return string
     */
    public function getDepartureAirportFsCode()
    {
        return $this->departureAirportFsCode;
    }

    /**
     * The FlightStats unique code for the arrival airport to use as a reference for finding the entry in the appendix
     * (unless the extended option to include inlined references is used).
     *
     * @return string
     */
    public function getArrivalAirportFsCode()
    {
        return $this->arrivalAirportFsCode;
    }

    /**
     * The number of stops between the departure and arrival airport.
     * This will always be 0 unless the extended option "includeDirects" is specified.
     *
     * @return int
     */
    public function getStops()
    {
        return $this->stops;
    }

    /**
     * The terminal from which the flight departed or will depart.
     *
     * @return string
     */
    public function getDepartureTerminal()
    {
        return $this->departureTerminal;
    }

    /**
     * The terminal into which the flight arrived or will arrive.
     *
     * @return string
     */
    public function getArrivalTerminal()
    {
        return $this->arrivalTerminal;
    }

    /**
     * The published departure time (local to the airport) for the flight provided by the airline's operating schedule.
     *
     * @return string
     */
    public function getDepartureTime()
    {
        return $this->departureTime;
    }

    /**
     * The published arrival time (local to the airport) for the flight provided by the airline's operating schedule.
     *
     * @return string
     */
    public function getArrivalTime()
    {
        return $this->arrivalTime;
    }

    /**
     * The IATA code for the equipment scheduled to be flown to use as a reference for finding the entry in the appendix
     * (unless the extended option to include inlined references is used)
     *
     * @return string
     */
    public function getFlightEquipmentIataCode()
    {
        return $this->flightEquipmentIataCode;
    }

    /**
     * Boolean value indicating if the marketed flight is a codeshare.
     *
     * @return bool
     */
    public function isIsCodeshare()
    {
        return $this->isCodeshare;
    }

    /**
     * Boolean value indicating if the marketed flight is a wetlease.
     *
     * @return bool
     */
    public function isIsWetlease()
    {
        return $this->isWetlease;
    }

    /**
     * The type of service offered for the flight
     *
     * @return string
     */
    public function getServiceType()
    {
        return $this->serviceType;
    }

    /**
     * IATA service classes offered for the flight.
     *
     * @return array
     */
    public function getServiceClasses()
    {
        return $this->serviceClasses;
    }

    /**
     * IATA restrictions imposed on the flight.
     *
     * @return array
     */
    public function getTrafficRestrictions()
    {
        return $this->trafficRestrictions;
    }

    /**
     * @return Operator|null
     */
    public function getOperator(): ?Operator
    {
        return $this->operator;
    }

    /**
     * Any codeshares for this operated flight. Codeshares are only possible if this flight leg is not itself a codeshare
     * (the codeshare field must be false).
     *
     * @return Codeshare[]
     */
    public function getCodeshares()
    {
        return $this->codeshares;
    }

    /**
     * Reference code for FlightStats' troubleshooting purposes.
     *
     * @return string
     */
    public function getReferenceCode()
    {
        return $this->referenceCode;
    }

    /**
     * @return Airport|null
     */
    public function getDepartureAirport()
    {
        return $this->departureAirport;
    }

    /**
     * @param Airport|null $departureAirport
     */
    public function setDepartureAirport(Airport $departureAirport)
    {
        $this->departureAirport = $departureAirport;
    }

    /**
     * @return Airport|null
     */
    public function getArrivalAirport()
    {
        return $this->arrivalAirport;
    }

    /**
     * @param Airport|null $arrivalAirport
     */
    public function setArrivalAirport(Airport $arrivalAirport)
    {
        $this->arrivalAirport = $arrivalAirport;
    }
}