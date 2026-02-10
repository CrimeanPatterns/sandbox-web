<?php

namespace AwardWallet\Schema\Parser\Common\Shortcut;


use AwardWallet\Schema\Parser\Common\FlightSegment;

class FlightExtra {

	/** @var FlightSegment $segment */
	protected $segment;

	public function __construct(FlightSegment $segment) {
		$this->segment = $segment;
	}

	/**
	 * @param $status
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return FlightExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function status($status, $allowEmpty = false, $allowNull = false) {
		$this->segment->setStatus($status, $allowEmpty, $allowNull);
		return $this;
	}

    /**
     * @return $this
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
	public function cancelled()
    {
        $this->segment->setCancelled(true);
        return $this;
    }

	/**
	 * @param $seats
	 * @return FlightExtra
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
	 * @return FlightExtra
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
	 * @return FlightExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function stops($stops, $allowEmpty = false, $allowNull = false) {
		$this->segment->setStops($stops, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $smoking
	 * @return FlightExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function smoking($smoking) {
		$this->segment->setSmoking($smoking);
		return $this;
	}

	/**
	 * @param $aircraft
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return FlightExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function aircraft($aircraft, $allowEmpty = false, $allowNull = false) {
		$this->segment->setAircraft($aircraft, $allowEmpty, $allowNull);
		return $this;
	}

    /**
     * @param $regNum
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return FlightExtra
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function regNum($regNum, $allowEmpty = false, $allowNull = false)
    {
        $this->segment->setRegistrationNumber($regNum, $allowEmpty, $allowNull);
        return $this;
    }

	/**
	 * @param $miles
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return FlightExtra
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
	 * @return FlightExtra
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
	 * @return FlightExtra
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
	 * @return FlightExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function duration($duration, $allowEmpty = false, $allowNull = false) {
		$this->segment->setDuration($duration, $allowEmpty, $allowNull);
		return $this;
	}

    /**
     * @param $meals
     * @return FlightExtra
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
     * @return FlightExtra
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function meal($meal, $allowEmpty = false, $allowNull = false) {
        $this->segment->addMeal($meal, $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @return FlightExtra
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
	public function transit()
    {
        $this->segment->setTransit(true);
        return $this;
    }

}