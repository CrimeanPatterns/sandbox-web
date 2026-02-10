<?php

namespace AwardWallet\Common\API\Email\V2\Meta;

use JMS\Serializer\Annotation\Type;

class EmailInfo {

	/**
	 * @var EmailAddress $from
	 * @Type("AwardWallet\Common\API\Email\V2\Meta\EmailAddress")
	 */
	public $from;

	/**
	 * @var EmailAddress[] $to
	 * @Type("array<AwardWallet\Common\API\Email\V2\Meta\EmailAddress>")
	 */
	public $to = [];

	/**
	 * @var EmailAddress[] $cc
	 * @Type("array<AwardWallet\Common\API\Email\V2\Meta\EmailAddress>")
	 */
	public $cc = [];

	/**
	 * @var string $subject
	 * @Type("string")
	 */
	public $subject;

	/**
	 * @var string $receivedDateTime
	 * @Type("string")
	 */
	public $receivedDateTime;

	/**
	 * @var string $userEmail
	 * @Type("string")
	 */
	public $userEmail;

	/**
	 * @var boolean $nested
	 * @Type("boolean")
	 */
	public $nested;

    /**
     * @var integer
	 * @Type("integer")
     */
	public $mailboxId;

    /**
     * @var string $mailboxAddress
     * @Type("string")
     */
	public $mailboxAddress;

}