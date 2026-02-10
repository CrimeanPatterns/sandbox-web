<?php


namespace AwardWallet\Common\Parsing\Filter;


use AwardWallet\Common\Itineraries\Itinerary;

interface ItineraryFilterInterface
{
    /**
     *
     *
     * @param Itinerary $itinerary
     * @param null $providerCode
     * @return Itinerary
     */
    public function filter(Itinerary $itinerary, $providerCode = null);
}