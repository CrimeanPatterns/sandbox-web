<?php

namespace AwardWallet\Common\Itineraries;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\Discriminator;
use JMS\Serializer\Annotation\Exclude;
use Psr\Log\LoggerInterface;

/**
 * Class Itinerary
 * @property ProviderDetails $providerDetails
 * @property $totalPrice
 *
 * @Discriminator(field = "type",
 * map = {
 * 		"flight": "AwardWallet\Common\Itineraries\Flight",
 * 		"transportation": "AwardWallet\Common\Itineraries\Transportation",
 * 		"cruise": "AwardWallet\Common\Itineraries\Cruise",
 * 		"hotelReservation": "AwardWallet\Common\Itineraries\HotelReservation",
 * 		"carRental": "AwardWallet\Common\Itineraries\CarRental",
 * 		"event": "AwardWallet\Common\Itineraries\Event",
 * 		"cancelled": "AwardWallet\Common\Itineraries\Cancelled"
 * })
 */
abstract class Itinerary extends LoggerEntity
{

    const ISO_DATE_FORMAT = 'Y-m-d\TH:i:s';

    /**
     * @var LoggerInterface
     * @Exclude
     */
    protected $logger;

    /**
     * @var ProviderDetails
     * @Type("AwardWallet\Common\Itineraries\ProviderDetails")
     */
    protected $providerDetails;

    /**
     * @var TotalPrice
     * @Type("AwardWallet\Common\Itineraries\TotalPrice")
     */
    protected $totalPrice;

    const ITINERARIES_KINDS = [
        "T" => "Trip",
        "L" => "Rental",
        "R" => "Reservation",
        "D" => "Direction",
        "E" => "Restaurant",
    ];

    public function __construct(LoggerInterface $logger = null) {
        parent::__construct($logger);
        $this->type = lcfirst(preg_replace('/^.+\\\/ims', '', get_class($this)));
    }


}