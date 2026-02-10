<?php

namespace AwardWallet\Schema\Parser\Common\Shortcut;

use AwardWallet\Schema\Parser\Common\FerrySegment;

class FerryExtra
{

    /** @var FerrySegment $segment */
    protected $segment;

    public function __construct(FerrySegment $segment)
    {
        $this->segment = $segment;
    }

    /**
     * @param $status
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return FerryExtra
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function status($status, $allowEmpty = false, $allowNull = false)
    {
        $this->segment->setStatus($status, $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @param $smoking
     * @return FerryExtra
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function smoking($smoking)
    {
        $this->segment->setSmoking($smoking);
        return $this;
    }


    /**
     * @param $miles
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return FerryExtra
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function miles($miles, $allowEmpty = false, $allowNull = false)
    {
        $this->segment->setMiles($miles, $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @param $duration
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return FerryExtra
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function duration($duration, $allowEmpty = false, $allowNull = false)
    {
        $this->segment->setDuration($duration, $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @param $meals
     * @return FerryExtra
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function meals($meals) {
        $this->segment->setMeals($meals);
        return $this;
    }

    /**
     * @param $meal
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return FerryExtra
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function meal($meal, $allowEmpty = false, $allowNull = false) {
        $this->segment->addMeal($meal, $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @param $cabin
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return FerryExtra
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function cabin($cabin, $allowEmpty = false, $allowNull = false)
    {
        $this->segment->setCabin($cabin, $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @param $name
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return FerryExtra
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function carrier($name, $allowEmpty = false, $allowNull = false) {
        $this->segment->setCarrier($name, $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @param $name
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return FerryExtra
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function vessel($name, $allowEmpty = false, $allowNull = false) {
        $this->segment->setVessel($name, $allowEmpty, $allowNull);
        return $this;
    }

}