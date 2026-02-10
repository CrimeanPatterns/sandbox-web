<?php


namespace AwardWallet\Common\Parsing\Solver\Itinerary;

use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Schema\Parser\Common\Itinerary;

class Cruise extends ItinerarySolver {

	public function solveItinerary(Itinerary $it, Extra $extra) {
		/** @var \AwardWallet\Schema\Parser\Common\Cruise $it */
		foreach($it->getSegments() as $s) {
			if ($s->getName())
				$this->dh->parseAddress($s->getName(), $extra);
		}
	}

}