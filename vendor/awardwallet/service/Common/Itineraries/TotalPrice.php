<?php

namespace AwardWallet\Common\Itineraries;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\Accessor;

/**
 * Class TotalPrice
 * @property $total
 * @property $cost
 * @property $spentAwards
 * @property $currencyCode
 * @property $tax
 * @property $discount
 * @property $fees
 * @property $rate
 * @property $rateType
 */
class TotalPrice extends LoggerEntity
{

    /**
     * @var double
     * @Type("double")
     */
    protected $total;

    /**
     * @var double
     * @Type("double")
     */
    protected $cost;

    /**
     * @var string
     * @Type("string")
     */
    protected $spentAwards;

    /**
     * @var string
     * @Type("string")
     */
    protected $currencyCode;

    /**
     * @var double
     * @Type("double")
     */
    protected $tax;
    /**
     * @var double
     * @Type("double")
     */
    protected $discount;
    /**
     * @var string
     * @Type("string")
     */
    protected $rate;
    /**
     * @var string
     * @Type("string")
     */
    protected $rateType;
    /**
     * @var Fee[]
     * @Type("array<AwardWallet\Common\Itineraries\Fee>")
     * @Accessor(getter="getFeesForJMS", setter="setFees")
     */
    protected $fees;

}