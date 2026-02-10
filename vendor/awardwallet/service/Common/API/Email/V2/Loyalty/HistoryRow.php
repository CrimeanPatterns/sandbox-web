<?php

namespace AwardWallet\Common\API\Email\V2\Loyalty;


use JMS\Serializer\Annotation\Type;

class HistoryRow {

	/**
	 * @var HistoryField[] $fields
	 * @Type("array<AwardWallet\Common\API\Email\V2\Loyalty\HistoryField>")
	 */
	public $fields;

	public function __construct($fields = null) {
		$this->fields = $fields;
	}

}