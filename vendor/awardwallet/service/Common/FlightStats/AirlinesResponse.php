<?php
namespace AwardWallet\Common\FlightStats;


use JMS\Serializer\Annotation as JMS;


class AirlinesResponse
{
    /**
     * @var Airline[]
     * @JMS\Type("array<AwardWallet\Common\FlightStats\Airline>")
     */
    private $airlines;

    /**
     * AirlinesResponse constructor.
     * @param array $airlines
     */
    public function __construct(array $airlines)
    {
        $this->airlines = $airlines;
    }

    /**
     * @return Airline[]
     */
    public function getAirlines(): array
    {
        return $this->airlines;
    }
}