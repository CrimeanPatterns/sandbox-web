<?php


namespace AwardWallet\Common\Parsing\Solver\Extra;


use AwardWallet\Common\FlightStats\Airline;
use AwardWallet\Common\FlightStats\ScheduleAppendix;
use AwardWallet\Common\FlightStats\ScheduledFlight;

class SolverData
{

    /** @var ScheduledFlight[] */
    private $scheduleList = [];

    /** @var ScheduleAppendix[] */
    private $appendices = [];

    private $fsCalls = [];

    public function addSchedule($key, ScheduledFlight $flight)
    {
        $this->scheduleList[$key] = $flight;
    }

    /**
     * @param $key
     * @return ScheduledFlight|null
     */
    public function getSchedule($key)
    {
        return isset($this->scheduleList[$key]) ? $this->scheduleList[$key] : null;
    }

    public function addFsCall($key, $call)
    {
        $this->fsCalls[$key] = $call;
    }

    public function getFsCall($key)
    {
        return $this->fsCalls[$key] ?? null;
    }

    public function getFsCallsTotal()
    {
        $r = [];
        foreach($this->fsCalls as $call) {
            if (isset($r[$call]))
                $r[$call]++;
            else
                $r[$call] = 1;
        }
        return $r;
    }

    public function addAppendix(ScheduleAppendix $appendix) {
        $this->appendices[] = $appendix;
    }

    /**
     * @return ScheduleAppendix[]
     */
    public function getAppendices() {
        return $this->appendices;
    }

    /**
     * @param $fsCode
     * @return Airline|null
     */
    public function findAirlineFromAppendix($fsCode) :?Airline
    {
        foreach ($this->appendices as $appendix)
            foreach ($appendix->getAirlines() as $airline)
                if ($airline->getFs() === $fsCode)
                    return $airline;
        return null;
    }

    public function findAirportFromAppendix($fsCode)
    {
        foreach($this->appendices as $appendix)
            foreach($appendix->getAirports() as $airport)
                if ($airport->getFs() === $fsCode)
                    return $airport->getIata();
        return null;
    }

}