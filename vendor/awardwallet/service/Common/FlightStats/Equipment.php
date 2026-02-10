<?php


namespace AwardWallet\Common\FlightStats;

use JMS\Serializer\Annotation as JMS;


class Equipment
{
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $iata;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $name;

    /**
     * @var bool
     * @JMS\Type("boolean")
     */
    private $turboProp;

    /**
     * @var bool
     * @JMS\Type("boolean")
     */
    private $jet;

    /**
     * @var bool
     * @JMS\Type("boolean")
     */
    private $widebody;

    /**
     * @var bool
     * @JMS\Type("boolean")
     */
    private $regional;

    /**
     * Equipment constructor.
     * @param string $iata
     * @param string $name
     * @param bool $turboProp
     * @param bool $jet
     * @param bool $widebody
     * @param bool $regional
     */
    public function __construct($iata, $name, $turboProp, $jet, $widebody, $regional)
    {
        $this->iata = $iata;
        $this->name = $name;
        $this->turboProp = $turboProp;
        $this->jet = $jet;
        $this->widebody = $widebody;
        $this->regional = $regional;
    }

    /**
     * @return string
     */
    public function getIata()
    {
        return $this->iata;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isTurboProp()
    {
        return $this->turboProp;
    }

    /**
     * @return bool
     */
    public function isJet()
    {
        return $this->jet;
    }

    /**
     * @return bool
     */
    public function isWidebody()
    {
        return $this->widebody;
    }

    /**
     * @return bool
     */
    public function isRegional()
    {
        return $this->regional;
    }


}