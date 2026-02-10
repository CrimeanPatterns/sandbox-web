<?php


namespace AwardWallet\Schema\Parser\Common;


use AwardWallet\Schema\Parser\Common\Shortcut\ParkingBooked;
use AwardWallet\Schema\Parser\Common\Shortcut\ParkingPlace;
use AwardWallet\Schema\Parser\Component\Options;
use Psr\Log\LoggerInterface;

class Parking extends Itinerary
{

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
    /**
     * @parsed Field
     * @attr type=basic
     * @attr length=long
     * @attr minlength=2
     */
    protected $address;
    /**
     * @parsed Field
     * @attr type=basic
     * @attr length=long
     * @attr minlength=2
     */
    protected $location;
    /**
     * @parsed Field
     * @attr type=basic
     * @attr length=short
     */
    protected $spot;
    /**
     * @parsed Field
     * @attr type=basic
     * @attr length=short
     */
    protected $plate;
    /**
     * @parsed Field
     * @attr type=phone
     */
    protected $phone;
    /**
     * @parsed Arr
     * @attr item=Field
     * @attr item_type=basic
     * @attr item_length=medium
     * @attr item_minlength=4
     */
    protected $openingHours;
    /**
     * @parsed DateTime
     */
    protected $startDate;
    /**
     * @parsed DateTime
     */
    protected $endDate;
    /**
     * @parsed Boolean
     */
    protected $noStartDate;
    /**
     * @parsed Boolean
     */
    protected $noEndDate;
    /**
     * @parsed Field
     * @attr type=basic
     */
    protected $rateType;
    /**
     * @parsed Field
     * @attr type=basic
     * @attr length=medium
     */
    protected $carDescription;

    /**
     * @var ParkingBooked
     */
    protected $_booked;
    /**
     * @var ParkingPlace
     */
    protected $_place;

    public function __construct($name, LoggerInterface $logger, Options $options = null)
    {
        parent::__construct($name, $logger, $options);
        $this->_booked = new ParkingBooked($this);
        $this->_place = new ParkingPlace($this);
    }

    /**
     * @return ParkingBooked
     */
    public function booked()
    {
        return $this->_booked;
    }

    /**
     * @return ParkingPlace
     */
    public function place()
    {
        return $this->_place;
    }

    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param mixed $address
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return Parking
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setAddress($address, $allowEmpty = false, $allowNull = false)
    {
        $this->setProperty($address, 'address', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param mixed $location
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return Parking
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setLocation($location, $allowEmpty = false, $allowNull = false)
    {
        $this->setProperty($location, 'location', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSpot()
    {
        return $this->spot;
    }

    /**
     * @param mixed $spot
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return Parking
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setSpot($spot, $allowEmpty = false, $allowNull = false)
    {
        $this->setProperty($spot, 'spot', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPlate()
    {
        return $this->plate;
    }

    /**
     * @param mixed $plate
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return Parking
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setPlate($plate, $allowEmpty = false, $allowNull = false)
    {
        $this->setProperty($plate, 'plate', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param mixed $startDate
     * @return Parking
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setStartDate($startDate)
    {
        $this->setProperty($startDate, 'startDate', false, false);
        return $this;
    }

    /**
     * @param $date
     * @param $relative
     * @param bool $after
     * @param string $format
     * @return Parking
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function parseStartDate($date, $relative = null, $format = '%D% %Y%', $after = true)
    {
        $this->parseUnixTimeProperty($date, 'startDate', $relative, $after, $format);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @param mixed $endDate
     * @return Parking
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setEndDate($endDate)
    {
        $this->setProperty($endDate, 'endDate', false, false);
        return $this;
    }

    /**
     * @param $date
     * @param $relative
     * @param bool $after
     * @param string $format
     * @return Parking
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function parseEndDate($date, $relative = null, $format = '%D% %Y%', $after = true)
    {
        $this->parseUnixTimeProperty($date, 'endDate', $relative, $after, $format);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getNoStartDate()
    {
        return $this->noStartDate;
    }

    /**
     * @param mixed $noStartDate
     * @return Parking
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setNoStartDate($noStartDate)
    {
        $this->setProperty($noStartDate, 'noStartDate', false, false);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getNoEndDate()
    {
        return $this->noEndDate;
    }

    /**
     * @param mixed $noEndDate
     * @return Parking
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setNoEndDate($noEndDate)
    {
        $this->setProperty($noEndDate, 'noEndDate', false, false);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param mixed $phone
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return Parking
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setPhone($phone, $allowEmpty = false, $allowNull = false)
    {
        $this->setProperty($phone, 'phone', $allowEmpty, $allowNull);
        return $this;
    }


    /**
     * @return mixed
     */
    public function getOpeningHours() {
        return $this->openingHours;
    }

    /**
     * @param mixed $openingHours
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return Parking
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setOpeningHours($openingHours, $allowEmpty = false, $allowNull = false) {
        $this->setProperty($openingHours, 'openingHours', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @param $hours
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return Parking
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function addOpeningHours($hours, $allowEmpty = false, $allowNull = false) {
        $this->addItem($hours, 'openingHours', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @param $hours
     * @return Parking
     */
    public function removeOpeningHours($hours) {
        $this->removeItem($hours, 'openingHours');
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRateType()
    {
        return $this->rateType;
    }

    /**
     * @param mixed $rateType
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return Parking
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setRateType($rateType, $allowEmpty = false, $allowNull = false)
    {
        $this->setProperty($rateType, 'rateType', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCarDescription()
    {
        return $this->carDescription;
    }

    /**
     * @param mixed $carDescription
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return Parking
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setCarDescription($carDescription, $allowEmpty = false, $allowNull = false)
    {
        $this->setProperty($carDescription, 'carDescription', $allowEmpty, $allowNull);
        return $this;
    }

    public function validate(bool $hasUpperConfNo)
    {
        $this->validateArrays();
        if ($this->travelAgency)
            $this->valid = $this->travelAgency->validate() && $this->valid;
        if ($this->price)
            $this->valid = $this->price->getValid() && $this->valid;
        if (!$this->cancelled) {
            if (empty($this->confirmationNumbers) && !$this->noConfirmationNumber)
                $this->invalid('missing confirmation number');
            if (empty($this->address))
                $this->invalid('missing address info');
            if (empty($this->startDate) && $this->noStartDate !== true)
                $this->invalid('missing startDate');
            if (empty($this->endDate) && $this->noEndDate !== true)
                $this->invalid('missing endDate');
        }
        else {
            if (!$hasUpperConfNo && !$this->hasConfNo())
                $this->invalid('missing confirmation number');
        }
        if (null !== $this->startDate && null !== $this->endDate && $this->startDate > $this->endDate)
            $this->invalid('invalid dates');
        $this->checkTravellers();
        return $this->valid;
    }
}