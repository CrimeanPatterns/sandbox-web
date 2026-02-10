<?php

namespace AwardWallet\Schema\Parser\Common;

use AwardWallet\Common\DateTimeUtils;
use AwardWallet\Schema\Parser\Common\Shortcut\FerryBooked;
use AwardWallet\Schema\Parser\Common\Shortcut\FerryExtra;
use AwardWallet\Schema\Parser\Common\Shortcut\Point;
use AwardWallet\Schema\Parser\Component\Base;
use AwardWallet\Schema\Parser\Component\Options;
use Psr\Log\LoggerInterface;

class FerrySegment extends BaseSegment
{

    /**
     * @parsed Field
     * @attr type=basic
     * @attr length=short
     */
    protected $carrier;
    /**
     * @parsed Field
     * @attr type=basic
     * @attr length=short
     */
    protected $vessel;
    /**
     * @parsed Arr
     * @attr item=Field
     * @attr item_type=basic
     * @attr item_length=medium
     */
    protected $accommodations;

    /**
     * @parsed Field
     * @attr type=number
     * @attr max=90
     */
    protected $adults;
    /**
     * @parsed Field
     * @attr type=number
     * @attr max=90
     */
    protected $kids;
    /**
     * @parsed Field
     * @attr type=basic
     * @attr length=short
     */
    protected $pets;

    /** @var Vehicle[] $vehicles */
    protected $vehicles;
    protected $_vehicle_cnt;
    //TODO: perhaps it's better make a separate class trailer
    /** @var Vehicle[] $trailers */
    protected $trailers;
    protected $_trailer_cnt;

    /**
     * @parsed Field
     * @attr regexp=/^[A-Z]{2} *(?:[A-Z]{3})?$/
     */
    protected $depCode;
    /**
     * @parsed Field
     * @attr type=basic
     * @attr length=medium
     * @attr minlength=2
     */
    protected $depName;
    /**
     * @parsed Field
     * @attr type=basic
     * @attr length=long
     * @attr minlength=5
     */
    protected $depAddress;
    /**
     * @parsed DateTime
     */
    protected $depDate;
    /**
     * @parsed Field
     * @attr regexp=/^[A-Z]{2} *(?:[A-Z]{3})?$/
     */
    protected $arrCode;
    /**
     * @parsed Field
     * @attr type=basic
     * @attr length=medium
     * @attr minlength=2
     */
    protected $arrName;
    /**
     * @parsed Field
     * @attr type=basic
     * @attr length=long
     * @attr minlength=5
     */
    protected $arrAddress;
    /**
     * @parsed DateTime
     */
    protected $arrDate;
    /**
     * @parsed Boolean
     */
    protected $noDepCode;
    /**
     * @parsed Boolean
     */
    protected $noDepDate;
    /**
     * @parsed Boolean
     */
    protected $noArrCode;
    /**
     * @parsed Boolean
     */
    protected $noArrDate;
    /**
     * @parsed Boolean
     */
    protected $datesStrict;
    /**
     * @parsed Field
     * @attr type=clean
     * @attr length=short
     */
    protected $status;
    /**
     * @parsed Boolean
     */
    protected $smoking;
    /**
     * @parsed Field
     * @attr type=basic
     * @attr length=short
     */
    protected $miles;
    /**
     * @parsed Field
     * @attr type=basic
     * @attr length=short
     */
    protected $duration;
    /**
     * @parsed Arr
     * @attr item=Field
     * @attr item_type=basic
     * @attr item_length=medium
     */
    protected $meals;
    /**
     * @parsed Field
     * @attr type=basic
     * @attr length=70
     */
    protected $cabin;

    /** @var Point $_dep */
    protected $_dep;
    /** @var Point $_arr */
    protected $_arr;
    /** @var  FerryExtra $_ext */
    protected $_ext;
    /** @var  FerryBooked $_booked */
    protected $_booked;

    public function __construct($name, LoggerInterface $logger, Options $options = null)
    {
        parent::__construct($name, $logger, $options);
        $this->_dep = new Point($this, 'd');
        $this->_arr = new Point($this, 'a');
        $this->_booked = new FerryBooked($this);
        $this->_ext = new FerryExtra($this);
        $this->vehicles = [];
        $this->_vehicle_cnt = 0;
        $this->trailers = [];
        $this->_trailer_cnt = 0;
    }

    /**
     * @return Point
     */
    public function departure()
    {
        return $this->_dep;
    }

    /**
     * @return Point
     */
    public function arrival()
    {
        return $this->_arr;
    }

    /**
     * @return FerryBooked
     */
    public function booked()
    {
        return $this->_booked;
    }

    /**
     * @return FerryExtra
     */
    public function extra()
    {
        return $this->_ext;
    }

    /**
     * @return mixed
     */
    public function getVessel()
    {
        return $this->vessel;
    }

    /**
     * @param mixed $vessel
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return FerrySegment
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setVessel($vessel, $allowEmpty = false, $allowNull = false)
    {
        $this->setProperty($vessel, 'vessel', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCarrier()
    {
        return $this->carrier;
    }

    /**
     * @param mixed $carrier
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return FerrySegment
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setCarrier($carrier, $allowEmpty = false, $allowNull = false)
    {
        $this->setProperty($carrier, 'carrier', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @return string
     */
    public function getAdults()
    {
        return $this->adults;
    }

