<?php

namespace AwardWallet\Common\API\Email\V2\Meta;

use JMS\Serializer\Annotation\Type;

class EmailAddress {

	/**
	 * @var string $name
	 * @Type("string")
	 */
	public $name;

	/**
	 * @var string $email
	 * @Type("string")
	 */
	public $email;

	public function __construct($name = null, $email = null) {
		$this->name = $name;
		$this->email = $email;
	}

}