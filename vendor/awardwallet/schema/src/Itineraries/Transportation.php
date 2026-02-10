<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

abstract class Transportation extends Itinerary {

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

	public function getPersons(): array
    {
        return $this->travelers ?? [];
    }

}