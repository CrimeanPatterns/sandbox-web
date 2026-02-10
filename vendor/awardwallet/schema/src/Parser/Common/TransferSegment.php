<?php

namespace AwardWallet\Schema\Parser\Common;

use AwardWallet\Common\DateTimeUtils;
use AwardWallet\Schema\Parser\Common\Shortcut\TransferExtra;
use AwardWallet\Schema\Parser\Common\Shortcut\Point;
use AwardWallet\Schema\Parser\Component\Base;
use AwardWallet\Schema\Parser\Component\InvalidDataException;
use AwardWallet\Schema\Parser\Component\Options;
use Psr\Log\LoggerInterface;

class TransferSegment extends BaseSegment {

	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=medium
	 */
	protected $carType;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=medium
	 */
	protected $carModel;
	/**
	 * @parsed Field
	 * @attr regexp=/^https?:\/\/\S+$/
	 * @attr maxlength=2000
	 */
	protected $carImageUrl;
	/**
	 * @parsed Field
	 * @attr type=number
	 * @attr max=90
	 */
	protected $adults;
	/**
	 * @parsed Field
	 * @attr type=number
	 * @attr max=90
	 */
	protected $kids;
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
     * @attr length=short
     * @attr minlength=2
     */
    protected $depGeoTip;
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
     * @attr length=short
     * @attr minlength=2
     */
    protected $arrGeoTip;
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
	 * @parsed Boolean
	 */
	protected $noDepDate;
	/**
	 * @parsed Boolean
	 */
	protected $noArrDate;
    /**
     * @var FlightSegment
     */
	protected $depFlightSegment;
    /**
     * @var FlightSegment
     */
	protected $arrFlightSegment;
    /**
     * @parsed Field
     * @attr type=basic
     * @attr maxlength=25
     */
	protected $flightDateCorrection;
    /**
     * @parsed Boolean
     */
    protected $datesStrict;
	/**
	 * @parsed Field
	 * @attr type=clean
	 * @attr length=short
	 */
	protected $status;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=short
	 */
	protected $miles;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=short
	 */
	protected $duration;

	/** @var  Point $_dep */
	protected $_dep;
	/** @var  Point $_arr */
	protected $_arr;
	/** @var  TransferExtra $_ext */
	protected $_ext;

	public function __construct($name, LoggerInterface $logger, Options $options = null) {
		parent::__construct($name, $logger, $options);
		$this->_dep = new Point($this, 'd');
		$this->_arr = new Point($this, 'a');
		$this->_ext = new TransferExtra($this);
	}

	/**
	 * @return Point
	 */
	public function arrival() {
		return $this->_arr;
	}

	/**
	 * @return Point
	 */
	public function departure() {
		return $this->_dep;
	}

	/**
	 * @return TransferExtra
	 */
	public function extra() {
		return $this->_ext;
	}

	/**
	 * @return string
	 */
	public function getCarType() {
		return $this->carType;
	}

	/**
	 * @param string $carType
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TransferSegment
	 * @throws InvalidDataException
	 */
	public function setCarType($carType, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($carType, 'carType', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getCarModel() {
		return $this->carModel;
	}

	/**
	 * @param string $carModel
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TransferSegment
	 * @throws InvalidDataException
	 */
	public function setCarModel($carModel, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($carModel, 'carModel', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCarImageUrl() {
		return $this->carImageUrl;
	}

	/**
	 * @param string $carImageUrl
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TransferSegment
	 * @throws InvalidDataException
	 */
	public function setCarImageUrl($carImageUrl, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($carImageUrl, 'carImageUrl', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getAdults() {
		return $this->adults;
	}

	/**
	 * @param string $adults
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TransferSegment
	 * @throws InvalidDataException
	 */
	public function setAdults($adults, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($adults, 'adults', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getKids() {
		return $this->kids;
	}

	/**
	 * @param string $kids
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TransferSegment
	 * @throws InvalidDataException
	 */
	public function setKids($kids, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($kids, 'kids', $allowEmpty, $allowNull);
		return $this;
	}

    /**
     * @return FlightSegment
     */
    public function getDepFlightSegment(): ?FlightSegment
    {
        return $this->depFlightSegment;
    }

    /**
     * @param FlightSegment $depFlightSegment
     * @return TransferSegment
     */
    public function setDepFlightSegment(FlightSegment $depFlightSegment): TransferSegment
    {
        $this->depFlightSegment = $depFlightSegment;
        return $this;
    }

    /**
     * @return FlightSegment
     */
    public function getArrFlightSegment(): ?FlightSegment
    {
        return $this->arrFlightSegment;
    }

    /**
     * @param FlightSegment $arrFlightSegment
     * @return TransferSegment
     */
    public function setArrFlightSegment(FlightSegment $arrFlightSegment): TransferSegment
    {
        $this->arrFlightSegment = $arrFlightSegment;
        return $this;
    }

    /**
     * @return string
     */
    public function getFlightDateCorrection(): ?string
    {
        return $this->flightDateCorrection;
    }

    /**
     * @param mixed $flightDateCorrection
     * @return TransferSegment
     * @throws InvalidDataException
     */
    public function setFlightDateCorrection($flightDateCorrection): TransferSegment
    {
        $this->setProperty($flightDateCorrection, 'flightDateCorrection', false, false);
        if (false === strtotime($flightDateCorrection)) {
            $this->invalid('flightDateCorrection invalid format');
        }
        return $this;
    }

    public function validateBasic($allowTzCross)
    {
        $this->checkEmpty(true);
        $this->checkIdentical(false, true);
        if ($this->arrDate && $this->depDate) {
            if ($this->arrDate < $this->depDate && !$allowTzCross) {
                $this->invalid('invalid dates');
            }
            if (abs($this->arrDate - $this->depDate) > 2 * DateTimeUtils::SECONDS_PER_DAY) {
                $this->invalid('dates are too far apart');
            }
        }
        if (isset($this->flightDateCorrection) && !isset($this->depFlightSegment) && !isset($this->arrFlightSegment)) {
            $this->invalid('flightDateCorrection is set, but the flight segment is not');
        }
        if (isset($this->depFlightSegment) && isset($this->arrFlightSegment)) {
            $this->invalid('both dep and arr flightSegments are set');
        }
        return $this->valid;
    }

    /**
     * checks data and sets valid flag
     * @return bool
     * @throws InvalidDataException
     */
    public function validateData()
    {
        if (empty($this->depName) && empty($this->depAddress) && empty($this->depCode))
            $this->invalid('missing departure location');
        if (!empty($this->depName) && stripos($this->depName, 'pickup') !== false)
            $this->invalid('possibly invalid departure location');
        if (empty($this->arrName) && empty($this->arrAddress) && empty($this->arrCode))
            $this->invalid('missing arrival location');
        if (!empty($this->arrName) && stripos($this->arrName, 'dropoff') !== false)
            $this->invalid('possibly invalid arrival location');
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
        return $this->valid;
    }

	/**
	 * @return Base[]
	 */
	protected function getChildren() {
		return [];
	}
	
}