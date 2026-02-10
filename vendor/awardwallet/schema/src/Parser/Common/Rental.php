<?php

namespace AwardWallet\Schema\Parser\Common;


use AwardWallet\Schema\Parser\Common\Shortcut\Car;
use AwardWallet\Schema\Parser\Common\Shortcut\RentalExtra;
use AwardWallet\Schema\Parser\Common\Shortcut\RentalPoint;
use AwardWallet\Schema\Parser\Common\Shortcut\DetailedAddress as DAShortcut;
use AwardWallet\Schema\Parser\Component\Base;
use AwardWallet\Schema\Parser\Component\HasDetailedAddress;
use AwardWallet\Schema\Parser\Component\Options;
use Psr\Log\LoggerInterface;

class Rental extends Itinerary implements HasDetailedAddress {

	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=long
	 * @attr minlength=2
	 */
	protected $pickUpLocation;
	/**
	 * @parsed DateTime
	 */
	protected $pickUpDateTime;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=long
	 * @attr minlength=2
	 */
	protected $dropOffLocation;
	/**
	 * @parsed DateTime
	 */
	protected $dropOffDateTime;
	/**
	 * @parsed Boolean
	 */
	protected $noPickUpLocation;
	/**
	 * @parsed Boolean
	 */
	protected $noDropOffLocation;
	/**
	 * @parsed Boolean
	 */
	protected $noPickUpDate;
	/**
	 * @parsed Boolean
	 */
	protected $noDropOffDate;
	/**
	 * @parsed Field
	 * @attr type=phone
	 */
	protected $pickUpPhone;
	/**
	 * @parsed Field
	 * @attr type=phone
	 */
	protected $pickUpFax;
    /**
     * @parsed Arr
     * @attr item=Field
     * @attr item_type=basic
     * @attr item_length=medium
     * @attr item_minlength=4
     */
    protected $pickUpOpeningHours;
	/**
	 * @parsed Field
	 * @attr type=phone
	 */
	protected $dropOffPhone;
	/**
	 * @parsed Field
	 * @attr type=phone
	 */
	protected $dropOffFax;
    /**
     * @parsed Arr
     * @attr item=Field
     * @attr item_type=basic
     * @attr item_length=medium
     * @attr item_minlength=4
     */
    protected $dropOffOpeningHours;

	/** @var  DetailedAddress $pickupDetailedAddress */
	protected $pickupDetailedAddress;
	/** @var  DetailedAddress $dropoffDetailedAddress */
	protected $dropoffDetailedAddress;

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
	 * @attr regexp=/^https?:\/\/.+$/
	 * @attr maxlength=2000
	 */
	protected $carImageUrl;
	/**
	 * @parsed KeyValue
	 * @attr unique=false
	 * @attr key=Field
	 * @attr key_type=basic
	 * @attr key_length=medium
	 * @attr val=Field
	 * @attr val_type=basic
	 * @attr val_length=medium
	 */
	protected $discounts;
	/**
	 * @parsed KeyValue
	 * @attr unique=false
	 * @attr key=Field
	 * @attr key_type=basic
	 * @attr key_length=medium
	 * @attr val=Field
	 * @attr val_type=price
	 */
	protected $equipment;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=medium
	 */
	protected $company;
    /**
     * @parsed Boolean
     */
    protected $host;
	/**
	 * @parsed KeyValue
	 * @attr unique=strict
	 * @attr key=Field
	 * @attr key_type=confno
	 * @attr key_length=short
	 * @attr key_minlength=3
	 * @attr val=Field
	 * @attr val_type=basic
	 * @attr val_length=medium
	 */
	protected $confirmationNumbers;
	/**
	 * @parsed Boolean
	 */
	protected $noConfirmationNumber;
	protected $primaryConfirmationKey;
	/**
	 * @parsed DateTime
	 * @attr seconds=true
	 */
	protected $reservationDate;
	/**
	 * @parsed Field
	 * @attr type=sentence
	 * @attr length=medium
	 */
	protected $status;
	/**
	 * @parsed KeyValue
	 * @attr unique=false
	 * @attr key=Field
	 * @attr key_type=basic
	 * @attr key_length=medium
	 * @attr key_minlength=2
	 * @attr val=Boolean
	 */
	protected $travellers;
	/**
	 * @parsed Boolean
	 */
	protected $areNamesFull;
	/**
	 * @parsed Boolean
	 */
	protected $cancelled;
    /**
     * @parsed Field
     * @attr type=soft
     * @attr length=long
     */
    protected $cancellation;
    /**
     * @parsed Field
     * @attr type=confno
     * @attr length=short
     */
    protected $cancellationNumber;

	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=medium
	 */
	protected $providerKeyword;
	/**
	 * @parsed Field
	 * @attr type=provider
	 */
	protected $providerCode;
	/**
	 * @parsed KeyValue
	 * @attr unique=true
	 * @attr key=Field
	 * @attr key_type=phone
	 * @attr val=Field
	 * @attr val_type=basic
	 * @attr val_length=medium
	 */
	protected $providerPhones;
	/**
	 * @parsed KeyValue
	 * @attr unique=true
	 * @attr key=Field
	 * @attr key_type=clean
	 * @attr key_length=short
	 * @attr val=Boolean
	 */
	protected $accountNumbers;
	/**
	 * @parsed Boolean
	 */
	protected $areAccountMasked;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=medium
	 */
	protected $earnedAwards;
    /**
     * @parsed Field
     * @attr type=basic
     * @attr length=extra
     */
    protected $notes;

