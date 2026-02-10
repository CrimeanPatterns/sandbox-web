<?php


namespace AwardWallet\Common\Geo\Google;


class ReverseGeoCodeParameters extends Parameters
{
    /**
     * The latitude value specifying the location for which you wish to obtain the closest, human-readable address.
     *
     * @var float
     */
    private $lat;

    /**
     * The longitude value specifying the location for which you wish to obtain the closest, human-readable address.
     *
     * @var float
     */
    private $lng;

    /**
     * A filter of one or more address types.
     * If the parameter contains multiple address types, the API returns all addresses that match any of the types.
     * A note about processing: The result_type parameter does not restrict the search to the specified address type(s).
     * Rather, the result_type acts as a post-search filter: the API fetches all results for the specified latlng,
     * then discards those results that do not match the specified address type(s).
     *
     * @link https://developers.google.com/maps/documentation/geocoding/intro#ReverseGeocoding
     *
     * @var string[]
     */
    private $resultType = [];

    /**
     * A filter of one or more location types.
     * If the parameter contains multiple location types, the API returns all addresses that match any of the types.
     * A note about processing: The location_type parameter does not restrict the search to the specified location type(s).
     * Rather, the location_type acts as a post-search filter: the API fetches all results for the specified latlng,
     * then discards those results that do not match the specified location type(s).
     * The following values are supported:
     *  "ROOFTOP" returns only the addresses for which Google has location information accurate down to street address precision.
     *  "RANGE_INTERPOLATED" returns only the addresses that reflect an approximation (usually on a road) interpolated between two precise points (such as intersections).
     *      An interpolated range generally indicates that rooftop geocodes are unavailable for a street address.
     *  "GEOMETRIC_CENTER" returns only geometric centers of a location such as a polyline (for example, a street) or polygon (region).
     *  "APPROXIMATE" returns only the addresses that are characterized as approximate.
     *
     * @var string[]
     */
    private $locationType = [];

    /**
     * The language in which to return results.
     *
     * @link https://developers.google.com/maps/faq#languagesupport list of supported languages.
     * Google often updates the supported languages, so this list may not be exhaustive.
     *
     * The geocoder does its best to provide a street address that is readable for both the user and locals.
     * To achieve that goal, it returns street addresses in the local language, transliterated to a script readable by the user if necessary, observing the preferred language.
     * All other addresses are returned in the preferred language. Address components are all returned in the same language, which is chosen from the first component.
     *
     * If a name is not available in the preferred language, the geocoder uses the closest match.
     *
     * @var string
     */
    private $language = 'en';

    /**
     * @return array
     */
    protected function getAllParametersAsArray(): array
    {
        return [
            'latlng' => "$this->lat,$this->lng",
            'result_type' => implode('|', $this->resultType),
            'location_type' => implode('|', $this->locationType),
            'language'   => $this->language,
        ];
    }

    private function __construct(float $lat, float $lng)
    {
        $this->lat = $lat;
        $this->lng = $lng;
    }

    /**
     * The language in which to return results.
     *
     * The geocoder does its best to provide a street address that is readable for both the user and locals.
     * To achieve that goal, it returns street addresses in the local language, transliterated to a script readable by the user if necessary, observing the preferred language.
     * All other addresses are returned in the preferred language. Address components are all returned in the same language, which is chosen from the first component.
     *
     * If a name is not available in the preferred language, the geocoder uses the closest match.
     *
     * The preferred language has a small influence on the set of results that the API chooses to return, and the order in which they are returned.
     * The geocoder interprets abbreviations differently depending on language, such as the abbreviations for street types,
     * or synonyms that may be valid in one language but not in another. For example, utca and tér are synonyms for street in Hungarian.
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * The language in which to return results.
     *
     * @link https://developers.google.com/maps/faq#languagesupport list of supported languages.
     * Google often updates the supported languages, so this list may not be exhaustive.
     *
     * The geocoder does its best to provide a street address that is readable for both the user and locals.
     * To achieve that goal, it returns street addresses in the local language, transliterated to a script readable by the user if necessary, observing the preferred language.
     * All other addresses are returned in the preferred language. Address components are all returned in the same language, which is chosen from the first component.
     *
     * If a name is not available in the preferred language, the geocoder uses the closest match.
     *
     * The preferred language has a small influence on the set of results that the API chooses to return, and the order in which they are returned.
     * The geocoder interprets abbreviations differently depending on language, such as the abbreviations for street types,
     * or synonyms that may be valid in one language but not in another. For example, utca and tér are synonyms for street in Hungarian.
     *
     * @param string $language
     * @return ReverseGeoCodeParameters
     */
    public function setLanguage(string $language)
    {
        $this->language = $language;
        return $this;
    }

