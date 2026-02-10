<?php


namespace AwardWallet\Schema\Parser\Common;


use AwardWallet\Schema\Parser\Component\Base;
use AwardWallet\Schema\Parser\Component\InvalidDataException;

class Coupon extends Base
{

//    CATEGORIES
    const CAT_CREDIT_CARD = 6;
    const CAT_AIRLINE = 1;
    const CAT_HOTEL = 2;
    const CAT_CAR_RENTAL = 3;
    const CAT_TRAIN = 4;
    const CAT_CRUISE = 10;
    const CAT_SALES = 7;
    const CAT_RESTAURANT = 8;
    const CAT_ONLINE_QUESTION = 9;
    const CAT_OTHER = 5;

    protected $_categories = [
        self::CAT_CREDIT_CARD,
        self::CAT_AIRLINE,
        self::CAT_HOTEL,
        self::CAT_CAR_RENTAL,
        self::CAT_TRAIN,
        self::CAT_CRUISE,
        self::CAT_SALES,
        self::CAT_RESTAURANT,
        self::CAT_ONLINE_QUESTION,
        self::CAT_OTHER
    ];
//    TYPES
    const TYPE_CERTIFICATE = 3;
    const TYPE_COMPANION_TICKET = 5;
    const TYPE_COUPON = 6;
    const TYPE_GIFT_CARD = 1;
    const TYPE_STORE_CREDIT = 7;
    const TYPE_TICKET = 4;
    const TYPE_TRAVEL_VOUCHER = 2;

    protected $_types = [
        self::TYPE_CERTIFICATE,
        self::TYPE_COMPANION_TICKET,
        self::TYPE_COUPON,
        self::TYPE_GIFT_CARD,
        self::TYPE_STORE_CREDIT,
        self::TYPE_TICKET,
        self::TYPE_TRAVEL_VOUCHER
    ];

    /**
     * @parsed Field
     * @attr type=number
     * @attr enum=[1,2,3,4,5,6,7,8,9,10]
     */
    protected $category;

    /**
     * @parsed Field
     * @attr type=number
     * @attr enum=[1,2,3,4,5,6,7]
     */
    protected $type;

    /**
     * @parsed Field
     * @attr type=soft
     */
    protected $owner;

    /**
     * @parsed Field
     * @attr length=short
     * @attr regexp=/^[A-Za-z\d]+$/
     */
    protected $number;

    /**
     * @parsed Field
     * @attr length=short
     * @attr regexp=/^[A-Za-z\d]+$/
     */
    protected $pin;

    /**
     * @parsed Field
     * @attr type=soft
     */
    protected $value;

    /**
     * @parsed DateTime
     */
    protected $expirationDate;

    /**
     * @parsed Boolean
     */
    protected $canExpire;
    /**
     * @parsed Field
     */
    protected $accountNumber;
    /**
     * @parsed Field
     * @attr enum=['left','right','center']
     */
    protected $accountMask;

    /**
     * @return mixed
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param $owner
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return $this
     * @throws InvalidDataException
     */
    public function setOwner($owner, $allowEmpty = false, $allowNull = false)
    {
        $this->setProperty($owner, 'owner', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param $category
     * @return $this
     * @throws InvalidDataException
     */
    public function setCategory($category)
    {
        $this->setProperty($category, 'category', false, false);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $type
     * @return $this
     * @throws InvalidDataException
     */
    public function setType($type)
    {
        $this->setProperty($type, 'type', false, false);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param $number
     * @return $this
     * @throws InvalidDataException
     */
    public function setNumber($number)
    {
        $this->setProperty($number, 'number', false, false);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPin()
    {
        return $this->pin;
    }

    /**
     * @param $pin
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return $this
     * @throws InvalidDataException
     */
    public function setPin($pin, $allowEmpty = false, $allowNull = false)
    {
        $this->setProperty($pin, 'pin', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param $value
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return $this
     * @throws InvalidDataException
     */
    public function setValue($value, $allowEmpty = false, $allowNull = false)
    {
        $this->setProperty($value, 'value', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    /**
     * @param $expirationDate
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return $this
     * @throws InvalidDataException
     */
    public function setExpirationDate($expirationDate, $allowEmpty = false, $allowNull = false)
    {
        $this->setProperty($expirationDate, 'expirationDate', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCanExpire()
    {
        return $this->canExpire;
    }

    /**
     * @param $canExpire
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return $this
     * @throws InvalidDataException
     */
    public function setCanExpire($canExpire, $allowEmpty = false, $allowNull = false)
    {
        $this->setProperty($canExpire, 'canExpire', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAccountNumber()
    {
        return $this->accountNumber;
    }

    /**
     * @param $accountNumber
     * @return $this
     * @throws InvalidDataException
     */
    public function setAccountNumber($accountNumber)
    {
        $this->setProperty($accountNumber, 'accountNumber', false, false);
        return $this;
    }

    /**
     * @param string $mask
     * @return $this
     * @throws InvalidDataException
     */
    public function setAccountMask($mask = 'left')
    {
        $this->setProperty($mask, 'accountMask', false, false);
        return $this;
    }

    public function getAccountMask()
    {
        return $this->accountMask;
    }

    public function validate()
    {
        if( empty($this->category) || empty($this->type) || (empty($this->number) && empty($this->pin))) {
            $this->invalid('missing require data for coupon');
        }
        if (!empty($this->accountMask) && empty($this->accountNumber)) {
            $this->invalid('mask is set without an account number');
        }
        if ($this->accountMask === 'center' && preg_match('/^[^*]+\*\*[^*]+$/', $this->accountNumber) == 0)
            $this->invalid('invalid center masked number format');
        return $this->valid;
    }

    protected function getChildren()
    {
        return [];
    }

}