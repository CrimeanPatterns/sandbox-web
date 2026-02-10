<?php

namespace AwardWallet\Schema\Parser\Common\Shortcut;


use AwardWallet\Schema\Parser\Common\FlightSegment;

class FlightAirline {

	/** @var FlightSegment $segment */
	protected $segment;

	public function __construct(FlightSegment $segment) {
		$this->segment = $segment;
	}

	/**
	 * @param $name
	 * @return FlightAirline
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function name($name) {
		$this->segment->setAirlineName($name);
		return $this;
	}

	/**
	 * @param $number
	 * @return FlightAirline
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function number($number) {
		$this->segment->setFlightNumber($number);
		return $this;
	}

	/**
	 * @param $number
	 * @return FlightAirline
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function confirmation($number) {
		$this->segment->setConfirmation($number);
		return $this;
	}

	/**
	 * @param $name
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return FlightAirline
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function operator($name, $allowEmpty = false, $allowNull = false) {
		$this->segment->setOperatedBy($name, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return FlightAirline
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function wetlease() {
		$this->segment->setIsWetlease(true);
		return $this;
	}

	/**
	 * @param $name
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return FlightAirline
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function carrierName($name, $allowEmpty = false, $allowNull = false) {
		$this->segment->setCarrierAirlineName($name, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $number
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return FlightAirline
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function carrierNumber($number, $allowEmpty = false, $allowNull = false) {
		$this->segment->setCarrierFlightNumber($number, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $number
	 * @return FlightAirline
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function carrierConfirmation($number) {
		$this->segment->setCarrierConfirmation($number);
		return $this;
	}

	/**
	 * @return FlightAirline
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function noNumber() {
		$this->segment->setNoFlightNumber(true);
		return $this;
	}

	/**
	 * @return FlightAirline
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function noName() {
		$this->segment->setNoAirlineName(true);
		return $this;
	}

}