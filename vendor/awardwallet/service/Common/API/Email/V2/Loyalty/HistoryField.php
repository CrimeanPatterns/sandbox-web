<?php

namespace AwardWallet\Common\API\Email\V2\Loyalty;


use JMS\Serializer\Annotation\Type;

class HistoryField {

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
	public $value;

	public function __construct($name = null, $code = null, $value = null) {
		$this->name = $name;
		$this->code = $code;
		$this->value = $value;
	}

}