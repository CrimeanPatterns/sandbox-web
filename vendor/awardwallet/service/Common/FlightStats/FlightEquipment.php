<?php


namespace AwardWallet\Common\FlightStats;

use JMS\Serializer\Annotation as JMS;

class FlightEquipment
{

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $scheduledEquipmentIataCode;

    /**
     * @return string
     */
    public function getScheduledEquipmentIataCode(): ?string
    {
        return $this->scheduledEquipmentIataCode;
    }

}
