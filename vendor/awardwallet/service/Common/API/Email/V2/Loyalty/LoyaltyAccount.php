<?php

namespace AwardWallet\Common\API\Email\V2\Loyalty;

use JMS\Serializer\Annotation\Type;

class LoyaltyAccount {

	/**
	 * @var string
	 * @Type("string")
	 */
	public $providerCode;
	/**
	 * @var double
	 * @Type("double")
	 */
	public $balance;
	/**
	 * @var string
	 * @Type("string")
	 */
	public $balanceDate;
    /**
     * @var string
     * @Type("string")
     */
    public $expirationDate;
    /**
     * @var string
     * @Type("string")
     */
	public $login;
    /**
     * @var string
     * @Type("string")
     */
    public $login2;
    /**
     * @var string
     * @Type("string")
     */
    public $loginMask;
    /**
     * @var string
     * @Type("string")
     */
	public $number;
    /**
     * @var string
     * @Type("string")
     */
    public $numberMask;
    /**
     * @var boolean
     * @Type("boolean")
     */
	public $isMember;

	/**
	 * @var Property[] $properties
	 * @Type("array<AwardWallet\Common\API\Email\V2\Loyalty\Property>")
	 */
	public $properties;

	/**
	 * @var HistoryRow[] $history
	 * @Type("array<AwardWallet\Common\API\Email\V2\Loyalty\HistoryRow>")
	 */
	public $history;

    /**
     * @var SubAccount[] $subAccounts
     * @Type("array<AwardWallet\Common\API\Email\V2\Loyalty\SubAccount>")
     */
    public $subAccounts;

}