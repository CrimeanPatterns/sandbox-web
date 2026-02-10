<?php


namespace AwardWallet\Common\Geo\Google\Traits;


trait HasAddressComponents
{
    /**
     * Human-readable address of this place.
     * Often this address is equivalent to the "postal address", which sometimes differs from country to country.
     *
     * @JMS\SerializedName("formatted_address")
     * @JMS\Type("string")
     *
     * @var string|null
     */
    protected $formattedAddress;

    /**
     * Array of separate address components used to compose a given address.
     * For example, the address "111 8th Avenue, New York, NY" contains separate address components for
     * "111" (the street number, "8th Avenue" (the route), "New York" (the city) and "NY" (the US state).
     *
     * @JMS\SerializedName("address_components")
     * @JMS\Type("array<AwardWallet\Common\Geo\Google\AddressComponent>")
     *
     * @var AddressComponent[]|null
     */
    private $addressComponents;

    /**
     * @return null|string
     */
    public function getFormattedAddress()
    {
        return $this->formattedAddress;
    }

    /**
     * @return AddressComponent[]|null
     */
    public function getAddressComponents()
    {
        return $this->addressComponents;
    }

    /**
     * @param array $types
     * @return AddressComponent[]
     */
    private function getFromAddressComponentsByTypes(array $types)
    {
        if (null === $this->addressComponents) {
            return [];
        }
        $returnComponents = [];
        foreach ($this->addressComponents as $addressComponent) {
            foreach ($addressComponent->getTypes() as $type) {
                if (in_array($type, $types)) {
                    $returnComponents[] = $addressComponent;
                    break;
                }
            }
        }
        return $returnComponents;
    }

    /**
     * @return AddressComponent|null
     */
    private function getCityComponent()
    {
        $components = $this->getFromAddressComponentsByTypes([
            'locality',
            'administrative_area_level_3',
            'administrative_area_level_2'
        ]);
        if (empty($components)) {
            return null;
        }
        return $components[0];
    }

    /**
     * @return null|string
     */
    public function getCity()
    {
        $cityComponent = $this->getCityComponent();
        if (null === $cityComponent) {
            return null;
        }
        return $cityComponent->getLongName();
    }

    /**
     * @return null|string
     */
    public function getCityShort()
    {
        $cityComponent = $this->getCityComponent();
        if (null === $cityComponent) {
            return null;
        }
        return $cityComponent->getShortName();
    }

    /**
     * @return AddressComponent|null
     */
    private function getCountryComponent()
    {
        $components = $this->getFromAddressComponentsByTypes([
            'country'
        ]);
        if (empty($components)) {
            return null;
        }
        return $components[0];
    }

    /**
     * @return null|string
     */
    public function getCountry()
    {
        $countryComponent = $this->getCountryComponent();
        if (null === $countryComponent) {
            return null;
        }
        return $this->getCountryComponent()->getLongName();
    }

    /**
     * @return null|string
     */
    public function getCountryShort()
    {
        $countryComponent = $this->getCountryComponent();
        if (null === $countryComponent) {
            return null;
        }
        return $this->getCountryComponent()->getShortName();
    }

    /**
     * @return AddressComponent|null
     */
    private function getStateComponent()
    {
        $components = $this->getFromAddressComponentsByTypes([
            'administrative_area_level_1',
            'administrative_area_level_2'
        ]);
        if (empty($components)) {
            return null;
        }
        return end($components);
    }

    /**
     * @return null|string
     */
    public function getState()
    {
        $stateComponent = $this->getStateComponent();
        if (null === $stateComponent) {
            return null;
        }
        return $stateComponent->getLongName();
    }

    /**
     * @return null|string
     */
    public function getStateShort()
    {
        $stateComponent = $this->getStateComponent();
        if (null === $stateComponent) {
            return null;
        }
        return $stateComponent->getShortName();
    }

    /**
     * @return AddressComponent|null
     */
    private function getPostalCodeComponent()
    {
        $components = $this->getFromAddressComponentsByTypes([
            'postal_code'
        ]);
        if (empty($components)) {
            return null;
        }
        return $components[0];
    }

    /**
     * @return null|string
     */
    public function getPostalCode()
    {
        $postalCodeComponent = $this->getPostalCodeComponent();
        if (null === $postalCodeComponent) {
            return null;
        }
        return $postalCodeComponent->getLongName();
    }

    /**
     * @return null|string
     */
    public function getPostalCodeShort()
    {
        $postalCodeComponent = $this->getPostalCodeComponent();
        if (null === $postalCodeComponent) {
            return null;
        }
        return $postalCodeComponent->getShortName();
    }

    /**
     * Local address without city name. E.g. "New Arbat Avenue, 11"
     *
     * @return null|string
     */
    public function getAddressLine()
    {
        if (null === $this->formattedAddress) {
            return null;
        }
        $addressLine = $this->formattedAddress;
        foreach ([$this->getCity(), $this->getState(), $this->getCountry()] as $value) {
            if (empty($value)) {
                continue;
            }
            if (false !== ($pos = mb_strpos($this->formattedAddress, $value))) {
                $addressLine = mb_substr($addressLine, 0, $pos);
            }
        }
        return trim($addressLine, ', ');
    }
}