<?php

namespace AwardWallet\Common\API\Email\V2;


use JMS\Serializer\Annotation as Serializer;

class ParseEmailResponseMin {

	/**
	 * @var int
	 * @Serializer\Type("integer")
	 */
	public $apiVersion = 2;
	/**
	 * @var string
	 * @Serializer\Type("string")
	 */
	public $status;
	/**
	 * @var string
	 * @Serializer\Type("string")
	 */
	public $errorMessage;
	/**
	 * @var array
	 * @Serializer\Type("array<string>")
	 */
	public $requestIds;

}