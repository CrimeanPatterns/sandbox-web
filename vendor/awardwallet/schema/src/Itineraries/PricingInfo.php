<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class PricingInfo {

    /**
     * @var double
     * @Type("double")
     */
	public $total;

    /**
     * @var double
     * @Type("double")
     */
	public $cost;

    /**
     * @var double
     * @Type("double")
     */
	public $discount;

    /**
     * @var string
     * @Type("string")
     */
	public $spentAwards;

    /**
     * @var string
     * @Type("string")
     */
	public $currencyCode;

    /**
     * @var Fee[]
     * @Type("array<AwardWallet\Schema\Itineraries\Fee>")
     */
	public $fees;

}