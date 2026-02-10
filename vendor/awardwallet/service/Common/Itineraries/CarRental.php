<?php

namespace AwardWallet\Common\Itineraries;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\Accessor;

/**
 * Class Rental
 * @property $pickup
 * @property $dropoff
 * @property $car
 * @property $rentalCompany
 * @property $driver
 * @property $promoCode
 * @property $serviceLevel
 * @property $pricedEquipment
 * @property $discount
 * @property $discounts
 * @property $paymentMethod
 */
class CarRental extends Itinerary
{

    /**
     * @var CarRentalPoint
     * @Type("AwardWallet\Common\Itineraries\CarRentalPoint")
     */
    protected $pickup;
    /**
     * @var CarRentalPoint
     * @Type("AwardWallet\Common\Itineraries\CarRentalPoint")
     */
    protected $dropoff;
    /**
     * @var Car
     * @Type("AwardWallet\Common\Itineraries\Car")
     */
    protected $car;
    /**
     * @var Fee[]
     * @Type("array<AwardWallet\Common\Itineraries\Fee>")
     * @Accessor(getter="getPricedEquipmentForJMS", setter="setPricedEquipment")
     */
    protected $pricedEquipment;
    /**
     * @var CarRentalDiscount[]
     * @Type("array<AwardWallet\Common\Itineraries\CarRentalDiscount>")
     * @Accessor(getter="getDiscountsForJMS", setter="setDiscounts")
     */
    protected $discounts;
    /**
     * @var string
     * @Type("string")
     */
    protected $rentalCompany;
    /**
     * @var Person
     * @Type("AwardWallet\Common\Itineraries\Person")
     */
    protected $driver;
    /**
     * @var string
     * @Type("string")
     */
    protected $promoCode;
    /**
     * @var string
     * @Type("string")
     */
    protected $serviceLevel;
    /**
     * @var string
     * @Type("string")
     */
    protected $paymentMethod;

}
