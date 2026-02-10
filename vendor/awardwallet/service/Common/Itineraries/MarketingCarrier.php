<?php

namespace AwardWallet\Common\Itineraries;


/**
 * Class OperatingCarrier
 *
 * @property Airline $airline
 * @property PhonesCollection $phones
 * @property $flightNumber
 * @property $confirmationNumber
 * @property boolean $isCodeshare
 */
class MarketingCarrier extends AbstractCarrier {

    /** @var string */
	protected $flightNumber;
    /** @var string */
	protected $confirmationNumber;
	/** @var boolean */
	protected $isCodeshare;

}