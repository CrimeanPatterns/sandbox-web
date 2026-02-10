<?php


namespace AwardWallet\Common\FlightStats;

use JMS\Serializer\Annotation as JMS;

class DualDate
{

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $dateUtc;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $dateLocal;

    /**
     * @return string
     */
    public function getDateUtc(): string
    {
        return $this->dateUtc;
    }

    /**
     * @return string
     */
    public function getDateLocal(): string
    {
        return $this->dateLocal;
    }

}