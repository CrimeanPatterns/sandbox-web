<?php


namespace AwardWallet\Common\FlightStats;

use JMS\Serializer\Annotation as JMS;

class ShortCodeshare
{

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $fsCode;

    /**
     * @var Airline
     */
    private $carrier;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $flightNumber;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $relationship;

    /**
     * @return string
     */
    public function getFsCode(): string
    {
        return $this->fsCode;
    }

    /**
     * @return Airline
     */
    public function getCarrier(): Airline
    {
        return $this->carrier;
    }

    /**
     * @param Airline $carrier
     */
    public function setCarrier(Airline $carrier): void
    {
        $this->carrier = $carrier;
    }

    /**
     * @return string
     */
    public function getFlightNumber(): string
    {
        return $this->flightNumber;
    }

    /**
     * @return string
     */
    public function getRelationship(): string
    {
        return $this->relationship;
    }



}