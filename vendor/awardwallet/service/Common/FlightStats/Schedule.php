<?php


namespace AwardWallet\Common\FlightStats;

use JMS\Serializer\Annotation as JMS;

class Schedule
{
    /**
     * @var ScheduledFlight[]
     * @JMS\Type("array<AwardWallet\Common\FlightStats\ScheduledFlight>")
     */
    private $scheduledFlights;

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
        if (null !== $this->error && null !== $this->error->getErrorCode()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Schedule constructor.
     * @param ScheduledFlight[] $scheduledFlights
     * @param ScheduleAppendix $appendix
     * @param Error|null $error
     */
    public function __construct(array $scheduledFlights, ScheduleAppendix $appendix, Error $error = null)
    {
        $this->scheduledFlights = $scheduledFlights;
        $this->appendix = $appendix;
        $this->error = $error;
    }

    /**
     * @return ScheduledFlight[]
     */
    public function getScheduledFlights()
    {
        return $this->scheduledFlights;
    }

    /**
     * @return ScheduleAppendix
     */
    public function getAppendix()
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
        foreach ($this->scheduledFlights as $scheduledFlight) {
            if (isset($airportsByCode[$scheduledFlight->getDepartureAirportFsCode()]))
                $scheduledFlight->setDepartureAirport($airportsByCode[$scheduledFlight->getDepartureAirportFsCode()]);
            if (isset($airportsByCode[$scheduledFlight->getArrivalAirportFsCode()]))
                $scheduledFlight->setArrivalAirport($airportsByCode[$scheduledFlight->getArrivalAirportFsCode()]);
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
        foreach ($this->scheduledFlights as $scheduledFlight) {
            $scheduledFlight->setCarrier($airlinesByCode[$scheduledFlight->getCarrierFsCode()]);
            if ($op = $scheduledFlight->getOperator())
                $op->setCarrier($airlinesByCode[$op->getCarrierFsCode()]);
        }
    }
}