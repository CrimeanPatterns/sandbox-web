<?php

namespace AwardWallet\Schema\Parser\Common\Shortcut;


use AwardWallet\Schema\Parser\Common\Flight;

class FlightIssued {

	/** @var Flight $parent */
	protected $parent;
	
	public function __construct(Flight $parent) {
		$this->parent = $parent;
		return $this;
	}

	/**
	 * @param $s
	 * @return FlightIssued
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function name($s) {
		$this->parent->setIssuingAirlineName($s);
		return $this;
	}

	/**
	 * @param $s
	 * @return FlightIssued
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function provider($s) {
		$this->parent->setIssuingAirlineCode($s);
		return $this;
	}

	/**
	 * @param $s
	 * @param boolean $masked
	 * @return FlightIssued
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function ticket($s, $masked) {
		$this->parent->addTicketNumber($s, $masked);
		return $this;
	}

	/**
	 * @param $a
	 * @param boolean $masked
	 * @return FlightIssued
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function tickets($a, $masked) {
		$this->parent->setTicketNumbers($a, $masked);
		return $this;
	}

	/**
	 * @param $s
	 * @return FlightIssued
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function confirmation($s) {
		$this->parent->setIssuingConfirmation($s);
		return $this;
	}

}