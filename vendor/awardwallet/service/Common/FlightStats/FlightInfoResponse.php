<?php


namespace AwardWallet\Common\FlightStats;


class FlightInfoResponse
{
    /**
     * The flight identification number and any additional characters (String).
     * @var string
     */
    private $flightNumber;

    public function getFlightNumber ()
    {
        return $this->flightNumber;
    }
}