<?php


namespace AwardWallet\Common\Geo\Google;


class PlaceAutocompleteParameters extends Parameters
{
    const ENTIRE_WORLD = 20000000; //radius in meters

    /**
     * Instructs the Place Autocomplete service to return only geocoding results, rather than business results.
     * Generally, you use this request to disambiguate results where the location specified may be indeterminate.
     */
    const TYPE_GEOCODE = 'geocode';

    /**
     * Instructs the Place Autocomplete service to return only geocoding results with a precise address.
     * Generally, you use this request when you know the user will be looking for a fully specified address.
     */
    const TYPE_ADDRESS = 'address';

    /**
     * Instructs the Place Autocomplete service to return only business results.
     */
    const TYPE_ESTABLISHMENT = 'establishment';

    /**
     * Instructs the Places service to return any result matching the following types:
     * locality
     * sublocality
     * postal_code
     * country
     * administrative_area_level_1
     * administrative_area_level_2
     */
    const TYPE_REGIONS = '(regions)';

    /**
     * Instructs the Places service to return results that match locality or administrative_area_level_3.
     */
    const TYPE_CITIES = '(cities)';

    /**
     * The text string on which to search.
     * The Place Autocomplete service will return candidate matches based on this string and order results based on their perceived relevance.
     *
     * @var string
     */
    private $input;

    /**
     * The position, in the input term, of the last character that the service uses to match predictions.
     * For example, if the input is 'Google' and the offset is 3, the service will match on 'Goo'.
     * The string determined by the offset is matched against the first word in the input term only.
     * For example, if the input term is 'Google abc' and the offset is 3, the service will attempt to match against 'Goo abc'.
     * If no offset is supplied, the service will use the whole term.
     * The offset should generally be set to the position of the text caret.
     *
     * @var int|null
     */
    private $offset;

    /**
     * The point around which you wish to retrieve place information.
     *
     * @var LatLng|null
     */
    private $location;

    /**
     * The distance (in meters) within which to return place results.
     * Note that setting a radius biases results to the indicated area, but may not fully restrict results to the specified area.
     * @link https://developers.google.com/places/web-service/autocomplete?hl=en#location_biasing
     *
     * @var int|null
     */
    private $radius;

    /**
     * The language code, indicating in which language the results should be returned, if possible.
     * Searches are also biased to the selected language; results in the selected language may be given a higher ranking.
     * See the list of supported languages (@link https://developers.google.com/maps/faq#languagesupport) and their codes.
     * Note that google often update supported languages so this list may not be exhaustive.
     *
     * @var string|null
     */
    private $language;

    /**
     * The types of place results to return.
     * See Place Types (@link https://developers.google.com/places/web-service/autocomplete?hl=en#place_types).
     * If no type is specified, all types will be returned.
     *
     * @var string[]|null
     */
    private $types;

    /**
     * A grouping of places to which you would like to restrict your results.
     * Currently, you can use components to filter by up to 5 countries.
     * Countries must be passed as a two character, ISO 3166-1 Alpha-2 compatible country code.
     * For example: ['country' => 'us', 'country' => 'ru']
     *
     * @var array|null
     */
    private $components;

    /**
     * Returns only those places that are strictly within the region defined by location and radius.
     * This is a restriction, rather than a bias, meaning that results outside this region will not be returned even if they match the user input.
     *
     * @var bool|null
     */
    private $strictBounds;

    /**
     * PlaceAutocompleteParameters constructor.
     * @param string $input
     */
    private function __construct(string $input) {

        $this->input = $input;
    }

    /**
     * The text string on which to search.
     * The Place Autocomplete service will return candidate matches based on this string and order results based on their perceived relevance.
     *
     * @return string
     */
    public function getInput(): string
    {
        return $this->input;
    }

    /**
     * The position, in the input term, of the last character that the service uses to match predictions.
     * For example, if the input is 'Google' and the offset is 3, the service will match on 'Goo'.
     * The string determined by the offset is matched against the first word in the input term only.
     * For example, if the input term is 'Google abc' and the offset is 3, the service will attempt to match against 'Goo abc'.
     * If no offset is supplied, the service will use the whole term.
     * The offset should generally be set to the position of the text caret.
     *
     * @return int|null
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * The position, in the input term, of the last character that the service uses to match predictions.
     * For example, if the input is 'Google' and the offset is 3, the service will match on 'Goo'.
     * The string determined by the offset is matched against the first word in the input term only.
     * For example, if the input term is 'Google abc' and the offset is 3, the service will attempt to match against 'Goo abc'.
     * If no offset is supplied, the service will use the whole term.
     * The offset should generally be set to the position of the text caret.
     *
     * @param int $offset
     */
    public function setOffset(int $offset)
    {
        $this->offset = $offset;
    }

