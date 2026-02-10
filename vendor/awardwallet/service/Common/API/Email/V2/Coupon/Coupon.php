<?php


namespace AwardWallet\Common\API\Email\V2\Coupon;

use JMS\Serializer\Annotation\Type;

class Coupon
{

    /**
     * @var string
     * @Type("string")
     */
    public $programCode;

    /**
     * @var string
     * @Type("string")
     */
    public $owner;

    /**
     * @var integer
     * @Type("integer")
     */
    public $category;

    /**
     * @var integer
     * @Type("integer")
     */
    public $type;

    /**
     * @var string
     * @Type("string")
     */
    public $number;

    /**
     * @var string
     * @Type("string")
     */
    public $pin;

    /**
     * @var string
     * @Type("string")
     */
    public $value;

    /**
     * @var string
     * @Type("string")
     */
    public $expirationDate;

    /**
     * @var boolean
     * @Type("boolean")
     */
    public $canExpire;

    /**
     * @var string
     * @Type("string")
     */
    public $accountNumber;

    /**
     * @var string
     * @Type("string")
     */
    public $accountMask;

}