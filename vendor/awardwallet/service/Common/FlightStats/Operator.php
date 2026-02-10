<?php


namespace AwardWallet\Common\FlightStats;

use JMS\Serializer\Annotation as JMS;

class Operator
{

    /**
     * @var string = null
     * @JMS\Type("string")
     */
    private $carrierFsCode = null;

    /**
     * @var Airline
     */
    private $carrier = null;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $flightNumber;

    /**
     * @return string
     */
    public function getCarrierFsCode()
    {
        return $this->carrierFsCode;
    }

    public function getCarrier()
    {
        return $this->carrier;
    }

    /**
     * @param Airline $carrier
     * @return Operator
     */
    public function setCarrier(Airline $carrier)
    {
        $this->carrier = $carrier;
        return $this;
    }

    /**
     * @return string
     */
    public function getFlightNumber()
    {
        return $this->flightNumber;
    }

}