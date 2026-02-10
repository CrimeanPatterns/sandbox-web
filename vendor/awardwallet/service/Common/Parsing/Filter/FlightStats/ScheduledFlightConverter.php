<?php

namespace AwardWallet\Common\Parsing\Filter\FlightStats;

use AwardWallet\Common\FlightStats\Airport;
use AwardWallet\Common\FlightStats\Schedule;
use AwardWallet\Common\FlightStats\ScheduleAppendix;
use AwardWallet\Common\FlightStats\ScheduledFlight;
use AwardWallet\Common\Itineraries\FlightPoint;
use AwardWallet\Common\Itineraries\FlightSegment;
use Psr\Log\LoggerInterface;

class ScheduledFlightConverter
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function extractInfoFromSchedule(Schedule $schedule, FlightSegment $segment)
    {
        $flight = $this->getFlightFromScheduleByDepartureDate($schedule, $segment->departure->localDateTime);

        if(null === $flight) {
            $this->logger->notice("Failed to find the flight at flight stats by route and date", [
                'DepCode' => $segment->departure->airportCode,
                'ArrCode' => $segment->arrival->airportCode,
                'DepDateTime' => $segment->departure->localDateTime
            ]);
            return false;
        }

        $this->extractInfoFromFlight($flight, $schedule->getAppendix(), $segment);
        return true;
    }

    private function extractInfoFromFlight(ScheduledFlight $flight, ScheduleAppendix $scheduleAppendix, FlightSegment $segment)
    {
        if(empty($segment->flightNumber)) {
            $flightNumber = $flight->getFlightNumber();
            $this->logger->notice("Assigning flight number $flightNumber to the segment", ['flightSegment' => $segment]);
            $segment->flightNumber = $flightNumber;
        }

        $this->updateAirportCode($segment->departure, $flight->getDepartureAirport());
        $this->updateAirportCode($segment->arrival, $flight->getArrivalAirport());

        if(empty($segment->stops))
            $segment->stops = $flight->getStops();

        if(empty($segment->airlineName) && !empty($flight->getCarrierFsCode())){
            foreach($scheduleAppendix->getAirlines() as $airline){
                if($airline->getFs() == $flight->getCarrierFsCode()){
                    $this->logger->notice("Assigning AirlineName to the segment", ['flightSegment' => $segment, "airlineName" => $airline->getName()]);
                    $segment->airlineName = $airline->getName();
                    break;
                }
            }
        }

        if(empty($segment->departure->terminal))
            $segment->departure->terminal = $flight->getDepartureTerminal();
        if(empty($segment->arrival->terminal))
            $segment->arrival->terminal = $flight->getArrivalTerminal();
        if(empty($segment->aircraft) && !empty($flight->getFlightEquipmentIataCode())) {
            foreach($scheduleAppendix->getEquipments() as $equipment){
                if($equipment->getIata() == $flight->getFlightEquipmentIataCode()){
                    $this->logger->notice("Assigning Aircraft to the segment", ['flightSegment' => $segment, "aircraft" => $equipment->getName()]);
                    $segment->aircraft = $equipment->getName();
                    break;
                }
            }
        }
    }

    /**
     * @param Schedule $schedule
     * @param $date
     * @return ScheduledFlight|null
     */
    private function getFlightFromScheduleByDepartureDate(Schedule $schedule, $date)
    {
        foreach ($schedule->getScheduledFlights() as $scheduledFlight) {
            if(strtotime($scheduledFlight->getDepartureTime()) == strtotime($date)) {
                return $scheduledFlight;
            }
        }
        return null;
    }

    /**
     * @param FlightPoint $flightPoint
     * @param Airport|null $airport
     */
    private function updateAirportCode(FlightPoint $flightPoint, Airport $airport = null)
    {
        if(null === $airport) {
            return;
        }

        if('' != $flightPoint->airportCode) {
            if($airport->getIata() !== $flightPoint->airportCode) {
                $this->logger->notice(
                    'Airport code differs from the one returned from flight stats',
                    ['flightPoint' => $flightPoint, 'flightStatsAirport' => $airport]
                );
            }
            return;
        }

        $this->logger->notice("Assigning airportCode to the segment", ['flightPoint' => $flightPoint, "code" => $airport->getIata()]);
        $flightPoint->airportCode = $airport->getIata();
    }
}