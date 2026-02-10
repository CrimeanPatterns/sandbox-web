<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class CarRental extends Itinerary {

    /**
     * @var ConfNo[]
     * @Type("array<AwardWallet\Schema\Itineraries\ConfNo>")
     */
	public $confirmationNumbers;

    /**
     * @var CarRentalLocation
     * @Type("AwardWallet\Schema\Itineraries\CarRentalLocation")
     */
	public $pickup;

    /**
     * @var CarRentalLocation
     * @Type("AwardWallet\Schema\Itineraries\CarRentalLocation")
     */
	public $dropoff;

    /**
     * @var Car
     * @Type("AwardWallet\Schema\Itineraries\Car")
     */
	public $car;

    /**
     * @var CarRentalDiscount[]
     * @Type("array<AwardWallet\Schema\Itineraries\CarRentalDiscount>")
     */
	public $discounts;

    /**
     * @var Person
     * @Type("AwardWallet\Schema\Itineraries\Person")
     */
	public $driver;

    /**
     * @var Fee[]
     * @Type("array<AwardWallet\Schema\Itineraries\Fee>")
     */
	public $pricedEquipment;

    /**
     * @var string
     * @Type("string")
     */
	public $rentalCompany;

    public function getPersons(): array
    {
        if (!empty($this->driver)) {
            return [$this->driver];
        }

        return [];
    }

}