<?php

namespace AwardWallet\Common\Itineraries;


/**
 * Class OperatingCarrier
 *
 * @property Airline $airline
 * @property $flightNumber
 * @property $confirmationNumber
 * @property PhonesCollection $phones
 */
class OperatingCarrier extends AbstractCarrier {

    /** @var string */
    protected $flightNumber;
	/** @var string */
    protected $confirmationNumber;

}