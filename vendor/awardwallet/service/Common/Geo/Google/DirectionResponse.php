<?php


namespace AwardWallet\Common\Geo\Google;

use JMS\Serializer\Annotation as JMS;

/**
 * Described minimum fields.
 * FE: available_travel_modes - not used yet, so no described.
 * for more information look @link: https://developers.google.com/maps/documentation/directions/intro#DirectionsResponses
 */
class DirectionResponse extends GoogleResponse
{
    /**
     * contains an array with details about the geocoding of origin, destination and waypoints.
     *
     * @JMS\Type("array")
     *
     * @var array
     */
    private $geocoded_waypoints;

    /**
     * contains an array of routes from the origin to the destination.
     *
     * @JMS\Type("array")
     *
     * @var array
     */
    private $routes;

    /**
     * contains metadata on the request.
     * @link: https://developers.google.com/maps/documentation/directions/intro#StatusCodes
     *
     * @JMS\Type("string")
     *
     * @var string
     */
    private $status;


    /**
     * @return array
     */
    public function getGeocodedWaypoints(): array
    {
        return $this->geocoded_waypoints;
    }

    /**
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

}