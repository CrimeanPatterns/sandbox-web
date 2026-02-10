<?php


namespace AwardWallet\Common\Parsing\Filter\Google;


use AwardWallet\Common\Geo\Google\GoogleApi;
use AwardWallet\Common\Geo\Google\GoogleRequestFailedException;
use AwardWallet\Common\Geo\Google\PlaceDetailsParameters;
use AwardWallet\Common\Geo\Google\PlaceDetailsToAddressConverter;
use AwardWallet\Common\Geo\Google\PlaceTextSearchParameters;
use AwardWallet\Common\Itineraries\HotelReservation;
use AwardWallet\Common\Itineraries\Itinerary;
use AwardWallet\Common\Parsing\Filter\FlightStats\AbstractItineraryFilter;
use Psr\Log\LoggerInterface;

class HotelAddressByNameFilter extends AbstractItineraryFilter
{
    /**
     * @var GoogleApi
     */
    private $googleApi;

    /**
     * @var PlaceDetailsToAddressConverter
     */
    private $addressConverter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    private $logComponents = [
        'components' => 'HotelAddressNameFilter'
    ];

    /**
     * HotelAddressByNameFilter constructor.
     * @param GoogleApi $googleApi
     * @param PlaceDetailsToAddressConverter $addressConverter
     * @param LoggerInterface $logger
     */
    public function __construct(GoogleApi $googleApi, PlaceDetailsToAddressConverter $addressConverter, LoggerInterface $logger)
    {
        $this->googleApi = $googleApi;
        $this->addressConverter = $addressConverter;
        $this->logger = $logger;
    }

    /**
     * @param Itinerary $itinerary
     * @param null $providerCode
     * @return void
     */
    public function filter(Itinerary $itinerary, $providerCode = null)
    {
        $this->logger->info('Start HotelAddressByNameFilter', array_merge($this->logComponents, [
            'confirmationNumber' => $itinerary->providerDetails ? $itinerary->providerDetails->confirmationNumber : 'none',
            'providerCode' => $providerCode
        ]));
        if (!$this->isApplicable($itinerary)) {
            $this->logger->info('Not an applicable type, skipping', $this->logComponents);
            return;
        }
        /** @var HotelReservation $reservation */
        $reservation = $itinerary;

        //Already have an address
        if ($reservation->address && !empty($reservation->address->text)) {
            $this->logger->info('Already have an address, skipping', $this->logComponents);
            return;
        }

        //No name to search with
        if (empty($reservation->hotelName)) {
            $this->logger->info('Have no name to search address with, skipping', $this->logComponents);
            return;
        }
        //Search for place ID
        $placeSearchParameters = PlaceTextSearchParameters::makeFromQuery($reservation->hotelName);
        $placeSearchParameters->setType('lodging');
        try {
            $this->logger->info(
                'Requesting Place Text Search Google API',
                array_merge($this->logComponents, ['parameters' => $placeSearchParameters->toArray()])
            );
            $placesSearchResponse = $this->googleApi->placeTextSearch($placeSearchParameters);
        } catch (GoogleRequestFailedException $e) {
            $this->logger->warning("Failed to perform Google Place Text search: " . $e->getMessage(), $this->logComponents);
            return;
        }
        if (empty($placesSearchResponse->getResults())) {
            $this->logger->notice('No results from Place Search Google API request, skipping', $this->logComponents);
            return;
        }
        $placeId = $placesSearchResponse->getResults()[0]->getPlaceId();
        //Get address by place ID
        $placeDetailsParameters = PlaceDetailsParameters::makeFromPlaceId($placeId);
        try {
            $this->logger->info(
                'Requesting Place Details Google API',
                array_merge($this->logComponents, ['parameters' => $placeDetailsParameters->toArray()])
            );
            $placeDetailsResponse = $this->googleApi->placeDetails($placeDetailsParameters);
        } catch (GoogleRequestFailedException $e) {
            $this->logger->warning(
                "Failed to perform Google Place Details search: " . $e->getMessage(),
                $this->logComponents
            );
            return;
        }
        if (null === $placeDetailsResponse->getResult()) {
            $this->logger->notice('No result for Place Details Google API request, skipping', $this->logComponents);
            return;
        }
        $reservation->address = $this->addressConverter->convert($placeDetailsResponse->getResult());
        $this->logger->info('Filter has been successfully applied', $this->logComponents);
    }

    /**
     * Fully qualified class names of applicable entities
     *
     * @return string[]
     */
    public function getApplicableEntities()
    {
        return [
            HotelReservation::class
        ];
    }
}