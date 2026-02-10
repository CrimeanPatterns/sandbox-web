<?php

namespace AwardWallet\Schema\Parser\Common\Shortcut;


use AwardWallet\Schema\Parser\Common\Parking;

class ParkingPlace
{

    /** @var Parking $parent */
    protected $parent;

    public function __construct(Parking $parent)
    {
        $this->parent = $parent;
    }

    /**
     * @param $s
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return ParkingPlace
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function address($s, $allowEmpty = false, $allowNull = false)
    {
        $this->parent->setAddress($s, $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @param $s
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return ParkingPlace
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function location($s, $allowEmpty = false, $allowNull = false)
    {
        $this->parent->setLocation($s, $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @param $phone
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return ParkingPlace
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function phone($phone, $allowEmpty = false, $allowNull = false)
    {
        $this->parent->setPhone($phone, $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @deprecated use openingHours()
     * @param $hours
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return ParkingPlace
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function hours($hours, $allowEmpty = false, $allowNull = false)
    {
        return $this->openingHours($hours, $allowEmpty, $allowNull);
    }

    /**
     * @param mixed $hours
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return ParkingPlace
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function openingHours($hours, $allowEmpty = false, $allowNull = false) {
        $this->parent->addOpeningHours($hours, $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @param mixed $hours
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return ParkingPlace
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function openingHoursFullList($hours, $allowEmpty = false, $allowNull = false) {
        $this->parent->setOpeningHours($hours, $allowEmpty, $allowNull);
        return $this;
    }


}