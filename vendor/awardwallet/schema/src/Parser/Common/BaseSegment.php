<?php

namespace AwardWallet\Schema\Parser\Common;


use AwardWallet\Schema\Parser\Component\Base;

abstract class BaseSegment extends Base {

	protected $depCode;
	protected $depName;
	protected $depGeoTip;
	protected $depAddress;
	protected $depDate;
	protected $arrCode;
	protected $arrName;
	protected $arrGeoTip;
	protected $arrAddress;
	protected $arrDate;
	protected $noDepCode;
	protected $noDepDate;
	protected $noArrCode;
	protected $noArrDate;
	protected $datesStrict;

	protected $status;
	protected $cancelled;
	protected $seats;
	protected $stops;
	protected $smoking;
	protected $miles;
	protected $cabin;
	protected $bookingCode;
	protected $duration;
	protected $meals;

	/**
	 * @return mixed
	 */
	public function getDepCode() {
		return $this->depCode;
	}

	/**
	 * @param mixed $depCode
	 * @return BaseSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setDepCode($depCode) {
		$this->setProperty($depCode, 'depCode', false, false);
		return $this;
	}

    /**
     * @return BaseSegment
     */
	public function clearDepCode()
    {
        $this->clearProperty('depCode');
        return $this;
    }

	/**
	 * @return mixed
	 */
	public function getDepName() {
		return $this->depName;
	}

	/**
	 * @param mixed $depName
	 * @return BaseSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setDepName($depName) {
		$this->setProperty($depName, 'depName', false, false);
		return $this;
	}

    public function getDepGeoTip()
    {
        return $this->depGeoTip;
    }

    /**
     * @param $depGeoTip
     * @return $this
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
	public function setDepGeoTip($depGeoTip)
    {
        $this->setProperty($depGeoTip, 'depGeoTip', false, false);
        return $this;
    }

	/**
	 * @return mixed
	 */
	public function getDepAddress() {
		return $this->depAddress;
	}

	/**
	 * @param mixed $depAddress
	 * @return BaseSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setDepAddress($depAddress) {
		$this->setProperty($depAddress, 'depAddress', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getDepDate() {
		return $this->depDate;
	}

	/**
	 * @param mixed $depDate
	 * @return BaseSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setDepDate($depDate) {
		$this->setProperty($depDate, 'depDate', false, false);
		return $this;
	}

	/**
	 * @param $date
	 * @param $relative
	 * @param bool $after
	 * @param string $format
	 * @return BaseSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function parseDepDate($date, $relative = null, $format = '%D% %Y%', $after = true) {
		$this->parseUnixTimeProperty($date, 'depDate', $relative, $after, $format);
		return $this;
	}

    /**
     * @param bool $strict
     * @return $this
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
	public function setDatesStrict($strict)
    {
        $this->setProperty($strict, 'datesStrict', false, false);
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getDatesStrict(): ?bool
    {
        return $this->datesStrict;
    }

	/**
	 * @return mixed
	 */
	public function getArrCode() {
		return $this->arrCode;
	}

	/**
	 * @param mixed $arrCode
	 * @return BaseSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setArrCode($arrCode) {
		$this->setProperty($arrCode, 'arrCode', false, false);
		return $this;
	}

    /**
     * @return BaseSegment
     */
	public function clearArrCode()
    {
        $this->clearProperty('arrCode');
        return $this;
    }

	/**
	 * @return mixed
	 */
	public function getArrName() {
		return $this->arrName;
	}

	/**
	 * @param mixed $arrName
	 * @return BaseSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setArrName($arrName) {
		$this->setProperty($arrName, 'arrName', false, false);
		return $this;
	}

    public function getArrGeoTip()
    {
        return $this->arrGeoTip;
    }

    /**
     * @param $arrGeoTip
     * @return $this
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setArrGeoTip($arrGeoTip)
    {
        $this->setProperty($arrGeoTip, 'arrGeoTip', false, false);
        return $this;
    }

	/**
	 * @return mixed
	 */
	public function getArrAddress() {
		return $this->arrAddress;
	}

