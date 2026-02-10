<?php


namespace AwardWallet\Common\FlightStats;

use JMS\Serializer\Annotation as JMS;


class ScheduleAppendix
{
    /**
     * @var Airline[]
     * @JMS\Type("array<AwardWallet\Common\FlightStats\Airline>")
     */
    private $airlines = [];

    /**
     * @var Airport[]
     * @JMS\Type("array<AwardWallet\Common\FlightStats\Airport>")
     */
    private $airports = [];

    /**
     * @var Equipment[]
     * @JMS\Type("array<AwardWallet\Common\FlightStats\Equipment>")
     */
    private $equipments = [];

    /**
     * ScheduleAppendix constructor.
     * @param Airline[] $airlines
     * @param Airport[] $airports
     * @param Equipment[] $equipments
     */
    public function __construct(array $airlines, array $airports, array $equipments)
    {
        $this->airlines = $airlines;
        $this->airports = $airports;
        $this->equipments = $equipments;
    }

    /**
     * @return Airline[]
     */
    public function getAirlines()
    {
        return $this->airlines;
    }

    /**
     * @return Airport[]
     */
    public function getAirports()
    {
        return $this->airports;
    }

    /**
     * @return Equipment[]
     */
    public function getEquipments()
    {
        return $this->equipments;
    }


}