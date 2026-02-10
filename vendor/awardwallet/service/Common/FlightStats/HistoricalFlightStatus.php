<?php


namespace AwardWallet\Common\FlightStats;

use JMS\Serializer\Annotation as JMS;

class HistoricalFlightStatus
{

    /**
     * @var FlightStatus[]
     * @JMS\Type("array<AwardWallet\Common\FlightStats\FlightStatus>")
     */
    private $flightStatuses;

    /**
     * @var ScheduleAppendix
     * @JMS\Type("AwardWallet\Common\FlightStats\ScheduleAppendix")
     */
    private $appendix;

    /**
     * @var Error
     * @JMS\Type("AwardWallet\Common\FlightStats\Error")
     */
    private $error;

    /**
     * @return Error
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        if (null !== $this->error && null !== $this->error->getErrorMessage()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return FlightStatus[]
     */
    public function getFlightStatuses(): array
    {
        return $this->flightStatuses;
    }

    /**
     * @return ScheduleAppendix
     */
    public function getAppendix(): ScheduleAppendix
    {
        return $this->appendix;
    }

    /**
     * @JMS\PostDeserialize()
     */
    public function setAirports()
    {
        $airportsByCode = [];
        foreach ($this->appendix->getAirports() as $airport) {
            $airportsByCode[$airport->getFs()] = $airport;
        }
        foreach ($this->flightStatuses as $status) {
            $status->setDepartureAirport($airportsByCode[$status->getDepartureAirportFsCode()]);
            $status->setArrivalAirport($airportsByCode[$status->getArrivalAirportFsCode()]);
        }
    }

    /**
     * @JMS\PostDeserialize()
     */
    public function setAirlines()
    {
        $airlinesByCode = [];
        foreach ($this->appendix->getAirlines() as $airline) {
            $airlinesByCode[$airline->getFs()] = $airline;
        }
        foreach ($this->flightStatuses as $status) {
            $status->setCarrier($airlinesByCode[$status->getCarrierFsCode()]);
            $status->setPrimaryCarrier($airlinesByCode[$status->getPrimaryCarrierFsCode()]);
            if (!empty($status->getCodeshares()))
                foreach($status->getCodeshares() as $cs)
                    $cs->setCarrier($airlinesByCode[$cs->getFsCode()]);
        }
    }

}