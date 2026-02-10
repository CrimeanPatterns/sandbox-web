<?php


namespace AwardWallet\Common\API\Converter\V2;

use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Schema\Parser\Common as Parsed;

class Loader
{

    /**
     * @var Itinerary[] $converters
     */
    protected $converters;

    public function __construct()
    {
        $this->converters = [
            Parsed\Flight::class => new Flight(),
            Parsed\Bus::class => new Bus(),
            Parsed\Train::class => new Train(),
            Parsed\Transfer::class => new Transfer(),
            Parsed\Hotel::class => new Hotel(),
            Parsed\Rental::class => new Rental(),
            Parsed\Cruise::class => new Cruise(),
            Parsed\Event::class => new Event(),
            Parsed\Parking::class => new Parking(),
            Parsed\Ferry::class => new Ferry(),
        ];
    }

    public function convert(Parsed\Itinerary $parsed, Extra $extra) {
        return $this->converters[get_class($parsed)]->convert($parsed, $extra);
    }
}