<?php


namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class Parking extends Itinerary
{

    /**
     * @var ConfNo[]
     * @Type("array<AwardWallet\Schema\Itineraries\ConfNo>")
     */
    public $confirmationNumbers;

    /**
     * @var Address
     * @Type("AwardWallet\Schema\Itineraries\Address")
     */
    public $address;

    /**
     * @var string
     * @Type("string")
     */
    public $locationName;

    /**
     * @var string
     * @Type("string")
     */
    public $spotNumber;

    /**
     * @var string
     * @Type("string")
     */
    public $licensePlate;

    /**
     * @var string
     * @Type("string")
     */
    public $startDateTime;

    /**
     * @var string
     * @Type("string")
     */
    public $endDateTime;

    /**
     * @var string
     * @Type("string")
     */
    public $phone;

    /**
     * @var string
     * @Type("string")
     */
    public $openingHours;

    /**
     * @var Person
     * @Type("AwardWallet\Schema\Itineraries\Person")
     */
    public $owner;

    /**
     * @var string
     * @Type("string")
     */
    public $rateType;

    /**
     * @var string
     * @Type("string")
     */
    public $carDescription;

    public function getPersons(): array
    {
        if (!empty($this->owner)) {
            return [$this->owner];
        }

        return [];
    }

}