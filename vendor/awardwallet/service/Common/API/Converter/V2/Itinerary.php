<?php


namespace AwardWallet\Common\API\Converter\V2;


use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Schema\Itineraries\Itinerary as OutputItinerary;
use AwardWallet\Schema\Parser\Common\Itinerary as ParsedItinerary;

abstract class Itinerary {

	public function convert(ParsedItinerary $parsed, Extra $extra): OutputItinerary {
		$r = $this->initItinerary($parsed, $extra);
		$this->convertCommon($parsed, $r, $extra);
		$this->convertItinerary($parsed, $r, $extra);
		return $r;
	}

	protected function convertCommon(ParsedItinerary $parsed, OutputItinerary $it, Extra $extra): OutputItinerary {
		if ($parsed->getTravelAgency())
			$it->travelAgency = Util::ota($parsed->getTravelAgency(), $extra);
		if ($parsed->getPrice())
			$it->pricingInfo = Util::price($parsed->getPrice());
		$it->status = $parsed->getStatus();
		$it->reservationDate = Util::date($parsed->getReservationDate());
		$it->providerInfo = Util::provider($parsed->getProviderCode(), $parsed->getProviderKeyword(), $parsed->getAccountNumbers(), $parsed->getAreAccountMasked(), $parsed->getEarnedAwards(), $extra);
        $it->cancellationPolicy = $parsed->getCancellation();
        $it->cancelled = $parsed->getCancelled();
        $it->notes = $parsed->getNotes();
		return $it;
	}

	protected abstract function initItinerary(ParsedItinerary $parsed, Extra $extra): OutputItinerary;

	protected abstract function convertItinerary(ParsedItinerary $parsed, OutputItinerary $it, Extra $extra): OutputItinerary;

}