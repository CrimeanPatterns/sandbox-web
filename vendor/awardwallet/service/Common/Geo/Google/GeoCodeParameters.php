<?php


namespace AwardWallet\Common\Geo\Google;


class GeoCodeParameters extends Parameters
{
    /**
     * The street address that you want to geocode, in the format used by the national postal service of the country concerned.
     * Additional address elements such as business names and unit, suite or floor numbers should be avoided.
     * Please @link https://developers.google.com/maps/faq#geocoder_queryformat for additional guidance.
     *
     * @var string|null
     */
    private $address;

    /**
     * A component filter for which you wish to obtain a geocode.
     * Will fully restrict the results from the geocoder. For more information see https://developers.google.com/maps/documentation/geocoding/intro#ComponentFiltering
     *
     * @var Components|null
     */
    private $components;

    /**
     * The bounding box of the viewport within which to bias geocode results more prominently.
     * This parameter will only influence, not fully restrict, results from the geocoder.
     * @link https://developers.google.com/maps/documentation/geocoding/intro#Viewports
     *
     * @var ViewPort|null
     */
    private $bounds;

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
     * @var string
     */
    private $language = 'en';

    /**
     * The region code, specified as a ccTLD ("top-level domain") two-character value.
     * This parameter will only influence, not fully restrict, results from the geocoder.
     * For more information @link https://developers.google.com/maps/documentation/geocoding/intro#RegionCodes
     *
     * @var string|null
     */
    private $region;

    /**
     * @return array
     */
    protected function getAllParametersAsArray(): array
    {
        return [
            'address'    => $this->address,
            'components' => $this->components,
            'bounds'     => $this->bounds,
            'language'   => $this->language,
            'region'     => $this->region
        ];
    }

    /**
     * GeoCodeParameters constructor.
     * @param string|null $address
     * @param Components|null $components
     */
    private function __construct(string $address = null, Components $components = null)
    {
        if (null === $address && null === $components) {
            throw new \InvalidArgumentException("Either address or components should be specified");
        }

        $this->address = $address;
        $this->components = $components;
    }

    /**
     * The street address that you want to geocode, in the format used by the national postal service of the country concerned.
     *
     * @return string|null
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * The street address that you want to geocode, in the format used by the national postal service of the country concerned.
     * Additional address elements such as business names and unit, suite or floor numbers should be avoided.
     * Please @link https://developers.google.com/maps/faq#geocoder_queryformat for additional guidance.
     *
     * @param string $address
     * @return GeoCodeParameters
     */
    public function setAddress(string $address): GeoCodeParameters
    {
        $this->address = $address;
        return $this;
    }

    /**
     * A component filter for which you wish to obtain a geocode.
     * Will fully restrict the results from the geocoder. For more information see https://developers.google.com/maps/documentation/geocoding/intro#ComponentFiltering
     *
     * @return Components|null
     */
    public function getComponents()
    {
        return $this->components;
    }

    /**
     * A component filter for which you wish to obtain a geocode.
     * Will fully restrict the results from the geocoder. For more information see https://developers.google.com/maps/documentation/geocoding/intro#ComponentFiltering
     *
     * @param Components[] $components
     * @return GeoCodeParameters
     */
    public function setComponents(array $components): GeoCodeParameters
    {
        $this->components = $components;
        return $this;
    }

    /**
     * The bounding box of the viewport within which to bias geocode results more prominently.
     * This parameter will only influence, not fully restrict, results from the geocoder.
     * @link https://developers.google.com/maps/documentation/geocoding/intro#Viewports
     *
     * @return ViewPort|null
     */
    public function getBounds()
    {
        return $this->bounds;
    }

    /**
     * The bounding box of the viewport within which to bias geocode results more prominently.
     * This parameter will only influence, not fully restrict, results from the geocoder.
     * @link https://developers.google.com/maps/documentation/geocoding/intro#Viewports
     *
     * @param ViewPort $bounds
     * @return GeoCodeParameters
     */
    public function setBounds(ViewPort $bounds): GeoCodeParameters
    {
        $this->bounds = $bounds;
        return $this;
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
     * @return GeoCodeParameters
     */
    public function setLanguage(string $language)
    {
        $this->language = $language;
        return $this;
    }

    /**
     * The region code, specified as a ccTLD ("top-level domain") two-character value.
     * This parameter will only influence, not fully restrict, results from the geocoder.
     * For more information @link https://developers.google.com/maps/documentation/geocoding/intro#RegionCodes
     *
     * @return string|null
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * The region code, specified as a ccTLD ("top-level domain") two-character value.
     * This parameter will only influence, not fully restrict, results from the geocoder.
     * For more information @link https://developers.google.com/maps/documentation/geocoding/intro#RegionCodes
     *
     * @param string $region
     * @return GeoCodeParameters
     */
    public function setRegion(string $region): GeoCodeParameters
    {
        $this->region = $region;
        return $this;
    }

    /**
     * @param string $address
     * @return GeoCodeParameters
     */
    public static function makeFromAddress(string $address)
    {
        if (empty($address)) {
            throw new \InvalidArgumentException("Address cannot be empty");
        }
        return new GeoCodeParameters($address);
    }

    /**
     * @param Components $components
     * @return GeoCodeParameters
     */
    public static function makeFromComponents(Components $components)
    {
        if (empty((string) $components)) {
            throw new \InvalidArgumentException("At least 1 component must be set");
        }
        return new GeoCodeParameters(null, $components);
    }
}