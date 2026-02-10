<?php

namespace AwardWallet\Schema\Parser\Common;

use AwardWallet\Common\DateTimeUtils;
use AwardWallet\Schema\Parser\Common\Shortcut\TrainExtra;
use AwardWallet\Schema\Parser\Common\Shortcut\Point;
use AwardWallet\Schema\Parser\Component\Base;
use AwardWallet\Schema\Parser\Component\InvalidDataException;
use AwardWallet\Schema\Parser\Component\Options;
use Psr\Log\LoggerInterface;


class TrainSegment extends BaseSegment {

	/**
	 * @parsed Field
	 * @attr type=clean
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
	protected $trainType;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=medium
	 */
	protected $trainModel;
	/**
	 * @parsed Field
	 * @attr regexp=/^[A-Za-z\d \-.,]{1,20}$/
	 */
	protected $carNumber;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=medium
	 */
	protected $serviceName;
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
    /**
     * @parsed KeyValue
     * @attr unique=strict
     * @attr key=Field
     * @attr key_regexp=/^https?:\/\/[\S ]+$/
     * @attr key_maxlength=2000
     * @attr val=Field
     * @attr val_type=basic
     * @attr val_length=medium
     */
    protected $links;

	/** @var  Point $_dep */
	protected $_dep;
	/** @var  Point $_arr */
	protected $_arr;
	/** @var  TrainExtra $_ext */
	protected $_ext;

	public function __construct($name, LoggerInterface $logger, Options $options = null) {
		parent::__construct($name, $logger, $options);
		$this->_dep = new Point($this, 'd');
		$this->_arr = new Point($this, 'a');
		$this->_ext = new TrainExtra($this);
		$this->links = [];
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
	 * @return TrainExtra
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
	 * @return TrainSegment
	 * @throws InvalidDataException
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
	 * @return TrainSegment
	 * @throws InvalidDataException
	 */
	public function setNoNumber($noNumber) {
		$this->setProperty($noNumber, 'noNumber', false, false);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTrainType() {
		return $this->trainType;
	}

	/**
	 * @param string $trainType
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TrainSegment
	 * @throws InvalidDataException
	 */
	public function setTrainType($trainType, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($trainType, 'trainType', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTrainModel() {
		return $this->trainModel;
	}

	/**
	 * @param string $trainModel
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TrainSegment
	 * @throws InvalidDataException
	 */
	public function setTrainModel($trainModel, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($trainModel, 'trainModel', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCarNumber() {
		return $this->carNumber;
	}

	/**
	 * @param string $carNumber
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TrainSegment
	 * @throws InvalidDataException
	 */
	public function setCarNumber($carNumber, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($carNumber, 'carNumber', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getServiceName() {
		return $this->serviceName;
	}

	/**
	 * @param string $serviceName
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TrainSegment
	 * @throws InvalidDataException
	 */
	public function setServiceName($serviceName, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($serviceName, 'serviceName', $allowEmpty, $allowNull);
		return $this;
	}

	public function getLinks()
    {
        return $this->links;
    }

    /**
     * @param string $link
     * @param string $name
     * @return TrainSegment
     * @throws InvalidDataException
     */
	public function addLink($link, $name = null)
    {
        $this->addKeyValue($link, $name, 'links', false, true, []);
        return $this;
    }

    public function validateBasic()
    {
        $this->validateArrays();
        $this->checkEmpty(false);
        $this->checkIdentical(true, true);

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
     * @throws InvalidDataException
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
        if (empty($this->number) && $this->noNumber !== true)
            $this->invalid('missing number');
        return $this->valid;
    }

	/**
	 * @return Base[]
	 */
	protected function getChildren() {
		return [];
	}
	
}