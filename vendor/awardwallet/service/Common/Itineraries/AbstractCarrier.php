<?php

namespace AwardWallet\Common\Itineraries;

use Psr\Log\LoggerInterface;

/**
 * Class AbstractCarrier
 * @property Airline $airline
 * @property PhonesCollection $phones
 */
abstract class AbstractCarrier extends LoggerEntity
{
    /** @var Airline */
    protected $airline;
    /** @var PhonesCollection */
    protected $phones;

    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        $this->airline = new Airline($logger);
        $this->phones = new PhonesCollection($logger);
    }

}