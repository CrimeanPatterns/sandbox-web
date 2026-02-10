<?php


namespace AwardWallet\Common\API\Converter\V2;


use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Schema\Itineraries\Itinerary as OutputItinerary;
use AwardWallet\Schema\Itineraries\Person;
use AwardWallet\Schema\Parser\Common\Itinerary as ParsedItinerary;

class Parking extends Itinerary
{

    protected function initItinerary(ParsedItinerary $parsed, Extra $extra): OutputItinerary
    {
        return new \AwardWallet\Schema\Itineraries\Parking();
    }

    protected function convertItinerary(ParsedItinerary $parsed, OutputItinerary $it, Extra $extra): OutputItinerary
    {
        /** @var \AwardWallet\Schema\Parser\Common\Parking $parsed */
        /** @var \AwardWallet\Schema\Itineraries\Parking $it */
        $it->confirmationNumbers = Util::confirmations($parsed);
        if ($parsed->getLocation())
            $it->locationName = $parsed->getLocation();
        if ($parsed->getStartDate())
            $it->startDateTime = Util::date($parsed->getStartDate());
        if ($parsed->getEndDate())
            $it->endDateTime = Util::date($parsed->getEndDate());
        if ($parsed->getAddress()) {
            $it->address = Util::emptyAddress([$parsed->getAddress()]);
            if ($geo = $extra->data->getGeo($parsed->getAddress()))
                $it->address = Util::address($parsed->getAddress(), $geo, $it->startDateTime ?? $it->endDateTime);
        }
        if ($parsed->getSpot())
            $it->spotNumber = $parsed->getSpot();
        if ($parsed->getPlate())
            $it->licensePlate = $parsed->getPlate();
        if ($parsed->getPhone())
            $it->phone = $parsed->getPhone();
        if ($parsed->getOpeningHours())
            $it->openingHours = implode('|', $parsed->getOpeningHours());
        if (count($parsed->getTravellers()) > 0) {
            $t = $parsed->getTravellers()[0];
            $it->owner = new Person();
            $it->owner->name = $t[0];
            $it->owner->full = $t[1] ?? $parsed->getAreNamesFull();
        }
        if ($parsed->getRateType())
            $it->rateType = $parsed->getRateType();
        if ($parsed->getCarDescription())
            $it->carDescription = $parsed->getCarDescription();
        return $it;
    }
}