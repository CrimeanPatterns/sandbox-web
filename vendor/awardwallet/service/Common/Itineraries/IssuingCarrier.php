<?php

namespace AwardWallet\Common\Itineraries;


/**
 * Class IssuingCarrier
 *
 * @property Airline $airline
 * @property PhonesCollection $phones
 * @property $confirmationNumber
 * @property array $ticketNumbers
 */
class IssuingCarrier extends AbstractCarrier {

    /** @var string */
	protected $confirmationNumber;
    /** @var string[] */
	protected $ticketNumbers;

}