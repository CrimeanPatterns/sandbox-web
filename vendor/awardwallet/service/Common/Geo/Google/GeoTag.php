<?php


namespace AwardWallet\Common\Geo\Google;

use AwardWallet\Common\Geo\Google\Traits\HasAddressComponents;
use JMS\Serializer\Annotation as JMS;

class GeoTag
{
    use HasAddressComponents;

    /**
     * Array indicates the type of the returned result.
     * This array contains a set of zero or more tags identifying the type of feature returned in the result.
     * For example, a geocode of "Chicago" returns "locality" which indicates that "Chicago" is a city, and also returns "political" which indicates it is a political entity.
     *
     * @JMS\Type("array<string>")
     *
     * @var string[]
     */
    private $types = [];

    /**
     * An array denoting all the localities contained in a postal code.
     * This is only present when the result is a postal code that contains multiple localities.
     *
     * @JMS\SerializedName("postcode_localities")
     * @JMS\Type("array<string>")
     *
     * @var string[]
     */
    private $postcodeLocalities;

    /**
     * @JMS\Type("AwardWallet\Common\Geo\Google\Geometry")
     *
     * @var Geometry
     */
    private $geometry;

    /**
     * Indicates that the geocoder did not return an exact match for the original request, though it was able to match part of the requested address.
     * You may wish to examine the original request for misspellings and/or an incomplete address.
     *
     * Partial matches most often occur for street addresses that do not exist within the locality you pass in the request.
     * Partial matches may also be returned when a request matches two or more locations in the same locality.
     * For example, "21 Henr St, Bristol, UK" will return a partial match for both Henry Street and Henrietta Street.
     * Note that if a request includes a misspelled address component, the geocoding service may suggest an alternative address.
     * Suggestions triggered in this way will also be marked as a partial match.
     *
     * @JMS\SerializedName("partial_match")
     * @JMS\Type("boolean")
     *
     * @var bool
     */
    private $partialMatch;

    /**
     * A unique identifier that can be used with other Google APIs.
     * For example, you can use the place_id in a GoogleApi::placeDetails() request to get details of a local business,
     * such as phone number, opening hours, user reviews, and more.
     * @link https://developers.google.com/places/place-id
     *
     * @JMS\SerializedName("place_id")
     * @JMS\Type("string")
     *
     * @var string
     */
    private $placeId;

    /**
     * Array indicates the type of the returned result.
     * This array contains a set of zero or more tags identifying the type of feature returned in the result.
     * For example, a geocode of "Chicago" returns "locality" which indicates that "Chicago" is a city, and also returns "political" which indicates it is a political entity.
     *
     * @return string[]
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * An array denoting all the localities contained in a postal code.
     * This is only present when the result is a postal code that contains multiple localities.
     *
     * @return string[]
     */
    public function getPostcodeLocalities(): array
    {
        return $this->postcodeLocalities;
    }

    /**
     * @return Geometry
     */
    public function getGeometry(): Geometry
    {
        return $this->geometry;
    }

    /**
     * Indicates that the geocoder did not return an exact match for the original request, though it was able to match part of the requested address.
     * You may wish to examine the original request for misspellings and/or an incomplete address.
     *
     * Partial matches most often occur for street addresses that do not exist within the locality you pass in the request.
     * Partial matches may also be returned when a request matches two or more locations in the same locality.
     * For example, "21 Henr St, Bristol, UK" will return a partial match for both Henry Street and Henrietta Street.
     * Note that if a request includes a misspelled address component, the geocoding service may suggest an alternative address.
     * Suggestions triggered in this way will also be marked as a partial match.
     *
     * @return bool
     */
    public function isPartialMatch(): bool
    {
        return $this->partialMatch;
    }

    /**
     * A unique identifier that can be used with other Google APIs.
     * For example, you can use the place_id in a GoogleApi::placeDetails() request to get details of a local business,
     * such as phone number, opening hours, user reviews, and more.
     * @link https://developers.google.com/places/place-id
     *
     * @return string
     */
    public function getPlaceId(): string
    {
        return $this->placeId;
    }
}