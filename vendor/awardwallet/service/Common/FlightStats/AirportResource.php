<?php


namespace AwardWallet\Common\FlightStats;

use JMS\Serializer\Annotation as JMS;

class AirportResource
{

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $departureTerminal;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $arrivalTerminal;

    /**
     * @return string
     */
    public function getDepartureTerminal(): ?string
    {
        return $this->departureTerminal;
    }

    /**
     * @param string $departureTerminal
     * @return AirportResource
     */
    public function setDepartureTerminal(string $departureTerminal): AirportResource
    {
        $this->departureTerminal = $departureTerminal;
        return $this;
    }

    /**
     * @return string
     */
    public function getArrivalTerminal(): ?string
    {
        return $this->arrivalTerminal;
    }

    /**
     * @param string $arrivalTerminal
     * @return AirportResource
     */
    public function setArrivalTerminal(string $arrivalTerminal): AirportResource
    {
        $this->arrivalTerminal = $arrivalTerminal;
        return $this;
    }

}