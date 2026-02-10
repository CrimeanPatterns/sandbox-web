<?php


namespace AwardWallet\Common\Geo\Google;

use JMS\Serializer\Annotation as JMS;

class Geometry
{
    /**
     * @JMS\Type("AwardWallet\Common\Geo\Google\LatLng")
     *
     * @var LatLng
     */
    private $location;

    /**
     * @JMS\SerializedName("viewport")
     * @JMS\Type("AwardWallet\Common\Geo\Google\ViewPort")
     *
     * @var ViewPort|null
     */
    private $viewPort;

    /**
     * Stores the bounding box which can fully contain the returned result.
     * Note that these bounds may not match the recommended viewport.
     * For example, San Francisco includes the Farallon islands, which are technically part of the city, but probably should not be returned in the viewport.
     *
     * @JMS\Type("AwardWallet\Common\Geo\Google\ViewPort")
     *
     * @var ViewPort|null
     */
    private $bounds;

    /**
     * Stores additional data about the specified location. The following values are currently supported:
     * "ROOFTOP" indicates that the returned result is a precise geocode for which we have location information accurate down to street address precision.
     * "RANGE_INTERPOLATED" indicates that the returned result reflects an approximation (usually on a road) interpolated between two precise points (such as intersections).
     * Interpolated results are generally returned when rooftop geocodes are unavailable for a street address.
     * "GEOMETRIC_CENTER" indicates that the returned result is the geometric center of a result such as a polyline (for example, a street) or polygon (region).
     * "APPROXIMATE" indicates that the returned result is approximate.
     *
     * @JMS\Type("string")
     * @var string|null
     */
    private $locationType;

    /**
     * @return LatLng
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @return ViewPort|null
     */
    public function getViewPort()
    {
        return $this->viewPort;
    }

    /**
     * @return ViewPort|null
     */
    public function getBounds()
    {
        return $this->bounds;
    }

    /**
     * @return string|null
     */
    public function getLocationType()
    {
        return $this->locationType;
    }
}