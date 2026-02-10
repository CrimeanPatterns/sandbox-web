<?php

namespace AwardWallet\Schema\Parser\Common;


use AwardWallet\Common\DateTimeUtils;
use AwardWallet\Schema\Parser\Common\Shortcut\BusExtra;
use AwardWallet\Schema\Parser\Common\Shortcut\Point;
use AwardWallet\Schema\Parser\Component\Base;
use AwardWallet\Schema\Parser\Component\Options;
use Psr\Log\LoggerInterface;

class BusSegment extends BaseSegment {

	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=short
	 */
	protected $number;
	/**
	 * @parsed Boolean
	 */
	protected $noNumber;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=medium
	 */
	protected $busType;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=medium
	 */
	protected $busModel;
	/**
	 * @parsed Field
	 * @attr regexp=/^[A-Za-z\d ]{1,10}$/
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
	 * @attr regexp=/^[A-Za-z\d ]{1,10}$/
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
	 * @attr type=clean
	 * @attr length=short
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
	 * @attr item_type=basic
	 * @attr item_length=short
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
	 * @attr length=short
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

	/** @var  Point $_dep */
	protected $_dep;
	/** @var  Point $_arr */
	protected $_arr;
	/** @var  BusExtra $_ext */
	protected $_ext;

	public function __construct($name, LoggerInterface $logger, Options $options = null) {
		parent::__construct($name, $logger, $options);
		$this->_dep = new Point($this, 'd');
		$this->_arr = new Point($this, 'a');
		$this->_ext = new BusExtra($this);
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
	 * @return BusExtra
	 */
	public function extra() {
		return $this->_ext;
	}

	/**
	 * @return string
	 */
	public function getNumber() {
		return $this->number;
	}

	/**
	 * @param string $number
	 * @return BusSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setNumber($number) {
		$this->setProperty($number, 'number', false, false);
		return $this;
	}

	/**
	 * @return bool
	 */
	public function getNoNumber() {
		return $this->noNumber;
	}

	/**
	 * @param bool $noNumber
	 * @return BusSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setNoNumber($noNumber) {
		$this->setProperty($noNumber, 'noNumber', false, false);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getBusType() {
		return $this->busType;
	}

	/**
	 * @param string $busType
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return BusSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setBusType($busType, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($busType, 'busType', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getBusModel() {
		return $this->busModel;
	}

	/**
	 * @param string $busModel
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return BusSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setBusModel($busModel, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($busModel, 'busModel', $allowEmpty, $allowNull);
		return $this;
	}


    public function validateBasic()
    {
        $this->validateArrays();
        $this->checkEmpty(false);
        $this->checkIdentical(true, true);
        if ($this->arrDate && $this->depDate) {
            if ($this->arrDate < $this->depDate) {
                $this->invalid('invalid dates');
            }
            if (abs($this->arrDate - $this->depDate) > 5 * DateTimeUtils::SECONDS_PER_DAY) {
                $this->invalid('dates are too far apart');
            }
        }
        return $this->valid;
    }

	/**
	 * checks data and sets valid flag
	 * @return bool
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function validateData()
    {
		if (empty($this->depName) && empty($this->depAddress) && empty($this->depCode))
			$this->invalid('missing departure location');
		if (empty($this->arrName) && empty($this->arrAddress) && empty($this->arrCode))
		    $this->invalid('missing arrival location');
		if (empty($this->depDate) && $this->noDepDate !== true)
			$this->invalid('missing depDate');
		if (empty($this->arrDate) && $this->noArrDate !== true)
			$this->invalid('missing arrDate');
		return $this->valid;
	}

	/**
	 * @return Base[]
	 */
	protected function getChildren() {
		return [];
	}
}