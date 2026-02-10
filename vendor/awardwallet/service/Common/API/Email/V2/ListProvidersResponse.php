<?php

namespace AwardWallet\Common\API\Email\V2;

use JMS\Serializer\Annotation\Type;
use AwardWallet\Common\API\Email\V2\Provider\ListProvidersItem;

class ListProvidersResponse {

	/**
	 * @var integer
	 * @Type("integer")
	 */
	public $apiVersion = 2;

	/**
	 * @var ListProvidersItem[] $providers
	 * @Type("array<AwardWallet\Common\API\Email\V2\Provider\ListProvidersItem>")
	 */
	public $providers;

}