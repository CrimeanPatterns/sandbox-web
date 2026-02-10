<?php


namespace AwardWallet\Common\Parsing\Solver\Helper;


use AwardWallet\Common\FlightStats\Airline;
use AwardWallet\Common\FlightStats\Airport;
use AwardWallet\Common\FlightStats\FlightStatus;
use AwardWallet\Common\FlightStats\Operator;
use AwardWallet\Common\FlightStats\ScheduledFlight;

class FlightSegmentData
{

    /** @var Airline */
    public $carrier;

    /** @var string */
    public $fn;

    /** @var Airport */
    public $depAir;

    /** @var Airport */
    public $arrAir;

    /** @var int */
    public $depDate;

    /** @var int */
    public $arrDate;

    /** @var string */
    public $depTerminal;

    /** @var string */
    public $arrTerminal;

    /** @var string */
    public $aircraftIata;

    /** @var Airline */
    public $operatorCarrier;

    /** @var string */
    public $operatorFn;

    public function __construct(
        Airline $carrier, string $fn,
        ?Airport $depAir, ?Airport $arrAir, int $depDate, int $arrDate,
        ?string $depTer, ?string $arrTer, ?string $aircraftIata, ?Airline $operatorCarrier, ?string $operatorFn)
    {
        $this->carrier = $carrier;
        $this->fn = $fn;
        $this->depAir = $depAir;
        $this->arrAir = $arrAir;
        $this->depDate = $depDate;
        $this->arrDate = $arrDate;
        $this->depTerminal = $depTer;
        $this->arrTerminal = $arrTer;
        $this->aircraftIata = $aircraftIata;
        $this->operatorCarrier = $operatorCarrier;
        $this->operatorFn = $operatorFn;
    }

    public static function fromSchedule(ScheduledFlight $sch): FlightSegmentData
    {
        if (!empty($sch->getOperator()))
            $op = $sch->getOperator();
        else
            $op = new Operator();
        return new self(
            $sch->getCarrier(),
            $sch->getFlightNumber(),
            $sch->getDepartureAirport(),
            $sch->getArrivalAirport(),
            strtotime($sch->getDepartureTime()),
            strtotime($sch->getArrivalTime()),
            $sch->getDepartureTerminal(),
            $sch->getArrivalTerminal(),
            $sch->getFlightEquipmentIataCode(),
            $op->getCarrier(),
            $op->getFlightNumber()
        );
    }

    public static function fromStatus(FlightStatus $st, ?string $iata, ?string $fn): FlightSegmentData
    {
        $match = false;
        $carrier = $flightNumber = null;
        $opCarrier = $opFlightNumber = null;
        /** @var Airline $car */
        foreach([$st->getCarrier(), $st->getPrimaryCarrier()] as $car)
            if (!empty($iata) && strcmp($iata, $car->getIata()) === 0) {
                $carrier = $car;
                $flightNumber = $st->getFlightNumber();
                $match = true;
                break;
            }
        if (!$match && !empty($fn) && strcmp($fn, $st->getFlightNumber()) === 0) {
            $carrier = $car;
            $flightNumber = $st->getFlightNumber();
            $match = true;
        }
        if (!$match && !empty($st->getCodeshares()))
            foreach($st->getCodeshares() as $cs)
                if (!empty($iata) && strcmp($iata, $cs->getCarrier()->getIata()) === 0
                    || !empty($fn) && strcmp($fn, $cs->getFlightNumber()) === 0) {
                    $carrier = $cs->getCarrier();
                    $flightNumber = $cs->getFlightNumber();
                    $opCarrier = $st->getCarrier();
                    $opFlightNumber = $st->getFlightNumber();
                    break;
                }
        return new self(
            $carrier,
            $flightNumber,
            $st->getDepartureAirport(),
            $st->getArrivalAirport(),
            strtotime($st->getDepartureDate()->getDateLocal()),
            strtotime($st->getArrivalDate()->getDateLocal()),
            $st->getAirportResources()->getDepartureTerminal(),
            $st->getAirportResources()->getArrivalTerminal(),
            $st->getFlightEquipment()->getScheduledEquipmentIataCode(),
            $opCarrier,
            $opFlightNumber
        );
    }

}