    /**
     * @param string $adults
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return FerrySegment
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setAdults($adults, $allowEmpty = false, $allowNull = false)
    {
        $this->setProperty($adults, 'adults', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @return string
     */
    public function getKids()
    {
        return $this->kids;
    }

    /**
     * @param string $kids
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return FerrySegment
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setKids($kids, $allowEmpty = false, $allowNull = false)
    {
        $this->setProperty($kids, 'kids', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @return string
     */
    public function getPets()
    {
        return $this->pets;
    }

    /**
     * @param string $pets
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return FerrySegment
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setPets($pets, $allowEmpty = false, $allowNull = false)
    {
        $this->setProperty($pets, 'pets', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAccommodations() {
        return $this->accommodations;
    }

    /**
     * @param mixed $accommodations
     * @return FerrySegment
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setAccommodations($accommodations) {
        $this->setProperty($accommodations, 'accommodations', false, false);
        return $this;
    }

    /**
     * @param $accommodation
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return FerrySegment
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function addAccommodation($accommodation, $allowEmpty = false, $allowNull = false) {
        $this->addItem($accommodation, 'accommodations', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @param $accommodation
     * @return FerrySegment
     */
    public function removeAccommodation($accommodation) {
        $this->removeItem($accommodation, 'accommodations');
        return $this;
    }

    /**
     * @return Vehicle[]
     */
    public function getVehicles()
    {
        return $this->vehicles;
    }

    /**
     * @return Vehicle
     */
    public function addVehicle()
    {
        $n = new Vehicle($this->_name . '-vehicle-' . $this->_vehicle_cnt, $this->logger, $this->_options);
        $this->_vehicle_cnt++;
        $this->vehicles[] = $n;
        $this->logDebug(sprintf('%s: added vehicle %s', $this->_name, $n->getId()));
        return $n;
    }

    /**
     * @param Vehicle $vehicle
     * @return FerrySegment
     */
    public function removeVehicle(Vehicle $vehicle)
    {
        $idx = null;
        foreach ($this->vehicles as $i => $r) {
            if (strcmp($r->getId(), $vehicle->getId()) === 0) {
                $idx = $i;
                break;
            }
        }
        if (isset($idx)) {
            unset($this->vehicles[$idx]);
        }
        $this->logDebug(sprintf('%s: removed vehicle %s', $this->_name, $vehicle->getId()));
        return $this;
    }

    /**
     * @return Vehicle[]
     */
    public function getTrailers()
    {
        return $this->trailers;
    }

    /**
     * @return Vehicle
     */
    public function addTrailer()
    {
        $n = new Vehicle($this->_name . '-trailer-' . $this->_trailer_cnt, $this->logger, $this->_options);
        $this->_trailer_cnt++;
        $this->trailers[] = $n;
        $this->logDebug(sprintf('%s: added trailer %s', $this->_name, $n->getId()));
        return $n;
    }

    /**
     * @param Vehicle $trailer
     * @return FerrySegment
     */
    public function removeTrailer(Vehicle $trailer)
    {
        $idx = null;
        foreach ($this->trailers as $i => $r) {
            if (strcmp($r->getId(), $trailer->getId()) === 0) {
                $idx = $i;
                break;
            }
        }
        if (isset($idx)) {
            unset($this->trailers[$idx]);
        }
        $this->logDebug(sprintf('%s: removed trailer %s', $this->_name, $trailer->getId()));
        return $this;
    }

    /**
     * @return Base[]
     */
    protected function getChildren()
    {
        $r = array_merge($this->vehicles, $this->trailers);
        return $r;
    }

    protected function fromArrayChildren(array $arr)
    {
        parent::fromArrayChildren($arr);
        if (isset($arr['vehicles']))
            foreach($arr['vehicles'] as $a)
                $this->addVehicle()->fromArray($a);
        if (isset($arr['trailers']))
            foreach($arr['trailers'] as $a)
                $this->addTrailer()->fromArray($a);
    }

    public function validateBasic($allowTzCross)
    {
        $this->validateArrays();
        $this->checkEmpty(false);
        /** @var Vehicle $vehicle */
        foreach(array_merge($this->trailers, $this->vehicles) as $vehicle)
            $this->valid = $vehicle->checkEmpty() && $this->valid;
        $this->checkIdentical(true, !$allowTzCross);
        if ($this->arrDate && $this->depDate) {
            if ($this->arrDate < $this->depDate && !$allowTzCross) {
                $this->invalid('invalid dates');
            }
            if (abs($this->arrDate - $this->depDate) > 2 * DateTimeUtils::SECONDS_PER_DAY) {
                $this->invalid('dates are too far apart');
            }
        }
        return $this->valid;
    }

    /**
     * checks data and sets valid flag
     * @return bool
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function validateData()
    {
        if (empty($this->depName) && empty($this->depAddress))
            $this->invalid('missing departure location');
        if (empty($this->arrName) && empty($this->arrAddress))
            $this->invalid('missing arrival location');
        if (empty($this->depDate) && $this->noDepDate !== true)
            $this->invalid('missing depDate');
        if (empty($this->arrDate) && $this->noArrDate !== true)
            $this->invalid('missing arrDate');
        return $this->valid;
    }

}