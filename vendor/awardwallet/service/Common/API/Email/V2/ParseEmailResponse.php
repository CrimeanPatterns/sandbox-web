<?php

namespace AwardWallet\Common\API\Email\V2;


use AwardWallet\Schema\Itineraries\FlightStatsMethodCalled;
use JMS\Serializer\Annotation\SkipWhenEmpty;
use JMS\Serializer\Annotation\Type;

class ParseEmailResponse {

	/**
	 * @var int
	 * @Type("integer")
	 */
	public $apiVersion = 2;
	/**
	 * @var string
	 * @Type("string")
	 */
	public $requestId;
	/**
	 * @var string
	 * @Type("string")
	 */
	public $status;
	/**
	 * @var string
	 * @Type("string")
	 */
	public $statusMessage;
    /**
     * @var string
     * @Type("string")
     */
    public $rejectMethod;
    /**
     * @var array
     * @Type("array<string>")
     */
    public $missingFields;
	/**
	 * @var string
	 * @Type("string")
	 */
	public $providerCode;
	/**
	 * @var \AwardWallet\Schema\Itineraries\Itinerary[]
	 * @Type("array<AwardWallet\Schema\Itineraries\Itinerary>")
	 */
	public $itineraries;
	/**
	 * @var \AwardWallet\Common\API\Email\V2\Loyalty\LoyaltyAccount
	 * @Type("AwardWallet\Common\API\Email\V2\Loyalty\LoyaltyAccount")
	 */
	public $loyaltyAccount;
    /**
     * @var \AwardWallet\Common\API\Email\V2\BoardingPass\BoardingPass[]
     * @Type("array<AwardWallet\Common\API\Email\V2\BoardingPass\BoardingPass>")
     * @SkipWhenEmpty
     */
	public $boardingPasses = [];
	/**
	 * @var \AwardWallet\Schema\Itineraries\PricingInfo
	 * @Type("AwardWallet\Schema\Itineraries\PricingInfo")
	 */
	public $pricingInfo;
	/**
	 * @var boolean
	 * @Type("boolean")
	 */
	public $fromProvider;
	/**
	 * @var \AwardWallet\Common\API\Email\V2\Meta\EmailInfo
	 * @Type("AwardWallet\Common\API\Email\V2\Meta\EmailInfo")
	 */
	public $metadata;
	/**
	 * @var \AwardWallet\Common\API\Email\V2\Meta\EmailInfo
	 * @Type("AwardWallet\Common\API\Email\V2\Meta\EmailInfo")
	 */
	public $nestedEmailMetadata;
	/**
	 * @var string
	 * @Type("string")
	 */
	public $parsingMethod;
    /**
     * @var FlightStatsMethodCalled[]
     * @Type("array<AwardWallet\Schema\Itineraries\FlightStatsMethodCalled>")
     */
	public $flightStatsMethodsUsed = [];
	/**
	 * @var string
	 * @Type("string")
	 */
	public $userData;
	/**
	 * @var string
	 * @Type("string")
	 */
	public $email;
    /**
     * @var array
     * @Type("array<string>")
     * @SkipWhenEmpty
     */
	public $oneTimeCodes = [];

    /**
     * @var \AwardWallet\Common\API\Email\V2\Coupon\Coupon[]
     * @Type("array<AwardWallet\Common\API\Email\V2\Coupon\Coupon>")
     * @SkipWhenEmpty
     */
    public $coupons = [];

    /**
     * @var \AwardWallet\Common\API\Email\V2\AwardRedemption\AwardRedemption[]
     * @Type("array<AwardWallet\Common\API\Email\V2\AwardRedemption\AwardRedemption>")
     * @SkipWhenEmpty
     */
    public $awardRedemption = [];

    /**
     * @var \AwardWallet\Common\API\Email\V2\CardPromo\CardPromo
     * @Type("AwardWallet\Common\API\Email\V2\CardPromo\CardPromo")
     * @SkipWhenEmpty
     */
    public $cardPromo;

}