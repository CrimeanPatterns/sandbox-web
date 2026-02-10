<?php

namespace AwardWallet\Schema\Parser\Common;


use AwardWallet\Common\DateTimeUtils;
use AwardWallet\Schema\Parser\Common\Shortcut\FlightAirline;
use AwardWallet\Schema\Parser\Common\Shortcut\FlightExtra;
use AwardWallet\Schema\Parser\Common\Shortcut\FlightPoint;
use AwardWallet\Schema\Parser\Component\Base;
use AwardWallet\Schema\Parser\Component\Options;
use Psr\Log\LoggerInterface;

class FlightSegment extends BaseSegment {

    public const FAKE_AIR_CODES = ['HDQ'];

	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=short
	 */
	protected $depTerminal;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=short
	 */
	protected $arrTerminal;
	/**
	 * @parsed Field
     * @attr regexp=/^[^<>@$%\{\}]+$/
	 * @attr length=short
	 */
	protected $airlineName;
	/**
	 * @parsed Field
	 * @attr regexp=/^\d{1,5}$/
	 */
	protected $flightNumber;
	/**
	 * @parsed Field
	 * @attr regexp=/^[A-Z\d]{5,10}$/
	 */
	protected $confirmation;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=short
	 */
	protected $operatedBy;
	/**
	 * @parsed Boolean
	 */
	protected $isWetlease;
	/**
	 * @parsed Field
	 * @attr regexp=/^\d{1,5}$/
	 */
	protected $carrierFlightNumber;
	/**
	 * @parsed Field
     * @attr regexp=/^[^<>@$%\{\}]+$/
	 * @attr length=short
	 */
	protected $carrierAirlineName;
	/**
	 * @parsed Field
	 * @attr regexp=/^[A-Z\d]{5,10}$/
	 */
	protected $carrierConfirmation;
	/**
	 * @parsed Boolean
	 */
	protected $noAirlineName;
	/**
	 * @parsed Boolean
	 */
	protected $noFlightNumber;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=medium
	 */
	protected $aircraft;
    /**
     * @parsed Field
     * @attr type=basic
     * @attr length=short
     */
    protected $registrationNumber;
	/**
	 * @parsed Field
	 * @attr regexp=/^[A-Z]{3}$/
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
     * @parsed DateTime
     */
    protected $depDay;
	/**
	 * @parsed Field
	 * @attr regexp=/^[A-Z]{3}$/
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
     * @parsed DateTime
     */
    protected $arrDay;
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
     * @attr type=sentence
     * @attr length=medium
	 */
	protected $status;
    /**
     * @parsed Boolean
     */
	protected $cancelled;
	/**
	 * @parsed Arr
	 * @attr item=Field
	 * @attr unique=true
	 * @attr item_regexp=/^[A-Z\d\-\\\/]{1,7}$/
	 *
	 */
	protected $seats;
	/**
	 * @parsed Field
	 * @attr type=number
	 * @attr max=10
	 */
	protected $stops;
    /**
     * @parsed Boolean
     */
	protected $transit;
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
	 * @attr length=70
	 */
	protected $cabin;
	/**
	 * @parsed Field
	 * @attr type=clean
	 * @attr length=short
	 */
	protected $bookingCode;
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

	/** @var FlightPoint $_dep */
	protected $_dep;
	/** @var FlightPoint $_arr */
	protected $_arr;
	/** @var FlightAirline $_air */
	protected $_air;
	/** @var FlightExtra $_ext */
	protected $_ext;

	public function __construct($name, LoggerInterface $logger, Options $options = null) {
		parent::__construct($name, $logger, $options);
		$this->_dep = new FlightPoint($this, 'd');
		$this->_arr = new FlightPoint($this, 'a');
		$this->_air = new FlightAirline($this);
		$this->_ext = new FlightExtra($this);
	}

	/**
	 * @return FlightPoint
	 */
	public function arrival() {
		return $this->_arr;
	}

	/**
	 * @return FlightPoint
	 */
	public function departure() {
		return $this->_dep;
	}

	/**
	 * @return FlightAirline
	 */
	public function airline() {
		return $this->_air;
	}

	/**
	 * @return FlightExtra
	 */
	public function extra() {
		return $this->_ext;
	}

	/* -------- getters and setters -------- */
	// region GS

	/**
	 * @return mixed
	 */
	public function getDepTerminal() {
		return $this->depTerminal;
	}

	/**
	 * @param mixed $depTerminal
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return FlightSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setDepTerminal($depTerminal, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($depTerminal, 'depTerminal', $allowEmpty, $allowNull);
		return $this;
	}

    /**
     * @param $depTerminal
     * @param string $terminalWord
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return FlightSegment
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
	public function parseDepTerminal($depTerminal, $terminalWord = 'terminal', $allowEmpty = false, $allowNull = false)
    {
        if (is_string($depTerminal) && strlen($depTerminal) > 0)
            $depTerminal = $this->parseTerminal($depTerminal, $terminalWord);
        return $this->setDepTerminal($depTerminal, $allowEmpty, $allowNull);
    }

	/**
	 * @return mixed
	 */
	public function getArrTerminal() {
		return $this->arrTerminal;
	}

