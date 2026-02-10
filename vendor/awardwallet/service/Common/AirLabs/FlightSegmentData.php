<?php


namespace AwardWallet\Common\AirLabs;


use AwardWallet\Common\AirLabs\FlightInfo;
use AwardWallet\Common\AirLabs\Route;

class FlightSegmentData
{
    // TODO: not full info. just necessary for now

    /** @var string */
    public $carrier;

    /** @var string */
    public $fn;

    /** @var string */
    public $depCode;

    /** @var string */
    public $arrCode;

    /** @var int */
    public $depDate;

    /** @var int */
    public $arrDate;

    /** @var string[] */
    public $depTerminal;

    /** @var string[] */
    public $arrTerminal;

    /** @var string */
    public $aircraftIata;

    /** @var string */
    public $operatorCarrier;

    /** @var string */
    public $operatorFn;

    public function __construct(
        string $carrier,
        string $fn,
        ?string $depCode,
        ?string $arrCode,
        int $depDate,
        int $arrDate,
        $depTer = null,
        $arrTer = null,
        ?string $aircraftIata,
        ?string $operatorCarrier,
        ?string $operatorFn
    ) {
        $this->carrier = $carrier;
        $this->fn = $fn;
        $this->depCode = $depCode;
        $this->arrCode = $arrCode;
        $this->depDate = $depDate;
        $this->arrDate = $arrDate;
        $this->depTerminal = $depTer;
        $this->arrTerminal = $arrTer;
        $this->aircraftIata = $aircraftIata;
        $this->operatorCarrier = $operatorCarrier;
        $this->operatorFn = $operatorFn;
    }

    public static function fromRoute(Route $route, int $depDate, int $arrDate)
    {
        return new self(
            $route->getAirlineIata(),
            $route->getFlightNumber(),
            $route->getDepIata(),
            $route->getArrIata(),
            strtotime($route->getDepTime(), $depDate),
            strtotime($route->getArrTime(), $arrDate),
            $route->getDepTerminals(),
            $route->getArrTerminals(),
            $route->getAircraftIcao(),
            $route->getCsAirlineIata(),
            $route->getCsFlightNumber()
        );
    }

    public static function fromFlight(FlightInfo $flightInfo)
    {
        return new self(
            $flightInfo->getAirlineIata(),
            $flightInfo->getFlightNumber(),
            $flightInfo->getDepIata(),
            $flightInfo->getArrIata(),
            strtotime($flightInfo->getDepTime()),
            strtotime($flightInfo->getArrTime()),
            $flightInfo->getDepTerminal(),
            $flightInfo->getArrTerminal(),
            $flightInfo->getAircraftIcao(),
            $flightInfo->getCsAirlineIata(),
            $flightInfo->getCsFlightNumber()
        );
    }
}