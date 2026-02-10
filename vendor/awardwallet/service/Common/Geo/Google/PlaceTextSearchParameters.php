<?php


namespace AwardWallet\Common\Geo\Google;


class PlaceTextSearchParameters extends Parameters
{
    /**
     * The text string on which to search, for example: "restaurant" or "123 Main Street".
     * The Google Places service will return candidate matches based on this string and order the results based on their perceived relevance.
     * This parameter becomes optional if the type parameter is also used in the search request.
     *
     * @var string
     */
    private $query = null;

    /**
     * The latitude/longitude around which to retrieve place information.
     * If you specify a location parameter, you must also specify a radius parameter.
     *
     * @var LatLng|null
     */
    private $location = null;

    /**
     * Defines the distance (in meters) within which to bias place results. The maximum allowed radius is 50 000 meters.
     * Results inside of this region will be ranked higher than results outside of the search circle; however, prominent results from outside of the search radius may be included.
     *
     * @var int|null
     */
    private $radius = null;

    /**
     * The language code, indicating in which language the results should be returned, if possible.
     * see https://developers.google.com/maps/faq#languagesupport the list of supported languages and their codes.
     * Note that we often update supported languages so this list may not be exhaustive.
     *
     * @var string|null
     */
    private $language = null;

    /**
     * Restricts results to only those places within the specified price level.
     * Valid values are in the range from 0 (most affordable) to 4 (most expensive), inclusive.
     * The exact amount indicated by a specific value will vary from region to region.
     *
     * @var int|null
     */
    private $minPrice = null;

    /**
     * Restricts results to only those places within the specified price level.
     * Valid values are in the range from 0 (most affordable) to 4 (most expensive), inclusive.
     * The exact amount indicated by a specific value will vary from region to region.
     *
     * @var int|null
     */
    private $maxPrice = null;

    /**
     * Returns only those places that are open for business at the time the query is sent.
     * Places that do not specify opening hours in the Google Places database will not be returned if you include this parameter in your query.
     *
     * @var boolean
     */
    private $openNow = null;

    /**
     * Returns the next 20 results from a previously run search.
     * Setting a pagetoken parameter will execute a search with the same parameters used previously — all parameters other than pagetoken will be ignored.
     *
     * @var string|null
     */
    private $pageToken = null;

    /**
     * Restricts the results to places matching the specified type.
     * Only one type may be specified (if more than one type is provided, all types following the first entry are ignored).
     * see https://developers.google.com/places/web-service/supported_types
     *
     * @var string|null
     */
    private $type = null;

    /**
     * @return array
     */
    protected function getAllParametersAsArray(): array
    {
        return [
            'query'     => $this->query,
            'location'  => $this->location,
            'radius'    => $this->radius,
            'language'  => $this->language,
            'minprice'  => $this->minPrice,
            'maxprice'  => $this->maxPrice,
            'opennow'   => $this->openNow,
            'pagetoken' => $this->pageToken,
            'type'      => $this->type
        ];
    }

    /**
     * PlaceTextSearchParameters constructor.
     * @param string|null $query
     * @param string|null $type
     */
    private function __construct(string $query = null, string $type = null)
    {
        if (null === $query && null === $type) {
            throw new \InvalidArgumentException("Either query or type should be specified");
        }
        $this->query = $query;
        $this->type = $type;
    }

    /**
     * The latitude/longitude around which to retrieve place information.
     *
     * @return LatLng|null
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * The distance (in meters) within which to bias place results
     *
     * @return int|null
     */
    public function getRadius()
    {
        return $this->radius;
    }

    /**
     * The latitude/longitude around which to retrieve place information (must be specified as latitude,longitude)
     * and the distance (in meters) within which to bias place results. The maximum allowed radius is 50 000 meters.
     *
     * @param LatLng $location
     * @param int $radius
     * @return $this
     */
    public function setLocationAndRadius(LatLng $location, int $radius)
    {
        if ($radius > 50000) {
            throw new \InvalidArgumentException("Radius cannot exceed 50 000 meters");
        }
        $this->location = $location;
        $this->radius = $radius;

        return $this;
    }