	/**
	 * @param mixed $arrAddress
	 * @return BaseSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setArrAddress($arrAddress) {
		$this->setProperty($arrAddress, 'arrAddress', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getArrDate() {
		return $this->arrDate;
	}

	/**
	 * @param mixed $arrDate
	 * @return BaseSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setArrDate($arrDate) {
		$this->setProperty($arrDate, 'arrDate', false, false);
		return $this;
	}

	/**
	 * @param $date
	 * @param $relative
	 * @param bool $after
	 * @param string $format
	 * @return BaseSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function parseArrDate($date, $relative = null, $format = '%D% %Y%', $after = true) {
		$this->parseUnixTimeProperty($date, 'arrDate', $relative, $after, $format);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getNoDepCode() {
		return $this->noDepCode;
	}

	/**
	 * @param boolean $noDepCode
	 * @return BaseSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setNoDepCode($noDepCode) {
		$this->setProperty($noDepCode, 'noDepCode', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getNoDepDate() {
		return $this->noDepDate;
	}

	/**
	 * @param mixed $noDepDate
	 * @return BaseSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setNoDepDate($noDepDate) {
		$this->setProperty($noDepDate, 'noDepDate', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getNoArrCode() {
		return $this->noArrCode;
	}

	/**
	 * @param mixed $noArrCode
	 * @return BaseSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setNoArrCode($noArrCode) {
		$this->setProperty($noArrCode, 'noArrCode', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getNoArrDate() {
		return $this->noArrDate;
	}

	/**
	 * @param mixed $noArrDate
	 * @return BaseSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setNoArrDate($noArrDate) {
		$this->setProperty($noArrDate, 'noArrDate', false, false);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @param string $status
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return BaseSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setStatus($status, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($status, 'status', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getSeats() {
		return $this->seats;
	}

    public function getCancelled(): ?bool
    {
        return $this->cancelled;
    }

    /**
     * @param bool $cancelled
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     * @return BaseSegment
     */
    public function setCancelled(bool $cancelled)
    {
        $this->setProperty($cancelled, 'cancelled', false, false);
        return $this;
    }

	/**
	 * @param mixed $seats
	 * @return BaseSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setSeats($seats) {
		$this->setProperty($seats, 'seats', false, false);
		return $this;
	}

	/**
	 * @param $seat
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return BaseSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function addSeat($seat, $allowEmpty = false, $allowNull = false) {
		$this->addItem($seat, 'seats', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $seat
	 * @return BaseSegment
	 */
	public function removeSeat($seat) {
		$this->removeItem($seat, 'seats');
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getStops() {
		return $this->stops;
	}

	/**
	 * @param mixed $stops
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return BaseSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setStops($stops, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($stops, 'stops', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getSmoking() {
		return $this->smoking;
	}

	/**
	 * @param mixed $smoking
	 * @return BaseSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setSmoking($smoking) {
		$this->setProperty($smoking, 'smoking', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getMiles() {
		return $this->miles;
	}

	/**
	 * @param mixed $miles
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return BaseSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setMiles($miles, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($miles, 'miles', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getCabin() {
		return $this->cabin;
	}

	/**
	 * @param mixed $cabin
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return BaseSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setCabin($cabin, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($cabin, 'cabin', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getBookingCode() {
		return $this->bookingCode;
	}

	/**
	 * @param mixed $bookingCode
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return BaseSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setBookingCode($bookingCode, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($bookingCode, 'bookingCode', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getDuration() {
		return $this->duration;
	}

	/**
	 * @param mixed $duration
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return BaseSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setDuration($duration, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($duration, 'duration', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getMeals() {
		return $this->meals;
	}

    /**
     * @param mixed $meals
     * @return BaseSegment
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setMeals($meals) {
        $this->setProperty($meals, 'meals', false, false);
        return $this;
    }

    /**
     * @param $meal
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return BaseSegment
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function addMeal($meal, $allowEmpty = false, $allowNull = false) {
        $this->addItem($meal, 'meals', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @param $meal
     * @return BaseSegment
     */
    public function removeMeal($meal) {
        $this->removeItem($meal, 'meals');
        return $this;
    }

    public function getDepNameTip()
    {
        return $this->concatTip($this->depName, $this->depGeoTip);
    }

    public function getArrNameTip()
    {
        return $this->concatTip($this->arrName, $this->arrGeoTip);
    }

    private function concatTip($address, $tip)
    {
        if (!$address) {
            return null;
        }
        if (!$tip) {
            return $address;
        }
        if (strpos($tip, ',') === 0) {
            return $address . ', ' . trim($tip, ' ,');
        }
        else {
            return trim($tip, ' ,') . ', ' . $address;
        }
    }

    public function checkEmpty(bool $checkCodes)
    {
        $check = [$this->depName, $this->arrName, $this->depDate, $this->arrDate];
        if ($checkCodes)
            $check = array_merge($check, [$this->depCode, $this->arrCode]);
        $empty = true;
        foreach($check as $item)
            $empty = $empty && empty($item);
        if ($empty)
            $this->invalid('empty segment');
    }

    public function checkIdentical(bool $checkLocation, bool $checkDate)
    {
        $check = [];
        if ($checkLocation)
            $check = array_merge($check, [[$this->depCode, $this->arrCode, 'codes'], [$this->depName, $this->arrName, 'locations']]);
        if ($checkDate)
            $check = array_merge($check, [[$this->depDate, $this->arrDate, 'dates']]);
        foreach($check as $pair)
            if (!empty($pair[0]) && !empty($pair[1]) && $pair[0] === $pair[1])
                $this->invalid($pair[2] . ' are identical');
    }

}