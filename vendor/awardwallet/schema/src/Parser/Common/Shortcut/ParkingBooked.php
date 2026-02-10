<?php

namespace AwardWallet\Schema\Parser\Common\Shortcut;


use AwardWallet\Schema\Parser\Common\Parking;

class ParkingBooked
{

    /** @var Parking $parent */
    protected $parent;

    public function __construct(Parking $parent)
    {
        $this->parent = $parent;
    }

    /**
     * @param $d
     * @return ParkingBooked
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function start($d)
    {
        $this->parent->setStartDate($d);
        return $this;
    }

    /**
     * @param $date
     * @param $relative
     * @param string $format
     * @param bool $after
     * @return ParkingBooked
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function start2($date, $relative = null, $format = '%D% %Y%', $after = true)
    {
        $this->parent->parseStartDate($date, $relative, $format, $after);
        return $this;
    }

    /**
     * @param $d
     * @return ParkingBooked
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function end($d)
    {
        $this->parent->setEndDate($d);
        return $this;
    }

    /**
     * @param $date
     * @param $relative
     * @param string $format
     * @param bool $after
     * @return ParkingBooked
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function end2($date, $relative = null, $format = '%D% %Y%', $after = true)
    {
        $this->parent->parseEndDate($date, $relative, $format, $after);
        return $this;
    }

    /**
     * @return ParkingBooked
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function noStart()
    {
        $this->parent->setNoStartDate(true);
        return $this;
    }

    /**
     * @return ParkingBooked
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function noEnd()
    {
        $this->parent->setNoEndDate(true);
        return $this;
    }

    /**
     * @param $s
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return ParkingBooked
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function spot($s, $allowEmpty = false, $allowNull = false)
    {
        $this->parent->setSpot($s, $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @param $s
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return ParkingBooked
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function plate($s, $allowEmpty = false, $allowNull = false)
    {
        $this->parent->setPlate($s, $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @param $s
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return ParkingBooked
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function rate($s, $allowEmpty = false, $allowNull = false)
    {
        $this->parent->setRateType($s, $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @param $s
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return ParkingBooked
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function car($s, $allowEmpty = false, $allowNull = false)
    {
        $this->parent->setCarDescription($s, $allowEmpty, $allowNull);
        return $this;
    }

}