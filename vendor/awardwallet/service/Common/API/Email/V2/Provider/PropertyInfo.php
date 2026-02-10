<?php

namespace AwardWallet\Common\API\Email\V2\Provider;

use JMS\Serializer\Annotation\Type;

class PropertyInfo {

	/**
	 * @var string $name
	 * @Type("string")
	 */
	public $code;
	/**
	 * @var string $name
	 * @Type("string")
	 */
	public $name;
	/**
	 * @var string $name
	 * @Type("string")
	 */
	public $kind;

	public function __construct($code = null, $name = null, $kind = null) {
		$this->code = $code;
		$this->name = $name;
		$this->kind = $kind;
	}

}