    /**
     * The point around which you wish to retrieve place information.
     *
     * @return LatLng|null
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * The point around which you wish to retrieve place information.
     *
     * @param LatLng $location
     */
    public function setLocation(LatLng $location)
    {
        $this->location = $location;
    }

    /**
     * The distance (in meters) within which to return place results.
     * Note that setting a radius biases results to the indicated area, but may not fully restrict results to the specified area.
     * @link https://developers.google.com/places/web-service/autocomplete?hl=en#location_biasing
     *
     * @return int|null
     */
    public function getRadius()
    {
        return $this->radius;
    }

    /**
     * The distance (in meters) within which to return place results.
     * Note that setting a radius biases results to the indicated area, but may not fully restrict results to the specified area.
     * @link https://developers.google.com/places/web-service/autocomplete?hl=en#location_biasing
     *
     * @param int $radius
     */
    public function setRadius(int $radius)
    {
        $this->radius = $radius;
    }

    /**
     * The language code, indicating in which language the results should be returned, if possible.
     * Searches are also biased to the selected language; results in the selected language may be given a higher ranking.
     * See the list of supported languages (@link https://developers.google.com/maps/faq#languagesupport) and their codes.
     * Note that google often update supported languages so this list may not be exhaustive.
     *
     * @return null|string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * The language code, indicating in which language the results should be returned, if possible.
     * Searches are also biased to the selected language; results in the selected language may be given a higher ranking.
     * See the list of supported languages (@link https://developers.google.com/maps/faq#languagesupport) and their codes.
     * Note that google often update supported languages so this list may not be exhaustive.
     *
     * @param string $language
     */
    public function setLanguage(string $language)
    {
        $this->language = $language;
    }

    /**
     * The types of place results to return.
     * See Place Types (@link https://developers.google.com/places/web-service/autocomplete?hl=en#place_types).
     * If no type is specified, all types will be returned.
     *
     * @return null|string[]
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * The types of place results to return.
     * See Place Types (@link https://developers.google.com/places/web-service/autocomplete?hl=en#place_types).
     * If no type is specified, all types will be returned.
     *
     * @param string[] $types
     */
    public function setTypes(array $types)
    {
        $this->types = $types;
    }

    /**
     * A grouping of places to which you would like to restrict your results.
     * Currently, you can use components to filter by up to 5 countries.
     * Countries must be passed as a two character, ISO 3166-1 Alpha-2 compatible country code.
     * For example: ['country' => 'us', 'country' => 'ru']
     *
     * @return array|null
     */
    public function getComponents()
    {
        return $this->components;
    }

    /**
     * A grouping of places to which you would like to restrict your results.
     * Currently, you can use components to filter by up to 5 countries.
     * Countries must be passed as a two character, ISO 3166-1 Alpha-2 compatible country code.
     * For example: ['country' => 'us', 'country' => 'ru']
     *
     * @param array $components
     */
    public function setComponents(array $components)
    {
        foreach ($components as $component) {
            if (!is_string($component)) {
                throw new \InvalidArgumentException("Invalid components format");
            }
        }
        $this->components = $components;
    }

    /**
     * Returns only those places that are strictly within the region defined by location and radius.
     * This is a restriction, rather than a bias, meaning that results outside this region will not be returned even if they match the user input.
     *
     * @return true|null
     */
    public function getStrictBounds()
    {
        return $this->strictBounds;
    }

    /**
     * Returns only those places that are strictly within the region defined by location and radius.
     * This is a restriction, rather than a bias, meaning that results outside this region will not be returned even if they match the user input.
     */
    public function setStrictBounds()
    {
        $this->strictBounds = true;
    }

    /**
     * @param string $input
     * @return PlaceAutocompleteParameters
     */
    public static function makeFromInput(string $input): PlaceAutocompleteParameters
    {
        if ('' === $input) {
            throw new \InvalidArgumentException("Input cannot be empty");
        }
        return new PlaceAutocompleteParameters($input);
    }

    /**
     * Should only contain string representation of set parameters and nulls otherwise.
     *
     * @return array
     */
    protected function getAllParametersAsArray(): array
    {
        //If no radius or location specified then remove any bias
        if (null === $this->location && null === $this->radius) {
            $location = new LatLng(0, 0);
            $radius = self::ENTIRE_WORLD;
        } else {
            $location = $this->location;
            $radius = $this->radius;
        }
        if (null !== $this->components) {
            $componentsArray = [];
            foreach ($this->components as $key => $component) {
                $componentsArray = $key . '=' . $component;
            }
            $componentsString = implode('|', $componentsArray);
        } else {
            $componentsString = null;
        }
        return [
            'input' => $this->input,
            'offset' => $this->offset,
            'location' => $location,
            'radius' => $radius,
            'language' => $this->language,
            'types' => null !== $this->types ? implode(',', $this->types) : null,
            'components' => $componentsString,
            'strictbounds' => $this->strictBounds
        ];
    }
}