<?php


namespace AwardWallet\Common\AirLabs;

use JMS\Serializer\Annotation as JMS;

class RoutesRequest
{
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $dep_iata;      //  required	Filtering by departure Airport IATA code.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $arr_iata;      //  required	Filtering by arrival Airport IATA code.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $airline_iata;  //	required	Filtering by Airline IATA code.
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $flight_number; //	optional	Filtering by Flight number only.
//    private $_fields;       //	optional	Fields to return (comma separated, e.g.: airline_iata,flight_number)

    /**
     * RoutesRequest constructor.
     * @param $dep_iata
     * @param $arr_iata
     * @param $airline_iata
     * @param $flight_number
     */
    public function __construct(string $dep_iata, string $arr_iata, string $airline_iata, string $flight_number)
    {
        $this->dep_iata = $dep_iata;
        $this->arr_iata = $arr_iata;
        $this->airline_iata = $airline_iata;
        $this->flight_number = $flight_number;
    }
}