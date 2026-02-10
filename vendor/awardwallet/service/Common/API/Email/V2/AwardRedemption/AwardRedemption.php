<?php


namespace AwardWallet\Common\API\Email\V2\AwardRedemption;

use JMS\Serializer\Annotation\Type;

class AwardRedemption
{
    /**
     * @var string
     * @Type("string")
     */
    public $dateIssued;

    /**
     * @var integer
     * @Type("integer")
     */
    public $milesRedeemed;

    /**
     * @var string
     * @Type("string")
     */
    public $recipient;

    /**
     * @var string
     * @Type("string")
     */
    public $description;

    /**
     * @var string
     * @Type("string")
     */
    public $accountNumber;

}