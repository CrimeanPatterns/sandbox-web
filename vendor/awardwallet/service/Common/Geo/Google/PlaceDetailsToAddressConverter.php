<?php


namespace AwardWallet\Common\Geo\Google;


use AwardWallet\Common\Itineraries\Address;
use Psr\Log\LoggerInterface;

class PlaceDetailsToAddressConverter
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * GooglePlaceDetailsToAddressConverter constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param PlaceDetails $placeDetails
     * @return Address
     */
    public function convert(PlaceDetails $placeDetails)
    {
        $address = new Address($this->logger);
        $address->addressLine = $placeDetails->getAddressLine();
        $address->text = $placeDetails->getFormattedAddress();
        $address->timezone = $placeDetails->getUtcOffset() * 60;
        $address->lng = $placeDetails->getLongitude();
        $address->lat = $placeDetails->getLatitude();
        $address->postalCode = $placeDetails->getPostalCode();
        $address->countryName = $placeDetails->getCountry();
        $address->stateName = $placeDetails->getState();
        $address->city = $placeDetails->getCity();
        return $address;
    }
}