	/**
	 * @param mixed $arrTerminal
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return FlightSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setArrTerminal($arrTerminal, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($arrTerminal, 'arrTerminal', $allowEmpty, $allowNull);
		return $this;
	}

    /**
     * @param $arrTerminal
     * @param string $terminalWord
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return FlightSegment
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function parseArrTerminal($arrTerminal, $terminalWord = 'terminal', $allowEmpty = false, $allowNull = false)
    {
        if (is_string($arrTerminal) && strlen($arrTerminal) > 0)
            $arrTerminal = $this->parseTerminal($arrTerminal, $terminalWord);
        return $this->setArrTerminal($arrTerminal, $allowEmpty, $allowNull);
    }

	/**
	 * @return mixed
	 */
	public function getFlightNumber() {
		return $this->flightNumber;
	}

	/**
	 * @param mixed $flightNumber
	 * @return FlightSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setFlightNumber($flightNumber) {
		$this->setProperty($flightNumber, 'flightNumber', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getAirlineName() {
		return $this->airlineName;
	}

	/**
	 * @param mixed $airlineName
	 * @return FlightSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setAirlineName($airlineName) {
		$this->setProperty($airlineName, 'airlineName', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getOperatedBy() {
		return $this->operatedBy;
	}

	/**
	 * @param mixed $operatedBy
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return FlightSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setOperatedBy($operatedBy, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($operatedBy, 'operatedBy', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return bool
	 */
	public function getIsWetlease() {
		return $this->isWetlease;
	}

	/**
	 * @param bool $isWetlease
	 * @return FlightSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setIsWetlease($isWetlease) {
		$this->setProperty($isWetlease, 'isWetlease', false, false);
		if ($isWetlease === false)
			$this->invalid('set carrierAirlineName instead of wetlease=false');
		return $this;
	}

    /**
     * @return mixed
     */
	public function getTransit()
    {
        return $this->transit;
    }

    /**
     * @param $transit
     * @return FlightSegment
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setTransit($transit)
    {
        $this->setProperty($transit, 'transit', false, false);
        return $this;
    }

	/**
	 * @return mixed
	 */
	public function getCarrierFlightNumber() {
		return $this->carrierFlightNumber;
	}

	/**
	 * @param mixed $carrierFlightNumber
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return FlightSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setCarrierFlightNumber($carrierFlightNumber, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($carrierFlightNumber, 'carrierFlightNumber', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getCarrierAirlineName() {
		return $this->carrierAirlineName;
	}

	/**
	 * @param mixed $carrierAirlineName
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return FlightSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setCarrierAirlineName($carrierAirlineName, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($carrierAirlineName, 'carrierAirlineName', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getNoFlightNumber() {
		return $this->noFlightNumber;
	}

	/**
	 * @param mixed $noFlightNumber
	 * @return FlightSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setNoFlightNumber($noFlightNumber) {
		$this->setProperty($noFlightNumber, 'noFlightNumber', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getNoAirlieName() {
		return $this->noAirlineName;
	}

	/**
	 * @param mixed $noAirlineName
	 * @return FlightSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setNoAirlineName($noAirlineName) {
		$this->setProperty($noAirlineName, 'noAirlineName', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getAircraft() {
		return $this->aircraft;
	}

	/**
	 * @param mixed $aircraft
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return FlightSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setAircraft($aircraft, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($aircraft, 'aircraft', $allowEmpty, $allowNull);
		return $this;
	}

    /**
     * @return mixed
     */
    public function getRegistrationNumber() {
        return $this->registrationNumber;
    }

    /**
     * @param string $regNum
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return FlightSegment
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setRegistrationNumber($regNum, $allowEmpty = false, $allowNull = false) {
        $this->setProperty($regNum, 'registrationNumber', $allowEmpty, $allowNull);
        return $this;
    }

	/**
	 * @return mixed
	 */
	public function getConfirmation() {
		return $this->confirmation;
	}

	/**
	 * @param mixed $confirmation
	 * @return FlightSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setConfirmation($confirmation) {
		$this->setProperty($confirmation, 'confirmation', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getCarrierConfirmation() {
		return $this->carrierConfirmation;
	}

	/**
	 * @param mixed $carrierConfirmation
	 * @return FlightSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setCarrierConfirmation($carrierConfirmation) {
		$this->setProperty($carrierConfirmation, 'carrierConfirmation', false, false);
		return $this;
	}

    /**
     * @return mixed
     */
    public function getDepDay() {
        return $this->depDay;
    }

