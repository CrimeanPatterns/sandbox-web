<?php


namespace AwardWallet\Common\AirLabs;

use AwardWallet\Common\AirLabs\Route;
use JMS\Serializer\Annotation as JMS;

class RoutesResponse
{
//    private $request;
//    private $terms;
    /**
     * @var Error
     * @JMS\Type("AwardWallet\Common\AirLabs\Error")
     */
    private $error;

    /**
     * @var Route[]
     * @JMS\Type("array<AwardWallet\Common\AirLabs\Route>")
     */
    private $response;

    /**
     * @return Route[]
     */
    public function getRoutes(): array
    {
        return $this->response;
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        return (null !== $this->error && null !== $this->error->getCode());
    }

    /**
     * @return Error
     */
    public function getError(): Error
    {
        return $this->error;
    }


}