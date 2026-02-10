<?php

namespace AwardWallet\Schema\Parser\Common\Shortcut;


use AwardWallet\Schema\Parser\Common\TrainSegment;
use AwardWallet\Schema\Parser\Component\InvalidDataException;

class TrainExtra {

	/** @var TrainSegment $segment */
	protected $segment;

	public function __construct(TrainSegment $segment) {
		$this->segment = $segment;
	}

	/**
	 * @param $number
	 * @return TrainExtra
	 * @throws InvalidDataException
	 */
	public function number($number) {
		$this->segment->setNumber($number);
		return $this;
	}

	/**
	 * @return TrainExtra
	 * @throws InvalidDataException
	 */
	public function noNumber() {
		$this->segment->setNoNumber(true);
		return $this;
	}

	/**
	 * @param $type
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TrainExtra
	 * @throws InvalidDataException
	 */
	public function type($type, $allowEmpty = false, $allowNull = false) {
		$this->segment->setTrainType($type, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $model
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TrainExtra
	 * @throws InvalidDataException
	 */
	public function model($model, $allowEmpty = false, $allowNull = false) {
		$this->segment->setTrainModel($model, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $car
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TrainExtra
	 * @throws InvalidDataException
	 */
	public function car($car, $allowEmpty = false, $allowNull = false) {
		$this->segment->setCarNumber($car, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $service
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TrainExtra
	 * @throws InvalidDataException
	 */
	public function service($service, $allowEmpty = false, $allowNull = false) {
		$this->segment->setServiceName($service, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $seats
	 * @return TrainExtra
	 * @throws InvalidDataException
	 */
	public function seats($seats) {
		$this->segment->setSeats($seats);
		return $this;
	}

	/**
	 * @param $seat
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TrainExtra
	 * @throws InvalidDataException
	 */
	public function seat($seat, $allowEmpty = false, $allowNull = false) {
		$this->segment->addSeat($seat, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $stops
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TrainExtra
	 * @throws InvalidDataException
	 */
	public function stops($stops, $allowEmpty = false, $allowNull = false) {
		$this->segment->setStops($stops, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $smoking
	 * @return TrainExtra
	 * @throws InvalidDataException
	 */
	public function smoking($smoking) {
		$this->segment->setSmoking($smoking);
		return $this;
	}

	/**
	 * @param $miles
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TrainExtra
	 * @throws InvalidDataException
	 */
	public function miles($miles, $allowEmpty = false, $allowNull = false) {
		$this->segment->setMiles($miles, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $cabin
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TrainExtra
	 * @throws InvalidDataException
	 */
	public function cabin($cabin, $allowEmpty = false, $allowNull = false) {
		$this->segment->setCabin($cabin, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $bookingCode
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TrainExtra
	 * @throws InvalidDataException
	 */
	public function bookingCode($bookingCode, $allowEmpty = false, $allowNull = false) {
		$this->segment->setBookingCode($bookingCode, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $duration
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TrainExtra
	 * @throws InvalidDataException
	 */
	public function duration($duration, $allowEmpty = false, $allowNull = false) {
		$this->segment->setDuration($duration, $allowEmpty, $allowNull);
		return $this;
	}

    /**
     * @param $meals
     * @return TrainExtra
     * @throws InvalidDataException
     */
    public function meals($meals) {
        $this->segment->setMeals($meals);
        return $this;
    }

    /**
     * @param $meal
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return TrainExtra
     * @throws InvalidDataException
     */
    public function meal($meal, $allowEmpty = false, $allowNull = false) {
        $this->segment->addMeal($meal, $allowEmpty, $allowNull);
        return $this;
    }

	/**
	 * @param $status
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TrainExtra
	 * @throws InvalidDataException
	 */
	public function status($status, $allowEmpty = false, $allowNull = false) {
		$this->segment->setStatus($status, $allowEmpty, $allowNull);
		return $this;
	}

    /**
     * @return TrainExtra
     * @throws InvalidDataException
     */
    public function cancelled()
    {
        $this->segment->setCancelled(true);
        return $this;
    }

    /**
     * @param $link
     * @param null $name
     * @return TrainExtra
     * @throws InvalidDataException
     */
    public function link($link, $name = null)
    {
        $this->segment->addLink($link, $name);
        return $this;
    }


}