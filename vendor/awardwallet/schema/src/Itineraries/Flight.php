<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class Flight extends Itinerary {

    /**
     * @var Person[]
     * @Type("array<AwardWallet\Schema\Itineraries\Person>")
     */
	public $travelers;

    /**
     * @var FlightSegment[]
     * @Type("array<AwardWallet\Schema\Itineraries\FlightSegment>")
     */
	public $segments = [];

	/**
	 * @var IssuingCarrier
	 * @Type("AwardWallet\Schema\Itineraries\IssuingCarrier")
	 */
	public $issuingCarrier;


    public function getPersons(): array
    {
        return $this->travelers ?? [];
    }
}