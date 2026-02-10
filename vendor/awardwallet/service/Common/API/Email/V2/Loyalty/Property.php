<?php

namespace AwardWallet\Common\API\Email\V2\Loyalty;

use JMS\Serializer\Annotation\Type;

class Property {

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
	 * @var string
	 * @Type("string")
	 */
	public $kind;
	/**
	 * @var string
	 * @Type("string")
	 */
	public $value;

	public function __construct($code = null, $name = null, $kind = null, $value = null) {
		$this->code = $code;
		$this->name = $name;
		$this->kind = $kind;
		$this->value = $value;
	}

}