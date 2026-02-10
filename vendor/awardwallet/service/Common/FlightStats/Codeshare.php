<?php

namespace AwardWallet\Common\FlightStats;

use JMS\Serializer\Annotation as JMS;

class Codeshare {

	/**
	 * The FlightStats unique code for the operating carrier to use as a reference for finding the entry in the appendix
	 * (unless the extended option to include inlined references is used).
	 *
	 * @var string = null
	 * @JMS\Type("string")
	 */
	private $carrierFsCode;

	/**
	 * The flight identification number and any additional characters
	 *
	 * @var string
	 * @JMS\Type("string")
	 */
	private $flightNumber;

	/**
	 * The type of service offered for the flight
	 *
	 * @var string
	 * @JMS\Type("string")
	 */
	private $serviceType;

	/**
	 * IATA service classes offered for the flight.
	 *
	 * @var array = null
	 * @JMS\Type("array<string>")
	 */
	private $serviceClasses = null;

	/**
	 * IATA restrictions imposed on the flight.
	 *
	 * @var array = null
	 * @JMS\Type("array<string>")
	 */
	private $trafficRestrictions = null;

	/**
	 * Reference code for FlightStats' troubleshooting purposes.
	 *
	 * @var string
	 * @JMS\Type("string")
	 */
	private $referenceCode;

	/**
	 * @return string
	 */
	public function getCarrierFsCode() {
		return $this->carrierFsCode;
	}

	/**
	 * @return string
	 */
	public function getFlightNumber() {
		return $this->flightNumber;
	}

	/**
	 * @return string
	 */
	public function getServiceType() {
		return $this->serviceType;
	}

	/**
	 * @return array
	 */
	public function getServiceClasses() {
		return $this->serviceClasses;
	}

	/**
	 * @return array
	 */
	public function getTrafficRestrictions() {
		return $this->trafficRestrictions;
	}

	/**
	 * @return string
	 */
	public function getReferenceCode() {
		return $this->referenceCode;
	}

}