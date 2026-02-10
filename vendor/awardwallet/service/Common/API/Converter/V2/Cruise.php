<?php


namespace AwardWallet\Common\API\Converter\V2;


use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Schema\Itineraries\CruiseDetails;
use AwardWallet\Schema\Itineraries\CruiseSegment;
use AwardWallet\Schema\Itineraries\Itinerary as OutputItinerary;
use AwardWallet\Schema\Itineraries\TransportLocation;
use AwardWallet\Schema\Parser\Common\Itinerary as ParsedItinerary;

class Cruise extends Itinerary
{

    protected function initItinerary(ParsedItinerary $parsed, Extra $extra): OutputItinerary
    {
        return new \AwardWallet\Schema\Itineraries\Cruise();
    }

    protected function convertItinerary(ParsedItinerary $parsed, OutputItinerary $it, Extra $extra): OutputItinerary
    {
        /** @var \AwardWallet\Schema\Parser\Common\Cruise $parsed */
        /** @var \AwardWallet\Schema\Itineraries\Cruise $it */
        $it->travelers = Util::names($parsed->getTravellers(), $parsed->getAreNamesFull(), null);
        $it->confirmationNumbers = Util::confirmations($parsed);
        if (count(array_filter([
                $parsed->getDescription(),
                $parsed->getDeck(),
                $parsed->getClass(),
                $parsed->getRoom(),
                $parsed->getShip(),
                $parsed->getShipCode(),
                $parsed->getVoyageNumber()
            ])) > 0) {
            $c = new CruiseDetails();
            $c->description = $parsed->getDescription();
            $c->deck = $parsed->getDeck();
            $c->class = $parsed->getClass();
            $c->room = $parsed->getRoom();
            $c->ship = $parsed->getShip();
            $c->shipCode = $parsed->getShipCode();
            $c->voyageNumber = $parsed->getVoyageNumber();
            $it->cruiseDetails = $c;
        }
        $it->segments = [];
        $depPort = $arrPort = $depDate = $arrDate = $depCode = $arrCode = null;
        foreach($parsed->getSegments() as $s) {
            if (($s->getAboard() || $s->getAshore()) && ($s->getName() && (strcasecmp($s->getName(), 'At Sea') !== 0) || $s->getCode())) {
                $arrPort = $s->getName();
                $arrCode = $s->getCode();
                $arrDate = $s->getAshore();
                if ($depPort && $arrPort && $depDate && $arrDate)
                    $it->segments[] = $this->convertSegment($depPort, $depDate, $depCode, $arrPort, $arrDate, $arrCode, $extra);
                $depPort = $s->getName();
                $depCode = $s->getCode();
                $depDate = $s->getAboard();
            }
        }
        return $it;
    }

    protected function convertSegment($depPort, $depDate, $depCode, $arrPort, $arrDate, $arrCode, Extra $extra): CruiseSegment
    {
        $seg = new CruiseSegment();
        $seg->departure = $this->convertPoint($depPort, $depCode, $depDate, $extra);
        $seg->arrival = $this->convertPoint($arrPort, $arrCode, $arrDate, $extra);
        return $seg;
    }

    protected function convertPoint($name, $code, $date, Extra $extra) : TransportLocation {
        $point = new TransportLocation();
        $point->localDateTime = Util::date($date);
        $point->address = Util::emptyAddress([$name, $code]);
        $point->name = $name ?? $code;
        if ($geo = $extra->data->getGeo($name)) {
            $point->address = Util::address($name, $geo, $point->localDateTime);
            if ($geo->name)
                $point->name = $geo->name;
        }
        $point->stationCode = $code;
        return $point;
    }
}