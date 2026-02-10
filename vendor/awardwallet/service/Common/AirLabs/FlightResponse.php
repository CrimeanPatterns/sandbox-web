<?php


namespace AwardWallet\Common\AirLabs;

use AwardWallet\Common\AirLabs\FlightInfo;
use JMS\Serializer\Annotation as JMS;

class FlightResponse
{
//    private $request;
//    private $terms;

    /**
     * @var Error
     * @JMS\Type("AwardWallet\Common\AirLabs\Error")
     */
    private $error;

    /**
     * @var FlightInfo
     * @JMS\Type("AwardWallet\Common\AirLabs\FlightInfo")
     */
    private $response;

    /**
     * @return FlightInfo
     */
    public function getFlightInfo(): FlightInfo
    {
        return $this->response;
    }

    /**
     * @return Error
     */
    public function getError(): Error
    {
        return $this->error;
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        if (null !== $this->error && null !== $this->error->getCode()) {
            return true;
        } else {
            return false;
        }
    }
}