    /**
     * @param mixed $depDay
     * @return FlightSegment
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setDepDay($depDay) {
        $this->setProperty($depDay, 'depDay', false, false);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getArrDay() {
        return $this->arrDay;
    }

    /**
     * @param mixed $arrDay
     * @return FlightSegment
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setArrDay($arrDay) {
        $this->setProperty($arrDay, 'arrDay', false, false);
        return $this;
    }

    // endregion GS

	/**
	 * @return Base[]
	 */
	protected function getChildren() {
		return [];
	}

    private function parseTerminal($val, $word)
    {
        if (preg_match(sprintf('/^%s\s+([\dA-Z]{1,3})$/i', preg_quote($word)), $val, $m) > 0)
            return $m[1];
        elseif (preg_match(sprintf('/^([\dA-Z]{1,3})\s+%s$/i', preg_quote($word)), $val, $m) > 0)
            return $m[1];
        else
            return $val;
    }

    /**
     * @param $date
     * @param $relative
     * @param bool $after
     * @param string $format
     * @return FlightSegment
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function parseDepDay($date, $relative = null, $format = '%D% %Y%', $after = true) {
        $this->parseUnixTimeProperty($date, 'depDay', $relative, $after, $format);
        return $this;
    }

    /**
     * @param $date
     * @param $relative
     * @param bool $after
     * @param string $format
     * @return FlightSegment
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function parseArrDay($date, $relative = null, $format = '%D% %Y%', $after = true) {
        $this->parseUnixTimeProperty($date, 'arrDay', $relative, $after, $format);
        return $this;
    }

    public function validateBasic()
    {
        $this->validateArrays();
        $this->checkEmpty(true);
        $this->checkIdentical(true, false);
        if (!empty($this->depDate) && !empty($this->depDay))
            $this->invalid('wrong parsing: depDate XOR depDay');
        if (!empty($this->depDay) && date('H:i:s', $this->depDay)!=='00:00:00')
            $this->invalid('wrong parsing: depDay with time');
        if (!empty($this->arrDate) && !empty($this->arrDay))
            $this->invalid('wrong parsing: arrDate XOR arrDay');
        if (!empty($this->arrDay) && date('H:i:s', $this->arrDay)!=='00:00:00')
            $this->invalid('wrong parsing: arrDay with time');
        if ((!empty($this->carrierConfirmation) || !empty($this->carrierFlightNumber)) && empty($this->carrierAirlineName))
            $this->invalid('need carrierAirlineName to parse the rest of carrier data');
        if (empty($this->operatedBy) && isset($this->isWetlease))
            $this->invalid('need operatedBy to set flag isWetlease');
        if ($this->airlineName && ($this->airlineName === $this->carrierAirlineName))
            $this->invalid('airlineName and carrierAirlineName cannot be identical');
        if (($d = $this->getDepDate()) && ($a = $this->getArrDate()) && ($diff = abs($a - $d)))
            if ($d > $a && $diff > DateTimeUtils::SECONDS_PER_DAY * 360 && $diff < DateTimeUtils::SECONDS_PER_DAY * 365 && date('m', $d) === '12' && date('m', $a) === '01')
                $this->logNotice(sprintf('%s: dates are far apart but it looks like year didn\'t carry over', $this->getId()));
            elseif ($diff > DateTimeUtils::SECONDS_PER_DAY * 5)
                $this->invalid('dates are too far apart');
        return $this->valid;
    }

    /**
     * checks data and sets valid flag
     * @return bool
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function validateData()
    {
        if (empty($this->depCode) && $this->noDepCode !== true)
            $this->invalid('missing depCode');
        if (empty($this->arrCode) && $this->noArrCode !== true)
            $this->invalid('missing arrCode');
        if (empty($this->airlineName) && $this->noAirlineName !== true)
            $this->invalid('missing airline name');
        if (empty($this->depCode) && empty($this->depName) && (empty($this->flightNumber) || empty($this->airlineName)))
            $this->invalid('missing departure location');
        if (empty($this->arrCode) && empty($this->arrName) && (empty($this->flightNumber) || empty($this->airlineName)))
            $this->invalid('missing arrival location');
        if (empty($this->depDate) && $this->noDepDate !== true)
            $this->invalid('missing depDate');
        if ($this->noDepDate && !empty($this->depDate)) {
            $this->invalid('invalid depDate/noDepDate');
        }
        if (empty($this->arrDate) && $this->noArrDate !== true)
            $this->invalid('missing arrDate');
        if ($this->noArrDate && !empty($this->arrDate)) {
            $this->invalid('invalid arrDate/noArrDate');
        }
        if ((!isset($this->flightNumber) || strlen($this->flightNumber) == 0) && $this->noFlightNumber !== true)
            $this->invalid('missing flightNumber');
        return $this->valid;
    }

    public function hasFakeCodes(): bool
    {
        return in_array($this->depCode, self::FAKE_AIR_CODES) || in_array($this->arrCode, self::FAKE_AIR_CODES);
    }

}