<?php

namespace AwardWallet\Common\AirLabs;

use JMS\Serializer\Annotation as JMS;

class FlightInfo
{
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $hex;               //	ICAO24 Hex address.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $reg_number;        //	Aircraft Registration Number
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $aircraft_icao;     //	Aircraft ICAO type. Available in the Free plan.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $flag;              //	ISO 2 country code from Countries DB. Available in the Free plan.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $lat;               //	Aircraft Geo-Latitude for now. Available in the Free plan.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $lng;               //	Aircraft Geo-Longitude for now. Available in the Free plan.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $alt;               //	Aircraft elevation for now (meters).
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $dir;               //	Aircraft head direction for now. Available in the Free plan.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $speed;             //	Aircraft horizontal speed (km) for now.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $v_speed;           //	Aircraft vertical speed (km) for now.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $squawk;            //	Aircraft squawk signal code.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $airline_iata;      //	Airline IATA code. Available in the Free plan.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $airline_icao;      //	Airline ICAO code.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $flight_iata;       //	Flight IATA code-number. Available in the Free plan.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $flight_icao;       //	Flight ICAO code-number.
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
    private $dep_iata;          //	Departure airport IATA code. Available in the Free plan.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $dep_icao;          //	Departure airport ICAO code.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $dep_terminal;      //	Estimated departure terminal.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $dep_gate;          //	Estimated departure gate.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $dep_time;          //	Departure time in the airport time zone. Available in the Free plan.
    /**
     * @var int
     * @JMS\Type("int")
     */
    private $dep_time_ts;       //	Departure UNIX timestamp.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $dep_time_utc;      //	Departure time in UTC time zone.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $dep_estimated;     //	Updated departure time in the airport time zone.
    /**
     * @var int
     * @JMS\Type("int")
     */
    private $dep_estimated_ts;  //	Updated departure UNIX timestamp.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $dep_estimated_utc; //	Updated departure time in UTC time zone.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $arr_iata;          //	Arrival airport IATA code. Available in the Free plan.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $arr_icao;          //	Arrival airport ICAO code.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $arr_terminal;      //	Estimated arrival terminal.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $arr_gate;          //	Estimated arrival gate.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $arr_baggage;       //	Arrival baggage claim carousel number.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $arr_time;          //	Arrival time in the airport time zone. Available in the Free plan.
    /**
     * @var int
     * @JMS\Type("int")
     */
    private $arr_time_ts;       //	Arrival UNIX timestamp.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $arr_time_utc;      //	Arrival time in UTC time zone.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $arr_estimated;     //	Updated arrival time in the airport time zone.
    /**
     * @var int
     * @JMS\Type("int")
     */
    private $arr_estimated_ts;  //	Updated arrival UNIX timestamp.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $arr_estimated_utc; //	Updated arrival time in UTC time zone.
    /**
     * @var int
     * @JMS\Type("int")
     */
    private $duration;          //	Estimated flight time (in minutes).
    /**
     * @var int
     * @JMS\Type("int")
     */
    private $delayed;           //	Estimated flight delay time (in minutes).
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $updated;           //	UNIX timestamp of last aircraft signal.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $status;            //	Current flight status - scheduled, en-route, landed.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $model;             //	Aircraft full model name.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $manufacturer;      //	Aircraft manufacturer name. Available in the Free plan.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $msn;               //	Manufacturer serial number.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $type;              //	Aircraft type - landplane, seaplane, tiltrotor, helicopter, gyrocopter, amphibian.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $engine;            //	Aircraft engine type - jet, piston, turboprop/turboshaft, electric.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $engine_count;      //	Aircraft engine number - 1, 2, 3, 4, 6, 8
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $built;             //	Aircraft built year
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $age;               //	Aircraft age (years)

    /**
     * @return mixed
     */
    public function getHex()
    {
        return $this->hex;
    }

    /**
     * @return mixed
     */
    public function getRegNumber()
    {
        return $this->reg_number;
    }

    /**
     * @return mixed
     */
    public function getAircraftIcao()
    {
        return $this->aircraft_icao;
    }

    /**
     * @return mixed
     */
    public function getFlag()
    {
        return $this->flag;
    }

    /**
     * @return mixed
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * @return mixed
     */
    public function getLng()
    {
        return $this->lng;
    }

    /**
     * @return mixed
     */
    public function getAlt()
    {
        return $this->alt;
    }

    /**
     * @return mixed
     */
    public function getDir()
    {
        return $this->dir;
    }

    /**
     * @return mixed
     */
    public function getSpeed()
    {
        return $this->speed;
    }

    /**
     * @return mixed
     */
    public function getVSpeed()
    {
        return $this->v_speed;
    }

    /**
     * @return mixed
     */
    public function getSquawk()
    {
        return $this->squawk;
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
    public function getAirlineIcao()
    {
        return $this->airline_icao;
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
    public function getFlightIcao()
    {
        return $this->flight_icao;
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
    public function getDepIata()
    {
        return $this->dep_iata;
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
    public function getDepTerminal()
    {
        return $this->dep_terminal;
    }

    /**
     * @return mixed
     */
    public function getDepGate()
    {
        return $this->dep_gate;
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
    public function getDepTimeTs()
    {
        return $this->dep_time_ts;
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
    public function getDepEstimated()
    {
        return $this->dep_estimated;
    }

    /**
     * @return mixed
     */
    public function getDepEstimatedTs()
    {
        return $this->dep_estimated_ts;
    }

    /**
     * @return mixed
     */
    public function getDepEstimatedUtc()
    {
        return $this->dep_estimated_utc;
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
    public function getArrIcao()
    {
        return $this->arr_icao;
    }

    /**
     * @return mixed
     */
    public function getArrTerminal()
    {
        return $this->arr_terminal;
    }

    /**
     * @return mixed
     */
    public function getArrGate()
    {
        return $this->arr_gate;
    }

    /**
     * @return mixed
     */
    public function getArrBaggage()
    {
        return $this->arr_baggage;
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
    public function getArrTimeTs()
    {
        return $this->arr_time_ts;
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
    public function getArrEstimated()
    {
        return $this->arr_estimated;
    }

    /**
     * @return mixed
     */
    public function getArrEstimatedTs()
    {
        return $this->arr_estimated_ts;
    }

    /**
     * @return mixed
     */
    public function getArrEstimatedUtc()
    {
        return $this->arr_estimated_utc;
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
    public function getDelayed()
    {
        return $this->delayed;
    }

    /**
     * @return mixed
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @return mixed
     */
    public function getManufacturer()
    {
        return $this->manufacturer;
    }

    /**
     * @return mixed
     */
    public function getMsn()
    {
        return $this->msn;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * @return mixed
     */
    public function getEngineCount()
    {
        return $this->engine_count;
    }

    /**
     * @return mixed
     */
    public function getBuilt()
    {
        return $this->built;
    }

    /**
     * @return mixed
     */
    public function getAge()
    {
        return $this->age;
    }

}