<?php

namespace AwardWallet\Common\Geo\Google;

class GeoResultConverter
{
    
    public static function decodeGoogleGeoResult(GeoTag $result) : array
    {
        $arDetailedAddress = array(
            'Formatted' => (string)$result->getFormattedAddress(),
        );
        $street = '';
        $number = '';
        $jnumber = '';
        $establishment = '';

        $data = array();

        if (!empty($result->getFormattedAddress()) && !empty($result->getAddressComponents())) {
            $citySublocality = false;
            foreach ($result->getAddressComponents() as $component) {
                $name = null;
                if (!empty($component->getLongName()))
                    $name = (string)$component->getLongName();
                elseif (!empty($component->getShortName()))
                    $name = (string)$component->getShortName();

                if (isset($name) && !empty($component->getTypes())) {
                    foreach ($component->getTypes() as $type) {
                        if ((string)$type != 'political')
                            $data[(string)$type] = $name;

                        switch ((string)$type) {
                            case 'political':
                                break;
                            case 'country':
                                $arDetailedAddress['Country'] = $name;
                                if(!empty($component->getShortName()) && $component->getShortName() != $name)
                                    $arDetailedAddress['CountryCode'] = $component->getShortName();
                                break;
                            case 'postal_code':
                                $arDetailedAddress['PostalCode'] = $name;
                                break;
                            case 'administrative_area_level_1':
                                $arDetailedAddress['State'] = $name;
                                if(!empty($component->getShortName()) && $component->getShortName() != $name)
                                    $arDetailedAddress['StateCode'] = $component->getShortName();
                                break;
                            case 'locality':
                                $arDetailedAddress['City'] = $name;
                                $citySublocality = false;
                                break;
                            case 'sublocality':
                                if (empty($arDetailedAddress['City'])) {
                                    $arDetailedAddress['City'] = $name;
                                    $citySublocality = true;
                                }
                                break;
                            case 'sublocality_level_4':
                            case 'sublocality_level_3':
                            case 'sublocality_level_2':
                                $jnumber = "$name-$jnumber";
                                break;
                            case 'sublocality_level_1':
                                if (empty($street))
                                    $street = $name;
                                break;
                            case 'administrative_area_level_3':
                            case 'administrative_area_level_2':
                                if (empty($arDetailedAddress['City']) || $citySublocality) {
                                    $arDetailedAddress['City'] = $name;
                                    $citySublocality = false;
                                }
                                if (empty($arDetailedAddress['State']))
                                    $arDetailedAddress['State'] = $name;
                                break;
                            case 'street_number':
                                $number = $name;
                                break;
                            case 'route':
                                $street = $name;
                                break;
                            case 'establishment':
                                $establishment = $name;
                                break;
                        }
                    }
                }
            }
            if (empty($number))
                $number = trim($jnumber, '-');
            $street = trim($number . ' ' . $street);
            if (empty($street))
                $street = $establishment;
            if (!empty($street))
                $arDetailedAddress['AddressLine'] = $street;
        }

        return $arDetailedAddress;
    }

}