    /**
     * @param float $lat
     * @param float $lng
     * @return ReverseGeoCodeParameters
     */
    public static function makeFromLatLng(float $lat, float $lng)
    {
        return new self($lat, $lng);
    }

    /**
     * A filter of one or more address types.
     * If the parameter contains multiple address types, the API returns all addresses that match any of the types.
     * A note about processing: The result_type parameter does not restrict the search to the specified address type(s).
     * Rather, the result_type acts as a post-search filter: the API fetches all results for the specified latlng,
     * then discards those results that do not match the specified address type(s).
     *
     * @link https://developers.google.com/maps/documentation/geocoding/intro#ReverseGeocoding
     *
     * @return string[]
     */
    public function getResultType(): array
    {
        return $this->resultType;
    }

    /**
     * A filter of one or more address types.
     * If the parameter contains multiple address types, the API returns all addresses that match any of the types.
     * A note about processing: The result_type parameter does not restrict the search to the specified address type(s).
     * Rather, the result_type acts as a post-search filter: the API fetches all results for the specified latlng,
     * then discards those results that do not match the specified address type(s).
     *
     * @link https://developers.google.com/maps/documentation/geocoding/intro#ReverseGeocoding
     *
     * @param string[] $resultType
     * @return $this
     */
    public function setResultType(array $resultType)
    {
        $this->resultType = $resultType;
        return $this;
    }

    /**
     * A filter of one or more location types.
     * If the parameter contains multiple location types, the API returns all addresses that match any of the types.
     * A note about processing: The location_type parameter does not restrict the search to the specified location type(s).
     * Rather, the location_type acts as a post-search filter: the API fetches all results for the specified latlng,
     * then discards those results that do not match the specified location type(s).
     * The following values are supported:
     *  "ROOFTOP" returns only the addresses for which Google has location information accurate down to street address precision.
     *  "RANGE_INTERPOLATED" returns only the addresses that reflect an approximation (usually on a road) interpolated between two precise points (such as intersections).
     *      An interpolated range generally indicates that rooftop geocodes are unavailable for a street address.
     *  "GEOMETRIC_CENTER" returns only geometric centers of a location such as a polyline (for example, a street) or polygon (region).
     *  "APPROXIMATE" returns only the addresses that are characterized as approximate.
     *
     * @var string[]
     * @return array
     */
    public function getLocationType(): array
    {
        return $this->locationType;
    }

    /**
     * A filter of one or more location types.
     * If the parameter contains multiple location types, the API returns all addresses that match any of the types.
     * A note about processing: The location_type parameter does not restrict the search to the specified location type(s).
     * Rather, the location_type acts as a post-search filter: the API fetches all results for the specified latlng,
     * then discards those results that do not match the specified location type(s).
     * The following values are supported:
     *  "ROOFTOP" returns only the addresses for which Google has location information accurate down to street address precision.
     *  "RANGE_INTERPOLATED" returns only the addresses that reflect an approximation (usually on a road) interpolated between two precise points (such as intersections).
     *      An interpolated range generally indicates that rooftop geocodes are unavailable for a street address.
     *  "GEOMETRIC_CENTER" returns only geometric centers of a location such as a polyline (for example, a street) or polygon (region).
     *  "APPROXIMATE" returns only the addresses that are characterized as approximate.
     *
     * @param string[] $locationType
     * @return $this
     */
    public function setLocationType(array $locationType)
    {
        $this->locationType = $locationType;
        return $this;
    }
}