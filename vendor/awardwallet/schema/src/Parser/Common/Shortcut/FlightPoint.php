<?php

namespace AwardWallet\Schema\Parser\Common\Shortcut;


use AwardWallet\Schema\Parser\Common\FlightSegment;

class FlightPoint {

	/** @var FlightSegment $segment */
	protected $segment;

	protected $type;

	public function __construct(FlightSegment $segment, $type) {
		$this->segment = $segment;
		$this->type = $type;
	}

	/**
	 * @param $code
	 * @return FlightPoint
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function code($code) {
		if ($this->type === 'd')
			$this->segment->setDepCode($code);
		else
			$this->segment->setArrCode($code);
		return $this;
	}

	/**
	 * @param $name
	 * @return FlightPoint
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function name($name) {
		if ($this->type === 'd')
			$this->segment->setDepName($name);
		else
			$this->segment->setArrName($name);
		return $this;
	}

	/**
	 * @param $date
	 * @return FlightPoint
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function date($date) {
		if ($this->type === 'd')
			$this->segment->setDepDate($date);
		else
			$this->segment->setArrDate($date);
		return $this;
	}

    /**
     * @return FlightPoint
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
	public function strict()
    {
        $this->segment->setDatesStrict(true);
        return $this;
    }

	/**
	 * @param $date
	 * @return FlightPoint
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function day($date) {
		if ($this->type === 'd')
			$this->segment->setDepDay($date);
		else
			$this->segment->setArrDay($date);
		return $this;
	}

	/**
	 * @param $date
	 * @param $relative
	 * @param string $format
	 * @param bool $after
	 * @return FlightPoint
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function date2($date, $relative = null, $format = '%D% %Y%', $after = true) {
		if ($this->type === 'd')
			$this->segment->parseDepDate($date, $relative, $format, $after);
		else
			$this->segment->parseArrDate($date, $relative, $format, $after);
		return $this;
	}

	/**
	 * @param $date
	 * @param $relative
	 * @param string $format
	 * @param bool $after
	 * @return FlightPoint
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function day2($date, $relative = null, $format = '%D% %Y%', $after = true) {
		if ($this->type === 'd')
			$this->segment->parseDepDay($date, $relative, $format, $after);
		else
			$this->segment->parseArrDay($date, $relative, $format, $after);
		return $this;
	}

	/**
	 * @param $terminal
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return FlightPoint
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function terminal($terminal, $allowEmpty = false, $allowNull = false) {
		if ($this->type === 'd')
			$this->segment->setDepTerminal($terminal, $allowEmpty, $allowNull);
		else
			$this->segment->setArrTerminal($terminal, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return FlightPoint
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function noCode() {
		if ($this->type === 'd')
			$this->segment->setNoDepCode(true);
		else
			$this->segment->setNoArrCode(true);
		return $this;
	}

	/**
	 * @return FlightPoint
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function noDate() {
		if ($this->type === 'd')
			$this->segment->setNoDepDate(true);
		else
			$this->segment->setNoArrDate(true);
		return $this;
	}

}