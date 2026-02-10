<?php

namespace AwardWallet\Common\API\Email\V2\Provider;

use JMS\Serializer\Annotation\Type;

class ListProvidersItem {

	/**
	 * @var string $code
	 * @Type("string")
	 */
	public $code;
	/**
	 * @var string $displayName
	 * @Type("string")
	 */
	public $displayName;
    /**
     * @var string $shortName
     * @Type("string")
     */
	public $shortName;
    /**
     * @var string[] $supportedLanguages
     * @Type("Array<string>")
     */
	public $supportedLanguages;
    /**
     * @var int $supportedFormatCount
     * @Type("int")
     */
	public $supportedFormatCount;
	/**
	 * @var PropertyInfo[] $properties
	 * @Type("array<AwardWallet\Common\API\Email\V2\Provider\PropertyInfo>")
	 */
	public $properties;
	/**
	 * @var PropertyInfo[] $historyColumns
	 * @Type("array<AwardWallet\Common\API\Email\V2\Provider\PropertyInfo>")
	 */
	public $historyColumns;

	public function __construct($code = null, $displayName = null, $properties = null, $historyColumns = null) {
		$this->code = $code;
		$this->displayName = $displayName;
		$this->properties = $properties;
		$this->historyColumns = $historyColumns;
	}

}