<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\Discriminator;

/**
 * @Discriminator(field = "type",
 * map = {
 * 		"flight": "AwardWallet\Schema\Itineraries\Flight",
 * 		"cruise": "AwardWallet\Schema\Itineraries\Cruise",
 * 		"hotelReservation": "AwardWallet\Schema\Itineraries\HotelReservation",
 * 		"carRental": "AwardWallet\Schema\Itineraries\CarRental",
 * 		"bus": "AwardWallet\Schema\Itineraries\Bus",
 * 		"train": "AwardWallet\Schema\Itineraries\Train",
 * 		"transfer": "AwardWallet\Schema\Itineraries\Transfer",
 * 		"event": "AwardWallet\Schema\Itineraries\Event",
 * 		"parking": "AwardWallet\Schema\Itineraries\Parking",
 * 		"ferry": "AwardWallet\Schema\Itineraries\Ferry",
 * 		"cancelled": "AwardWallet\Schema\Itineraries\Cancelled"
 * })
 */
abstract class Itinerary {

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
     * @var boolean
     * @Type("boolean")
     */
	public $cancelled;

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
     * @return Person[]
     */
    abstract public function getPersons() : array;

}