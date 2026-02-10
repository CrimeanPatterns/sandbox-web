<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class Cruise extends Itinerary {

    /**
     * @var ConfNo[]
     * @Type("array<AwardWallet\Schema\Itineraries\ConfNo>")
     */
	public $confirmationNumbers;

    /**
     * @var Person[]
     * @Type("array<AwardWallet\Schema\Itineraries\Person>")
     */
	public $travelers;

    /**
     * @var CruiseSegment[]
     * @Type("array<AwardWallet\Schema\Itineraries\CruiseSegment>")
     */
	public $segments;

    /**
     * @var CruiseDetails
     * @Type("AwardWallet\Schema\Itineraries\CruiseDetails")
     */
	public $cruiseDetails;

	public function getPersons(): array
    {
        return $this->travelers ?? [];
    }

}