<?php


namespace AwardWallet\Common\API\Email\V2\CardPromo;

use JMS\Serializer\Annotation\Type;

class CardPromoOfferDetails
{
    /**
     * @var string
     * @Type("string")
     */
    public $multiplier;

    /**
     * @var string
     * @Type("string")
     */
    public $offerDeadline;

    /**
     * @var string
     * @Type("string")
     */
    public $applicationDeadline;

    /**
     * @var string
     * @Type("string")
     */
    public $applicationURL;

    /**
     * @var integer
     * @Type("integer")
     */
    public $limitAmount;

    /**
     * @var string
     * @Type("string")
     */
    public $limitCurrency;

    /**
     * @var array
     * @Type("array<string>")
     */
    public $bonusCategories;
}