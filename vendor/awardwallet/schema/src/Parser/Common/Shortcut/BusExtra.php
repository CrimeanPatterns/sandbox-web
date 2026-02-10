<?php

namespace AwardWallet\Schema\Parser\Common\Shortcut;


use AwardWallet\Schema\Parser\Common\BusSegment;

class BusExtra {

	/** @var BusSegment $segment */
	protected $segment;

	public function __construct(BusSegment $segment) {
		$this->segment = $segment;
	}

	/**
	 * @param $number
	 * @return BusExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function number($number) {
		$this->segment->setNumber($number);
		return $this;
	}

	/**
	 * @return BusExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function noNumber() {
		$this->segment->setNoNumber(true);
		return $this;
	}

	/**
	 * @param $type
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return BusExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function type($type, $allowEmpty = false, $allowNull = false) {
		$this->segment->setBusType($type, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $model
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return BusExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function model($model, $allowEmpty = false, $allowNull = false) {
		$this->segment->setBusModel($model, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $seats
	 * @return BusExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function seats($seats) {
		$this->segment->setSeats($seats);
		return $this;
	}

	/**
	 * @param $seat
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return BusExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function seat($seat, $allowEmpty = false, $allowNull = false) {
		$this->segment->addSeat($seat, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $stops
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return BusExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function stops($stops, $allowEmpty = false, $allowNull = false) {
		$this->segment->setStops($stops, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $smoking
	 * @return BusExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function smoking($smoking) {
		$this->segment->setSmoking($smoking);
		return $this;
	}

	/**
	 * @param $miles
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return BusExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function miles($miles, $allowEmpty = false, $allowNull = false) {
		$this->segment->setMiles($miles, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $cabin
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return BusExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function cabin($cabin, $allowEmpty = false, $allowNull = false) {
		$this->segment->setCabin($cabin, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $bookingCode
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return BusExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function bookingCode($bookingCode, $allowEmpty = false, $allowNull = false) {
		$this->segment->setBookingCode($bookingCode, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $duration
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return BusExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function duration($duration, $allowEmpty = false, $allowNull = false) {
		$this->segment->setDuration($duration, $allowEmpty, $allowNull);
		return $this;
	}

    /**
     * @param $meals
     * @return BusExtra
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
     * @return BusExtra
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function meal($meal, $allowEmpty = false, $allowNull = false) {
        $this->segment->addMeal($meal, $allowEmpty, $allowNull);
        return $this;
    }

    /**
	 * @param $status
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return BusExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function status($status, $allowEmpty = false, $allowNull = false) {
		$this->segment->setStatus($status, $allowEmpty, $allowNull);
		return $this;
	}
	
}