<?php


namespace AwardWallet\Common\Parsing\Filter\FlightStats;


use AwardWallet\Common\FlightStats\Airport;
use AwardWallet\Common\FlightStats\Communicator;
use AwardWallet\Common\Itineraries\FlightPoint;
use AwardWallet\Common\Itineraries\FlightSegment;
use Psr\Log\LoggerInterface;

class AirportCodeByFlightStatsFilter implements TripSegmentFilterInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Communicator
     */
    private $communicator;
    /**
     * @var ScheduledFlightConverter
     */
    private $converter;

    /**
     * AirportCodeByFlightStatsFilter constructor.
     * @param LoggerInterface $logger
     * @param Communicator $communicator
     */
    public function __construct(LoggerInterface $logger, Communicator $communicator, ScheduledFlightConverter $converter)
    {
        $this->logger = $logger;
        $this->communicator = $communicator;
        $this->converter = $converter;
    }

    /**
     * фильтрует данные полученные от парсера, дополняет их, возвращает в том же формате что и получает.
     * providerCode - код провайдера по нашей базе
     * @param string $providerCode
     * @param FlightSegment $flightSegment
     * @return void
     */
    public function filterTripSegment($providerCode, FlightSegment $flightSegment)
    {
        if('' != $flightSegment->departure->airportCode && '' != $flightSegment->arrival->airportCode) {
            $this->logger->debug('Airport codes are already present');
            return;
        }

        $schedule = $this->communicator->getScheduleByCarrierFNAndDepartureDate($flightSegment->airlineName, $flightSegment->flightNumber, $flightSegment->departure->localDateTime);
        if(empty($schedule))
            return;

        $this->converter->extractInfoFromSchedule($schedule, $flightSegment);
    }

}