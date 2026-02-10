<?php


namespace AwardWallet\Common\API\Converter\V2;


use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Schema\Itineraries\Itinerary as OutputItinerary;
use AwardWallet\Schema\Parser\Common\Itinerary as ParsedItinerary;

class Event extends Itinerary {

    protected function initItinerary(ParsedItinerary $parsed, Extra $extra): OutputItinerary
    {
        return new \AwardWallet\Schema\Itineraries\Event();
    }

    protected function convertItinerary(ParsedItinerary $parsed, OutputItinerary $it, Extra $extra): OutputItinerary
    {
        /** @var \AwardWallet\Schema\Parser\Common\Event $parsed */
        /** @var \AwardWallet\Schema\Itineraries\Event $it */
        $it->confirmationNumbers = Util::confirmations($parsed);
        $it->startDateTime = Util::date($parsed->getStartDate());
        $it->endDateTime = Util::date($parsed->getEndDate());
        $it->address = Util::emptyAddress([$parsed->getAddress()]);
        if ($parsed->getAddress() && $geo = $extra->data->getGeo($parsed->getAddress()))
            $it->address = Util::address($parsed->getAddress(), $geo, $it->startDateTime);
        $it->eventName = $parsed->getName();
        $it->eventType = $parsed->getEventType();
        $it->phone = $parsed->getPhone();
        $it->fax = $parsed->getFax();
        $it->guestCount = $parsed->getGuestCount();
        $it->kidsCount = $parsed->getKidsCount();
        $it->guests = Util::names($parsed->getTravellers(), $parsed->getAreNamesFull(), null);
        if ($parsed->getSeats())
            $it->seats = array_values($parsed->getSeats());
        return $it;
    }
}