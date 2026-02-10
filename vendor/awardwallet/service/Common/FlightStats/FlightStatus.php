<?php


namespace AwardWallet\Common\FlightStats;

use JMS\Serializer\Annotation as JMS;

class FlightStatus
{

    /**
     * @var integer
     * @JMS\Type("integer")
     */
    private $flightId;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $carrierFsCode;

    /**
     * @var Airline
     */
    private $carrier;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $operatingCarrierFsCode;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $primaryCarrierFsCode;

    /**
     * @var Airline
     */
    private $primaryCarrier;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $flightNumber;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $departureAirportFsCode;

    /**
     * @var Airport
     */
    private $departureAirport;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $arrivalAirportFsCode;

    /**
     * @var Airport
     */
    private $arrivalAirport;

    /**
     * @var DualDate
     * @JMS\Type("AwardWallet\Common\FlightStats\DualDate")
     */
    private $departureDate;

    /**
     * @var DualDate
     * @JMS\Type("AwardWallet\Common\FlightStats\DualDate")
     */
    private $arrivalDate;

    /**
     * @var FlightEquipment
     * @JMS\Type("AwardWallet\Common\FlightStats\FlightEquipment")
     */
    private $flightEquipment;

    /**
     * @var ShortCodeshare[]
     * @JMS\Type("array<AwardWallet\Common\FlightStats\ShortCodeshare>")
     */
    private $codeshares;

    /**
     * @var AirportResource
     * @JMS\Type("AwardWallet\Common\FlightStats\AirportResource")
     */
    private $airportResources;

    /**
     * @return int
     */
    public function getFlightId(): int
    {
        return $this->flightId;
    }

    /**
     * @return string
     */
    public function getCarrierFsCode(): string
    {
        return $this->carrierFsCode;
    }

    /**
     * @return string
     */
    public function getOperatingCarrierFsCode(): string
    {
        return $this->operatingCarrierFsCode;
    }

    /**
     * @return string
     */
    public function getPrimaryCarrierFsCode(): string
    {
        return $this->primaryCarrierFsCode;
    }

    /**
     * @return string
     */
    public function getFlightNumber(): string
    {
        return $this->flightNumber;
    }

    /**
     * @return string
     */
    public function getDepartureAirportFsCode(): string
    {
        return $this->departureAirportFsCode;
    }

    /**
     * @return string
     */
    public function getArrivalAirportFsCode(): string
    {
        return $this->arrivalAirportFsCode;
    }

    /**
     * @return DualDate
     */
    public function getDepartureDate(): DualDate
    {
        return $this->departureDate;
    }

    /**
     * @return DualDate
     */
    public function getArrivalDate(): DualDate
    {
        return $this->arrivalDate;
    }

    /**
     * @return FlightEquipment
     */
    public function getFlightEquipment(): FlightEquipment
    {
        return $this->flightEquipment;
    }

    /**
     * @return ShortCodeshare[]
     */
    public function getCodeshares(): array
    {
        return $this->codeshares;
    }

    /**
     * @return Airline
     */
    public function getCarrier(): Airline
    {
        return $this->carrier;
    }

    /**
     * @param Airline $carrier
     */
    public function setCarrier(Airline $carrier): void
    {
        $this->carrier = $carrier;
    }

    /**
     * @return Airline
     */
    public function getPrimaryCarrier(): Airline
    {
        return $this->primaryCarrier;
    }

    /**
     * @param Airline $carrier
     */
    public function setPrimaryCarrier(Airline $carrier): void
    {
        $this->primaryCarrier = $carrier;
    }

    /**
     * @return Airport
     */
    public function getDepartureAirport(): Airport
    {
        return $this->departureAirport;
    }

    /**
     * @param Airport $departureAirport
     */
    public function setDepartureAirport(Airport $departureAirport): void
    {
        $this->departureAirport = $departureAirport;
    }

    /**
     * @return Airport
     */
    public function getArrivalAirport(): Airport
    {
        return $this->arrivalAirport;
    }

    /**
     * @param Airport $arrivalAirport
     */
    public function setArrivalAirport(Airport $arrivalAirport): void
    {
        $this->arrivalAirport = $arrivalAirport;
    }

    /**
     * @return AirportResource
     */
    public function getAirportResources(): AirportResource
    {
        return $this->airportResources;
    }

    /**
     * @param AirportResource $airportResources
     * @return FlightStatus
     */
    public function setAirportResources(AirportResource $airportResources): FlightStatus
    {
        $this->airportResources = $airportResources;
        return $this;
    }

}