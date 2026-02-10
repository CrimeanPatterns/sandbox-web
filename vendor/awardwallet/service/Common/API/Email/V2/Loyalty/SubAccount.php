<?php

namespace AwardWallet\Common\API\Email\V2\Loyalty;

use JMS\Serializer\Annotation\Type;

class SubAccount
{

    /**
     * @var string $code
     * @Type("string")
     */
    public $code;

    /**
     * @var string $displayName
     * @Type("string")
     */
    public $displayName;

    /**
     * @var double $balance
     * @Type("double")
     */
    public $balance;

    /**
     * @var string $expirationDate
     * @Type("string")
     */
    public $expirationDate;

    /**
     * @var Property[] $properties
     * @Type("array<AwardWallet\Common\API\Email\V2\Loyalty\Property>")
     */
    public $properties;

}