	/** @var RentalPoint $_pickup */
	protected $_pickup;
	/** @var RentalPoint $_dropoff */
	protected $_dropoff;
	/** @var Car $_car */
	protected $_car;
	/** @var RentalExtra $_extra */
	protected $_extra;

	public function __construct($name, LoggerInterface $logger, Options $options = null) {
		parent::__construct($name, $logger, $options);
		$this->_pickup = new RentalPoint($this, new DAShortcut($this, 'p'), 'p');
		$this->_dropoff = new RentalPoint($this, new DAShortcut($this, 'd'), 'd');
		$this->_car = new Car($this);
		$this->_extra = new RentalExtra($this);
	}

	/**
	 * @return RentalPoint
	 */
	public function pickup() {
		return $this->_pickup;
	}

	/**
	 * @return RentalPoint
	 */
	public function dropoff() {
		return $this->_dropoff;
	}

	/**
	 * @return Car
	 */
	public function car() {
		return $this->_car;
	}

	/**
	 * @return RentalExtra
	 */
	public function extra() {
		return $this->_extra;
	}

	/**
	 * @return mixed
	 */
	public function getPickUpLocation() {
		return $this->pickUpLocation;
	}

	/**
	 * @param mixed $pickUpLocation
	 * @return Rental
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setPickUpLocation($pickUpLocation) {
		$this->setProperty($pickUpLocation, 'pickUpLocation', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getPickUpDateTime() {
		return $this->pickUpDateTime;
	}

	/**
	 * @param mixed $pickUpDateTime
	 * @return Rental
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setPickUpDateTime($pickUpDateTime) {
		$this->setProperty($pickUpDateTime, 'pickUpDateTime', false, false);
		return $this;
	}

	/**
	 * @param $date
	 * @param $relative
	 * @param string $format
	 * @param bool $after
	 * @return Rental
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function parsePickUpDateTime($date, $relative = null, $format = '%D% %Y%', $after = true) {
		$this->parseUnixTimeProperty($date, 'pickUpDateTime', $relative, $after, $format);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getDropOffLocation() {
		return $this->dropOffLocation;
	}

	/**
	 * @param mixed $dropOffLocation
	 * @return Rental
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setDropOffLocation($dropOffLocation) {
		$this->setProperty($dropOffLocation, 'dropOffLocation', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getDropOffDateTime() {
		return $this->dropOffDateTime;
	}

	/**
	 * @param mixed $dropOffDateTime
	 * @return Rental
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setDropOffDateTime($dropOffDateTime) {
		$this->setProperty($dropOffDateTime, 'dropOffDateTime', false, false);
		return $this;
	}

	/**
	 * @param $date
	 * @param $relative
	 * @param string $format
	 * @param bool $after
	 * @return Rental
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function parseDropOffDateTime($date, $relative = null, $format = '%D% %Y%', $after = true) {
		$this->parseUnixTimeProperty($date, 'dropOffDateTime', $relative, $after, $format);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getNoPickupLocation() {
		return $this->noPickUpLocation;
	}

	/**
	 * @param mixed $noPickupLocation
	 * @return Rental
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setNoPickupLocation($noPickupLocation) {
		$this->setProperty($noPickupLocation, 'noPickUpLocation', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getNoDropOffLocation() {
		return $this->noDropOffLocation;
	}

	/**
	 * @param mixed $noDropOffLocation
	 * @return Rental
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setNoDropOffLocation($noDropOffLocation) {
		$this->setProperty($noDropOffLocation, 'noDropOffLocation', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getNoPickUpDate() {
		return $this->noPickUpDate;
	}

	/**
	 * @param mixed $noPickUpDate
	 * @return Rental
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setNoPickUpDate($noPickUpDate) {
		$this->setProperty($noPickUpDate, 'noPickUpDate', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getNoDropOffDate() {
		return $this->noDropOffDate;
	}

	/**
	 * @param mixed $noDropOffDate
	 * @return Rental
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setNoDropOffDate($noDropOffDate) {
		$this->setProperty($noDropOffDate, 'noDropOffDate', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getCarType() {
		return $this->carType;
	}

	/**
	 * @param mixed $carType
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Rental
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
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
	 * @param mixed $carModel
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Rental
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setCarModel($carModel, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($carModel, 'carModel', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getCarImageUrl() {
		return $this->carImageUrl;
	}

	/**
	 * @param mixed $carImageUrl
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Rental
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setCarImageUrl($carImageUrl, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($carImageUrl, 'carImageUrl', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getDiscounts() {
		return $this->discounts;
	}

	/**
	 * @param $code
	 * @param $name
	 * @return Rental
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function addDiscount($code, $name) {
		$this->addKeyValue($code, $name, 'discounts', false, false, []);
		return $this;
	}

	/**
	 * @param $code
	 * @return Rental
	 */
	public function removeDiscount($code) {
		$this->removeItem($code, 'discounts');
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getEquipment() {
		return $this->equipment;
	}

	/**
	 * @param $name
	 * @param $charge
	 * @return Rental
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function addEquipment($name, $charge) {
		$this->addKeyValue($name, $charge, 'equipment', false, false, []);
		return $this;
	}

	/**
	 * @param $name
	 * @return Rental
	 */
	public function removeEquipment($name) {
		$this->removeItem($name, 'equipment');
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getCompany() {
		return $this->company;
	}

	/**
	 * @param mixed $company
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Rental
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setCompany($company, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($company, 'company', $allowEmpty, $allowNull);
		return $this;
	}

    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param bool $host
     * @return Rental
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setHost($host)
    {
        $this->setProperty($host, 'host', false, false);
        return $this;
    }

	/**
	 * @return mixed
	 */
	public function getPickUpPhone() {
		return $this->pickUpPhone;
	}

	/**
	 * @param mixed $pickUpPhone
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Rental
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setPickUpPhone($pickUpPhone, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($pickUpPhone, 'pickUpPhone', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getPickUpFax() {
		return $this->pickUpFax;
	}

	/**
	 * @param mixed $pickUpFax
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Rental
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setPickUpFax($pickUpFax, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($pickUpFax, 'pickUpFax', $allowEmpty, $allowNull);
		return $this;
	}

	/**
     * @deprecated use addPickUpOpeningHours()
	 * @param mixed $pickUpHours
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Rental
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setPickUpHours($pickUpHours, $allowEmpty = false, $allowNull = false) {
		return $this->addPickUpOpeningHours($pickUpHours, $allowEmpty, $allowNull);
	}

    /**
     * @return mixed
     */
    public function getPickUpOpeningHours() {
        return $this->pickUpOpeningHours;
    }

    /**
     * @param mixed $pickUpOpeningHours
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return Rental
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setPickUpOpeningHours($pickUpOpeningHours, $allowEmpty = false, $allowNull = false) {
        $this->setProperty($pickUpOpeningHours, 'pickUpOpeningHours', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @param $hours
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return Rental
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function addPickUpOpeningHours($hours, $allowEmpty = false, $allowNull = false) {
        $this->addItem($hours, 'pickUpOpeningHours', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @param $hours
     * @return Rental
     */
    public function removePickUpOpeningHours($hours) {
        $this->removeItem($hours, 'pickUpOpeningHours');
        return $this;
    }

    /**
	 * @return mixed
	 */
	public function getDropOffPhone() {
		return $this->dropOffPhone;
	}

	/**
	 * @param mixed $dropOffPhone
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Rental
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setDropOffPhone($dropOffPhone, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($dropOffPhone, 'dropOffPhone', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getDropOffFax() {
		return $this->dropOffFax;
	}

	/**
	 * @param mixed $dropOffFax
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Rental
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setDropOffFax($dropOffFax, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($dropOffFax, 'dropOffFax', $allowEmpty, $allowNull);
		return $this;
	}

	/**
     * @deprecated use addDropOffOpeningHours()
	 * @param mixed $dropOffHours
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Rental
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setDropOffHours($dropOffHours, $allowEmpty = false, $allowNull = false) {
		return $this->addDropOffOpeningHours($dropOffHours, $allowEmpty, $allowNull);
	}

	/**
	 * @return mixed
	 */
	public function getDropOffOpeningHours() {
		return $this->dropOffOpeningHours;
	}

	/**
	 * @param mixed $dropOffOpeningHours
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Rental
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setDropOffOpeningHours($dropOffOpeningHours, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($dropOffOpeningHours, 'dropOffOpeningHours', $allowEmpty, $allowNull);
		return $this;
	}

    /**
     * @param $hours
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return Rental
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function addDropOffOpeningHours($hours, $allowEmpty = false, $allowNull = false) {
        $this->addItem($hours, 'dropOffOpeningHours', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @param $hours
     * @return Rental
     */
    public function removeDropOffOpeningHours($hours) {
        $this->removeItem($hours, 'dropOffOpeningHours');
        return $this;
    }

	/**
	 * @param $option
	 * @return DetailedAddress
	 */
	public function obtainDetailedAddress($option) {
		if ($option === 'p') {
			if (!isset($this->pickupDetailedAddress))
				$this->pickupDetailedAddress = new DetailedAddress($this->getId().'-pickup-address', $this->logger, $this->_options);
			return $this->pickupDetailedAddress;
		}
		else {
			if (!isset($this->dropoffDetailedAddress))
				$this->dropoffDetailedAddress = new DetailedAddress($this->getId().'-dropoff-address', $this->logger, $this->_options);
			return $this->dropoffDetailedAddress;
		}
	}

    /**
     * @return DetailedAddress
     */
	public function obtainPickUpDetailedAddress() {
	    return $this->obtainDetailedAddress('p');
    }

    /**
     * @return DetailedAddress
     */
    public function obtainDropOffDetailedAddress() {
        return $this->obtainDetailedAddress('d');
    }

	/**
	 * @return DetailedAddress
	 */
	public function getPickUpDetailedAddress() {
		return $this->pickupDetailedAddress;
	}

	/**
	 * @return DetailedAddress
	 */
	public function getDropOffDetailedAddress() {
		return $this->dropoffDetailedAddress;
	}

	/**
	 * @param bool $pickupToDropoff - true to copy P to D, false vice versa
	 * @return Rental
	 */
	public function setSameLocation(bool $pickupToDropoff) {
		if ($pickupToDropoff) {
			$this->logDebug('copying pickup location to dropoff');
			$this->dropOffLocation = $this->pickUpLocation;
			$this->dropoffDetailedAddress = $this->pickupDetailedAddress;
			$this->dropOffPhone = $this->pickUpPhone;
			$this->dropOffFax = $this->pickUpFax;
			$this->dropOffOpeningHours = $this->pickUpOpeningHours;
		}
		else {
			$this->logDebug('copying dropoff location to pickup');
			$this->pickUpLocation = $this->dropOffLocation;
			$this->pickupDetailedAddress = $this->dropoffDetailedAddress;
			$this->pickUpPhone = $this->dropOffPhone;
			$this->pickUpFax = $this->dropOffFax;
			$this->pickUpOpeningHours = $this->dropOffOpeningHours;
		}
		return $this;
	}

	/**
	 * @return Base[]
	 */
	protected function getChildren() {
		$r = parent::getChildren();
		if (isset($this->pickupDetailedAddress))
			$r[] = $this->pickupDetailedAddress;
		if (isset($this->dropoffDetailedAddress))
			$r[] = $this->dropoffDetailedAddress;
		return $r;
	}

    public function validate(bool $hasUpperConfNo)
    {
        $this->validateArrays();
        if ($this->travelAgency)
            $this->valid = $this->travelAgency->validate() && $this->valid;
        if ($this->price)
            $this->valid = $this->price->getValid() && $this->valid;
        $confNo = $hasUpperConfNo || $this->hasConfNo();
        if (!$this->cancelled) {
            if (empty($this->confirmationNumbers) && !$this->noConfirmationNumber)
                $this->invalid('missing confirmation number');
            if (empty($this->pickUpDateTime) && $this->noPickUpDate !== true)
                $this->invalid('missing pickup date');
            if ($this->noPickUpDate && !empty($this->pickUpDateTime)) {
                $this->invalid('invalid pickUpDate/noPickUpDate');
            }
            if (empty($this->dropOffDateTime) && $this->noDropOffDate !== true)
                $this->invalid('missing dropoff date');
            if ($this->noDropOffDate && !empty($this->dropOffDateTime)) {
                $this->invalid('invalid dropOffDate/noDropOffDate');
            }
            if (!empty($this->pickUpDateTime) && !empty($this->dropOffDateTime) && $this->pickUpDateTime >= $this->dropOffDateTime)
                $this->invalid('invalid dates');
            if (empty($this->pickUpLocation) && $this->noPickUpLocation !== true)
                $this->invalid('missing pickup location');
            if (empty($this->dropOffLocation) && $this->noDropOffLocation !== true)
                $this->invalid('missing dropoff location');
            $pickup = !empty($this->pickUpLocation) || !empty($this->pickupDetailedAddress) && $this->pickupDetailedAddress->isFull();
            $dropoff = !empty($this->dropOffLocation) || !empty($this->dropoffDetailedAddress) && $this->dropoffDetailedAddress->isFull();
            if (!$pickup && !$dropoff)
                $this->invalid('missing location info');
            /*
            if (!empty($this->pickUpLocation) && (stripos($this->pickUpLocation, 'pickup') !== false))
                $this->invalid('possibly invalid pickup location');
            if (!empty($this->dropOffLocation) && (stripos($this->dropOffLocation, 'dropoff') !== false))
                $this->invalid('possibly invalid dropoff location');
            */
        }
        else {
            if (!$confNo)
                $this->invalid('missing confirmation number');
        }
        if (null !== $this->pickUpDateTime && null !== $this->dropOffDateTime && $this->pickUpDateTime > $this->dropOffDateTime)
            $this->invalid('invalid dates');
        $this->checkTravellers();
        return $this->valid;
    }


}