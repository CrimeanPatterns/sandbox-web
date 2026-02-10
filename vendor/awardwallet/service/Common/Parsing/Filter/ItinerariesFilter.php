<?php


namespace AwardWallet\Common\Parsing\Filter;


use AwardWallet\Common\Itineraries\Cancelled;
use AwardWallet\Common\Itineraries\Flight;
use AwardWallet\Common\Itineraries\FlightSegment;
use AwardWallet\Common\Itineraries\ItinerariesCollection;
use AwardWallet\Common\Itineraries\Itinerary;
use AwardWallet\Common\Itineraries\SegmentsCollection;
use AwardWallet\Common\Parsing\Filter\FlightStats\TripSegmentFilterInterface;
use Psr\Log\LoggerInterface;

class ItinerariesFilter
{
    /**
     * @var TripSegmentFilterInterface[]
     */
    private $segmentFilters = [];

    /**
     * @var ItineraryFilterInterface[]
     */
    private $itineraryFilters = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    private $logComponents = [
        'components' => 'ItinerariesFilter'
    ];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function addSegmentFilter(TripSegmentFilterInterface $filter)
    {
        if(!in_array($filter, $this->segmentFilters)) {
            $this->segmentFilters[] = $filter;
        }
    }

    /**
     * @param ItineraryFilterInterface $filter
     */
    public function addItineraryFilter(ItineraryFilterInterface $filter)
    {
        if(!in_array($filter, $this->segmentFilters)) {
            $this->itineraryFilters[] = $filter;
        }
    }

    /**
     * @param ItinerariesCollection $itineraries
     * @param $providerCode
     * @return void
     */
    public function filter(ItinerariesCollection $itineraries, $providerCode)
    {
        $this->logger->info(
            'Start filtering',
            array_merge(
                $this->logComponents,
                ['ItinerariesCount' => count($itineraries), 'providerCode' => $providerCode]
            )
        );
        $collection = $itineraries->getCollection();
        if (empty($collection))
        {
            $this->logger->notice('No itineraries, skip filtering', $this->logComponents);
            return;
        }

        foreach ($collection as $itinerary) {
            if ($itinerary instanceof Cancelled) {
                $this->logger->debug('Cancelled itinerary, ignoring', $this->logComponents);
                continue;
            }
            $this->filterItinerary($providerCode, $itinerary);
            if (!$itinerary instanceof Flight) {
                $this->logger->notice('Not an instance of Flight, skipping', $this->logComponents);
                continue;
            }

            if ($itinerary->type !== 'flight') {
                $this->logger->notice("Type is not 'flight', skipping", $this->logComponents);
                continue;
            }

            $segments = [];
            if ($itinerary->segments instanceof SegmentsCollection){
                $segments = $itinerary->segments->getCollection();
            } elseif (is_array($itinerary->segments)){
                $segments = $itinerary->segments;
            }
            if (empty($segments)) {
                $this->logger->info('No segments, skipping', $this->logComponents);
                continue;
            }

            foreach ($segments as $flightSegment) {
                $this->filterFlightSegment($providerCode, $flightSegment);
            }
        }
        $this->logger->info('End filtering', $this->logComponents);
    }

    /**
     * @param $providerCode
     * @param Itinerary $itinerary
     */
    private function filterItinerary($providerCode, Itinerary $itinerary)
    {
        foreach ($this->itineraryFilters as $itineraryFilter) {
            $itineraryFilter->filter($itinerary, $providerCode);
        }
    }

    /**
     * @param $providerCode
     * @param FlightSegment $flightSegment
     * @return void
     */
    private function filterFlightSegment($providerCode, FlightSegment $flightSegment)
    {
        foreach ($this->segmentFilters as $filter) {
            $filter->filterTripSegment($providerCode, $flightSegment);
        }
    }

    /**
     * @return ItineraryFilterInterface[]
     */
    public function getItineraryFilters()
    {
        return $this->itineraryFilters;
    }

    /**
     * @return TripSegmentFilterInterface[]
     */
    public function getSegmentFilters()
    {
        return $this->segmentFilters;
    }
}