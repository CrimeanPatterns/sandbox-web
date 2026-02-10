<?php


namespace AwardWallet\Common\Parsing\Solver;

use AwardWallet\Common\Parsing\Solver\Helper\ExtraHelper;
use AwardWallet\Common\Parsing\Solver\Helper\ItineraryHelper;
use AwardWallet\Common\Parsing\Solver\Itinerary\Bus;
use AwardWallet\Common\Parsing\Solver\Itinerary\Cruise;
use AwardWallet\Common\Parsing\Solver\Itinerary\Event;
use AwardWallet\Common\Parsing\Solver\Itinerary\Ferry;
use AwardWallet\Common\Parsing\Solver\Itinerary\Flight;
use AwardWallet\Common\Parsing\Solver\Itinerary\Hotel;
use AwardWallet\Common\Parsing\Solver\Itinerary\ItinerarySolver;
use AwardWallet\Common\Parsing\Solver\Itinerary\Rental;
use AwardWallet\Common\Parsing\Solver\Itinerary\Train;
use AwardWallet\Common\Parsing\Solver\Itinerary\Transfer;
use AwardWallet\Common\Parsing\Solver\Itinerary\Parking;
use AwardWallet\Schema\Parser\Component\Master;
use AwardWallet\Common\Parsing\Solver\Extra\Extra;

class MasterSolver {

	/**
	 * @var ExtraHelper
	 */
	protected $eh;
	/**
	 * @var ItineraryHelper
	 */
	protected $ih;
	/**
	 * @var ItinerarySolver[]
	 */
	protected $itinerarySolvers;
    /**
     * @var Loyalty
     */
	protected $loyalty;

	public function __construct(
		ExtraHelper $eh,
		Bus $bus,
		Cruise $cruise,
		Event $event,
		Flight $flight,
		Hotel $hotel,
		Rental $rental,
		Train $train,
		Transfer $transfer,
        Parking $parking,
        Ferry $ferry,
        Loyalty $loyalty,
        ItineraryHelper $ih) {
		$this->eh = $eh;
		$this->itinerarySolvers = [
			\AwardWallet\Schema\Parser\Common\Bus::class => $bus,
			\AwardWallet\Schema\Parser\Common\Cruise::class => $cruise,
			\AwardWallet\Schema\Parser\Common\Event::class => $event,
			\AwardWallet\Schema\Parser\Common\Flight::class => $flight,
			\AwardWallet\Schema\Parser\Common\Hotel::class => $hotel,
			\AwardWallet\Schema\Parser\Common\Rental::class => $rental,
			\AwardWallet\Schema\Parser\Common\Train::class => $train,
			\AwardWallet\Schema\Parser\Common\Transfer::class => $transfer,
            \AwardWallet\Schema\Parser\Common\Parking::class => $parking,
            \AwardWallet\Schema\Parser\Common\Ferry::class => $ferry,
		];
		$this->loyalty = $loyalty;
        $this->ih = $ih;
	}

	public function solve(Master $master, Extra $extra) {
		$this->eh->solveProvider(null, $extra->provider->code, $extra);
		$this->ih->extractFlightsNotAirCode($master);
		foreach ($master->getItineraries() as $itinerary)
            $this->itinerarySolvers[get_class($itinerary)]->solve($itinerary, $extra);
		if (null !== $master->getStatement())
		    $this->loyalty->solve($master->getStatement(), $extra);
		foreach($master->getItineraries() as $itinerary) {
		    if ($itinerary instanceof \AwardWallet\Schema\Parser\Common\Transfer) {
		        foreach($itinerary->getSegments() as $segment) {
		            if (!empty($segment->getDepFlightSegment()) && empty($segment->getDepDate())
                        || !empty($segment->getArrFlightSegment()) && empty($segment->getArrDate())) {
		                $this->ih->calculateTransferDate($segment, $extra);
                    }
                }
            }
        }
	}

}