<?php

namespace AwardWallet\Schema\Itineraries;

use JMS\Serializer\Annotation\Type;

class TravelAgency {

    /**
     * @var ProviderInfo
     * @Type("AwardWallet\Schema\Itineraries\ProviderInfo")
     */
	public $providerInfo;

    /**
     * @var ConfNo[]
     * @Type("array<AwardWallet\Schema\Itineraries\ConfNo>")
     */
	public $confirmationNumbers;

    /**
     * @var PhoneNumber[]
     * @Type("array<AwardWallet\Schema\Itineraries\PhoneNumber>")
     */
	public $phoneNumbers;

}