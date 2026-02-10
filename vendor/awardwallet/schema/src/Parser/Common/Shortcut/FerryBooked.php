<?php

namespace AwardWallet\Schema\Parser\Common\Shortcut;

use AwardWallet\Schema\Parser\Common\FerrySegment;

class FerryBooked
{

    /** @var FerrySegment $segment */
    protected $segment;

    public function __construct(FerrySegment $segment) {
        $this->segment = $segment;
    }

    /**
     * @param $adults
     * @return FerryBooked
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function adults($adults) {
        $this->segment->setAdults($adults);
        return $this;
    }

    /**
     * @param $kids
     * @return FerryBooked
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function kids($kids) {
        $this->segment->setKids($kids);
        return $this;
    }

    /**
     * @param $pets
     * @return FerryBooked
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function pets($pets) {
        $this->segment->setPets($pets);
        return $this;
    }

    /**
     * @param $accommodations
     * @return FerryBooked
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function accommodations($accommodations)
    {
        $this->segment->setAccommodations($accommodations);
        return $this;
    }

    /**
     * @param $accommodation
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return FerryBooked
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function accommodation($accommodation, $allowEmpty = false, $allowNull = false)
    {
        $this->segment->addAccommodation($accommodation, $allowEmpty, $allowNull);
        return $this;
    }
}