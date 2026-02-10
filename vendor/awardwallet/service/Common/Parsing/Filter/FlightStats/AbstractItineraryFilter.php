<?php


namespace AwardWallet\Common\Parsing\Filter\FlightStats;


use AwardWallet\Common\Itineraries\Itinerary;
use AwardWallet\Common\Parsing\Filter\ItineraryFilterInterface;

abstract class AbstractItineraryFilter implements ItineraryFilterInterface
{
    /**
     *
     *
     * @param Itinerary $itinerary
     * @param null $providerCode
     * @return Itinerary
     */
    abstract public function filter(Itinerary $itinerary, $providerCode = null);

    /**
     * @param Itinerary $itinerary
     * @return bool
     */
    protected function isApplicable(Itinerary $itinerary)
    {
        $applicable = false;
        foreach ($this->getApplicableEntities() as $applicableEntity) {
            if($itinerary instanceof $applicableEntity) {
                $applicable = true;
                break;
            }
        }

        return $applicable;
    }

    /**
     * Filly qualified class names of applicable entities
     *
     * @return string[]
     */
    abstract public function getApplicableEntities();
}