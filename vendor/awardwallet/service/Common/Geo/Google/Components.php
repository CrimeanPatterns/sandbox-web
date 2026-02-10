<?php


namespace AwardWallet\Common\Geo\Google;


class Components
{
    /**
     * Matches long or short name of a route.
     *
     * @var string|null
     */
    private $route;

    /**
     * Matches against both locality and sublocality types.
     *
     * @var string|null
     */
    private $locality;

    /**
     * Matches all the administrative_area levels.
     *
     * @var string|null
     */
    private $administrativeArea;

    /**
     * Matches postal_code and postal_code_prefix.
     *
     * @var string|null
     */
    private $postalCode;

    /**
     * Matches a country name or a two letter ISO 3166-1 country code.
     *
     * @var string|null
     */
    private $country;

    /**
     * Matches long or short name of a route.
     *
     * @return string|null
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Matches long or short name of a route.
     *
     * @param string $route
     * @return Components
     */
    public function setRoute(string $route): Components
    {
        $this->route = $route;
        return $this;
    }

    /**
     * Matches against both locality and sublocality types.
     *
     * @return string|null
     */
    public function getLocality()
    {
        return $this->locality;
    }

    /**
     * Matches against both locality and sublocality types.
     *
     * @param string $locality
     * @return Components
     */
    public function setLocality(string $locality): Components
    {
        $this->locality = $locality;
        return $this;
    }

    /**
     * Matches all the administrative_area levels.
     *
     * @return string|null
     */
    public function getAdministrativeArea()
    {
        return $this->administrativeArea;
    }

    /**
     * Matches all the administrative_area levels.
     *
     * @param string $administrativeArea
     * @return Components
     */
    public function setAdministrativeArea(string $administrativeArea): Components
    {
        $this->administrativeArea = $administrativeArea;
        return $this;
    }

    /**
     * Matches postal_code and postal_code_prefix.
     *
     * @return string|null
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * Matches postal_code and postal_code_prefix.
     *
     * @param string $postalCode
     * @return Components
     */
    public function setPostalCode(string $postalCode): Components
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    /**
     * Matches a country name or a two letter ISO 3166-1 country code.
     *
     * @return string|null
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Matches a country name or a two letter ISO 3166-1 country code.
     *
     * @param string $country
     * @return Components
     */
    public function setCountry(string $country): Components
    {
        $this->country = $country;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return implode('|', $this->toArray());
    }

    /**
     * @return array
     */
    private function toArray()
    {
        $array = [
            'route' => $this->route,
            'locality' => $this->locality,
            'administrative_area' => $this->administrativeArea,
            'postal_code' => $this->postalCode,
            'country' => $this->country
        ];
        return array_filter($array, function ($componentValue) {
            return null !== $componentValue;
        });
    }
}