<?php

namespace AwardWallet\Common\Itineraries;

use JMS\Serializer\Annotation\Type;

/**
 * Class ProviderDetails
 * @property string $confirmationNumber
 * @property array $confirmationNumbers
 * @property $tripNumber
 * @property $accountNumbers
 * @property $reservationDate
 * @property $status
 * @property $name
 * @property $code
 * @property $earnedAwards
 */
class ProviderDetails extends LoggerEntity
{

    /**
     * @var string
     * @Type("string")
     */
    protected $confirmationNumber;
    /**
     * @var array
     * @Type("array")
     */
    protected $confirmationNumbers;
    /**
     * @var string
     * @Type("string")
     */
    protected $tripNumber;

    /**
     * @var string
     * @Type("string")
     */
    protected $accountNumbers;

    /**
     * @var string
     * @Type("string")
     */
    protected $reservationDate;

    /**
     * @var string
     * @Type("string")
     */
    protected $status;

    /**
     * @var string
     * @Type("string")
     */
    protected $name;

    /**
     * @var string
     * @Type("string")
     */
    protected $code;

    /**
     * @var string
     * @Type("string")
     */
    protected $earnedAwards;

}