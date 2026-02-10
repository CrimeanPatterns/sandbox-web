<?php

namespace AwardWallet\Schema\Parser\Common\Shortcut;


use AwardWallet\Schema\Parser\Common\Rental;

class RentalPoint {

	/** @var Rental $parent */
	protected $parent;

	protected $type;

	/** @var  DetailedAddress $da */
	protected $da;

	public function __construct(Rental $parent, DetailedAddress $da, $type) {
		$this->parent = $parent;
		$this->type = $type;
		$this->da = $da;
	}

	/**
	 * @param $location
	 * @return RentalPoint
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function location($location) {
		if ($this->type === 'p')
			$this->parent->setPickUpLocation($location);
		else
			$this->parent->setDropOffLocation($location);
		return $this;
	}

	/**
	 * @return RentalPoint
	 */
	public function same() {
		$this->parent->setSameLocation($this->type === 'd');
		return $this;
	}

	/**
	 * @param $date
	 * @return RentalPoint
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function date($date) {
		if ($this->type === 'p')
			$this->parent->setPickUpDateTime($date);
		else
			$this->parent->setDropOffDateTime($date);
		return $this;
	}

	/**
	 * @param $date
	 * @param $relative
	 * @param string $format
	 * @param bool $after
	 * @return RentalPoint
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function date2($date, $relative = null, $format = '%D% %Y%', $after = true) {
		if ($this->type === 'p')
			$this->parent->parsePickUpDateTime($date, $relative, $format, $after);
		else
			$this->parent->parseDropOffDateTime($date, $relative, $format, $after);
		return $this;
	}

	/**
	 * @return RentalPoint
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function noLocation() {
		if ($this->type === 'p')
			$this->parent->setNoPickupLocation(true);
		else
			$this->parent->setNoDropOffLocation(true);
		return $this;
	}

	/**
	 * @return RentalPoint
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function noDate() {
		if ($this->type === 'p')
			$this->parent->setNoPickUpDate(true);
		else
			$this->parent->setNoDropOffDate(true);
		return $this;
	}

	/**
	 * @return DetailedAddress
	 */
	public function detailed() {
		return $this->da;
	}

    /**
     * @param $phone
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return RentalPoint
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
	public function phone($phone, $allowEmpty = false, $allowNull = false) {
        if ($this->type === 'p')
            $this->parent->setPickUpPhone($phone, $allowEmpty, $allowNull);
        else
            $this->parent->setDropOffPhone($phone, $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @param $fax
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return RentalPoint
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function fax($fax, $allowEmpty = false, $allowNull = false) {
        if ($this->type === 'p')
            $this->parent->setPickUpFax($fax, $allowEmpty, $allowNull);
        else
            $this->parent->setDropOffFax($fax, $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @deprecated use openingHours()
     * @param $hours
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return RentalPoint
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function hours($hours, $allowEmpty = false, $allowNull = false) {
        return $this->openingHours($hours, $allowEmpty, $allowNull);
    }

    /**
     * @param mixed $hours
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return RentalPoint
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function openingHours($hours, $allowEmpty = false, $allowNull = false) {
        if ($this->type === 'p')
            $this->parent->addPickUpOpeningHours($hours, $allowEmpty, $allowNull);
        else
            $this->parent->addDropOffOpeningHours($hours, $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @param mixed $hours
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return RentalPoint
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function openingHoursFullList($hours, $allowEmpty = false, $allowNull = false) {
        if ($this->type === 'p')
            $this->parent->setPickUpOpeningHours($hours, $allowEmpty, $allowNull);
        else
            $this->parent->setDropOffOpeningHours($hours, $allowEmpty, $allowNull);
        return $this;
    }

}