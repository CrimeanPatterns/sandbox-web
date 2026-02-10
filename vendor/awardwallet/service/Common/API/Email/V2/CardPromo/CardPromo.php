<?php


namespace AwardWallet\Common\API\Email\V2\CardPromo;

use JMS\Serializer\Annotation\Type;

class CardPromo
{
    /**
     * @var string
     * @Type("string")
     */
    public $cardName;

    /**
     * @var string
     * @Type("string")
     */
    public $cardOwner;

    /**
     * @var integer
     * @Type("integer")
     */
    public $cardMemberSince;

    /**
     * @var integer
     * @Type("integer")
     */
    public $lastDigits;

    /**
     * @var CardPromoOfferDetails
     * @Type("AwardWallet\Common\API\Email\V2\CardPromo\CardPromoOfferDetails")
     */
    public $offerDetails;

}