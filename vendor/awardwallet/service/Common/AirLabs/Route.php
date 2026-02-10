<?php


namespace AwardWallet\Common\AirLabs;

use JMS\Serializer\Annotation as JMS;

class Route
{

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $airline_icao;      //	Airline ICAO code. Available in the Free plan.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $airline_iata;      //	Airline IATA code. Available in the Free plan.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $flight_icao;       //	Flight ICAO code-number. Available in the Free plan.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $flight_iata;       //	Flight IATA code-number. Available in the Free plan.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $flight_number;     //	Flight number only. Available in the Free plan.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $cs_airline_iata;   //	Codeshared airline IATA code.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $cs_flight_iata;    //	Codeshared flight IATA code-number.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $cs_flight_number;  //	Codeshared flight number.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $dep_icao;          //	Departure airport ICAO code.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $dep_iata;          //	Departure airport IATA code.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $dep_time;          //	Departure time in the airport time zone.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $dep_time_utc;      //	Departure time in UTC time zone.
    /**
     * @var string[]
     * @JMS\Type("array<string>")
     */
    private $dep_terminals;     //	List of possible departure terminals.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $arr_icao;          //	Arrival airport ICAO code.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $arr_iata;          //	Arrival airport IATA code.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $arr_time;          //	Time of arrival in the airport time zone.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $arr_time_utc;      //	Time of arrival in the UTC time zone.
    /**
     * @var string[]
     * @JMS\Type("array<string>")
     */
    private $arr_terminals;     //	A list of possible arrival terminals.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $duration;          //	Estimated flight time (in minutes).
    /**
     * @var string[]
     * @JMS\Type("array<string>")
     */
    private $days;              // Flight departure days - sun, mon, tue, wed, thu, fri, sat.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $aircraft_icao;     // Aircraft ICAO code.

    /**
     * @return mixed
     */
    public function getAirlineIcao()
    {
        return $this->airline_icao;
    }

    /**
     * @return mixed
     */
    public function getAirlineIata()
    {
        return $this->airline_iata;
    }

    /**
     * @return mixed
     */
    public function getFlightIcao()
    {
        return $this->flight_icao;
    }

    /**
     * @return mixed
     */
    public function getFlightIata()
    {
        return $this->flight_iata;
    }

    /**
     * @return mixed
     */
    public function getFlightNumber()
    {
        return $this->flight_number;
    }

    /**
     * @return mixed
     */
    public function getCsAirlineIata()
    {
        return $this->cs_airline_iata;
    }

    /**
     * @return mixed
     */
    public function getCsFlightIata()
    {
        return $this->cs_flight_iata;
    }

    /**
     * @return mixed
     */
    public function getCsFlightNumber()
    {
        return $this->cs_flight_number;
    }

    /**
     * @return mixed
     */
    public function getDepIcao()
    {
        return $this->dep_icao;
    }

    /**
     * @return mixed
     */
    public function getDepIata()
    {
        return $this->dep_iata;
    }

    /**
     * @return mixed
     */
    public function getDepTime()
    {
        return $this->dep_time;
    }

    /**
     * @return mixed
     */
    public function getDepTimeUtc()
    {
        return $this->dep_time_utc;
    }

    /**
     * @return mixed
     */
    public function getDepTerminals()
    {
        return $this->dep_terminals;
    }

    /**
     * @return mixed
     */
    public function getArrIcao()
    {
        return $this->arr_icao;
    }

    /**
     * @return mixed
     */
    public function getArrIata()
    {
        return $this->arr_iata;
    }

    /**
     * @return mixed
     */
    public function getArrTime()
    {
        return $this->arr_time;
    }

    /**
     * @return mixed
     */
    public function getArrTimeUtc()
    {
        return $this->arr_time_utc;
    }

    /**
     * @return mixed
     */
    public function getArrTerminals()
    {
        return $this->arr_terminals;
    }

    /**
     * @return mixed
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @return mixed
     */
    public function getDays()
    {
        return $this->days;
    }

    /**
     * @return mixed
     */
    public function getAircraftIcao()
    {
        return $this->aircraft_icao;
    }

}