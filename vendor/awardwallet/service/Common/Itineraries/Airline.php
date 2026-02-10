<?php

namespace AwardWallet\Common\Itineraries;

/**
 * Class Airline
 *
 * @property $name
 * @property $iata
 * @property $icao
 */
class Airline extends LoggerEntity {

    /** @var string */
	protected $name;
    /** @var string */
	protected $iata;
    /** @var string */
	protected $icao;

}