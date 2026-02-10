<?php

namespace AwardWallet\Common\Parsing\Filter\FlightStats;


use AwardWallet\Common\Itineraries\FlightSegment;
use AwardWallet\Common\FlightStats\Communicator;
use Psr\Log\LoggerInterface;

class FlightNumberFilter implements TripSegmentFilterInterface
{
    /**
     * @var Communicator
     */
    private $communicator;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ScheduledFlightConverter
     */
    private $converter;

    /**
     * FlightNumberFilter constructor.
     * @param LoggerInterface $logger
     * @param Communicator $communicator
     */
    public function __construct(LoggerInterface $logger,  Communicator $communicator, ScheduledFlightConverter $converter)
    {
        $this->communicator = $communicator;
        $this->logger = $logger;
        $this->converter = $converter;
    }

    /**
     * фильтрует данные полученные от парсера, дополняет их, возвращает в том же формате что и получает.
     * providerCode - код провайдера по нашей базе
     * @param string $providerCode
     * @param FlightSegment $flightSegment
     * @return void
     */
    public function filterTripSegment($providerCode = '', FlightSegment $flightSegment)
    {
        if('' != $flightSegment->flightNumber) {
            $this->logger->debug("Segment already contains flight number", ['flightSegment' => $flightSegment]);
            return;
        }

        $schedule = $this->communicator->getScheduleByRouteAndDate(
            $flightSegment->departure->airportCode,
            $flightSegment->arrival->airportCode,
            $flightSegment->departure->localDateTime
        );
        if(empty($schedule))
            return;

        $this->converter->extractInfoFromSchedule($schedule, $flightSegment);
    }
}