    /**
     * The language code, indicating in which language the results should be returned, if possible.
     * @link https://developers.google.com/maps/faq#languagesupport the list of supported languages and their codes.
     * Note that we often update supported languages so this list may not be exhaustive.
     *
     * @return null|string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * The language code, indicating in which language the results should be returned, if possible.
     * @link https://developers.google.com/maps/faq#languagesupport the list of supported languages and their codes.
     * Note that google often update supported languages so this list may not be exhaustive.
     *
     * @param string $language
     * @return $this
     */
    public function setLanguage(string $language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Restricts results to only those places within the specified price level.
     * Valid values are in the range from 0 (most affordable) to 4 (most expensive), inclusive.
     * The exact amount indicated by a specific value will vary from region to region.
     *
     * @return int|null
     */
    public function getMinPrice()
    {
        return $this->minPrice;
    }

    /**
     * Restricts results to only those places within the specified price level.
     * Valid values are in the range from 0 (most affordable) to 4 (most expensive), inclusive.
     * The exact amount indicated by a specific value will vary from region to region.
     *
     * @return int|null
     */
    public function getMaxPrice()
    {
        return $this->maxPrice;
    }

    /**
     * @param int $minPrice
     * @return $this
     */
    public function setMinPrice(int $minPrice)
    {
        if ($minPrice > 4 || $minPrice < 0) {
            throw new \InvalidArgumentException("Valid values are between 0 and 4 inclusive");
        }
        if (null !== $this->maxPrice && $minPrice > $this->maxPrice) {
            throw new \InvalidArgumentException("Minimum price cannot be higher than maximum price");
        }
        $this->minPrice = $minPrice;

        return $this;
    }

    /**
     * @param int $maxPrice
     * @return $this
     */
    public function setMaxPrice(int $maxPrice)
    {
        if ($maxPrice > 4 || $maxPrice < 0) {
            throw new \InvalidArgumentException("Valid values are between 0 and 4 inclusive");
        }
        if (null !== $this->minPrice && $maxPrice < $this->minPrice) {
            throw new \InvalidArgumentException("Maximum price cannot be lower than minimum price");
        }
        $this->minPrice = $maxPrice;

        return $this;
    }

    /**
     * Returns only those places that are open for business at the time the query is sent.
     * Places that do not specify opening hours in the Google Places database will not be returned if you include this parameter in your query.
     *
     * @return bool
     */
    public function isOpenNow(): bool
    {
        return $this->openNow;
    }

    /**
     * Returns only those places that are open for business at the time the query is sent.
     * Places that do not specify opening hours in the Google Places database will not be returned if you include this parameter in your query.
     *
     * @return $this
     */
    public function setOpenNow()
    {
        $this->openNow = true;

        return $this;
    }

    /**
     * Returns the next 20 results from a previously run search.
     * Setting a pagetoken parameter will execute a search with the same parameters used previously — all parameters other than pagetoken will be ignored.
     *
     * @return null|string
     */
    public function getPageToken()
    {
        return $this->pageToken;
    }

    /**
     * Returns the next 20 results from a previously run search.
     * Setting a pagetoken parameter will execute a search with the same parameters used previously — all parameters other than pagetoken will be ignored.
     *
     * @param string $pageToken
     * @return $this
     */
    public function setPageToken(string $pageToken)
    {
        $this->pageToken = $pageToken;

        return $this;
    }

    /**
     * Restricts the results to places matching the specified type.
     * @link https://developers.google.com/places/web-service/supported_types
     *
     * @return null|string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Restricts the results to places matching the specified type.
     * @link https://developers.google.com/places/web-service/supported_types
     *
     * @param string $type
     * @return $this
     */
    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param string $query
     * @return PlaceTextSearchParameters
     */
    public static function makeFromQuery(string $query)
    {
        if (empty($query)) {
            throw new \InvalidArgumentException("Query cannot be empty");
        }
        return new PlaceTextSearchParameters($query);
    }

    /**
     * @param string $type
     * @return PlaceTextSearchParameters
     */
    public static function makeFromType(string $type)
    {
        if (empty($type)) {
            throw new \InvalidArgumentException("Type cannot be empty");
        }
        return new PlaceTextSearchParameters(null, $type);
    }
}