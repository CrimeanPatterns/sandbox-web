<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class Cancelled extends Itinerary {

	/**
	 * @var TravelAgency
	 * @Type("AwardWallet\Schema\Itineraries\TravelAgency")
	 */
	public $travelAgency;

	/**
	 * @var PricingInfo
	 * @Type("AwardWallet\Schema\Itineraries\PricingInfo")
	 */
	public $pricingInfo;

	/**
	 * @var string
	 * @Type("string")
	 */
	public $status;

	/**
	 * @var string
	 * @Type("string")
	 */
	public $reservationDate;

	/**
	 * @var ProviderInfo
	 * @Type("AwardWallet\Schema\Itineraries\ProviderInfo")
	 */
	public $providerInfo;

    /**
     * @var string
     * @Type("string")
     */
    public $cancellationPolicy;
    /**
     * @var string
     * @Type("string")
     */
    public $notes;

    /**
     * @var string
     * @Type("string")
     */
	public $itineraryType;

    /**
     * @var string
     * @Type("string")
     */
	public $confirmationNumber;

	public function getPersons(): array
    {
        return [];
    }

}