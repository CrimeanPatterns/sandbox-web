<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class ProviderInfo {

    /**
     * @var string
     * @Type("string")
     */
	public $code;

    /**
     * @var string
     * @Type("string")
     */
	public $name;

	/**
	 * @var ParsedNumber[]
	 * @Type("array<AwardWallet\Schema\Itineraries\ParsedNumber>")
	 */
	public $accountNumbers;

    /**
     * @var string
     * @Type("string")
     */
	public $earnedRewards;

}