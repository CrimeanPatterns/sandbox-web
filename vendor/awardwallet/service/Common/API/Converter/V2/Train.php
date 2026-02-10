<?php


namespace AwardWallet\Common\API\Converter\V2;


use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Schema\Itineraries\Itinerary as OutputItinerary;
use AwardWallet\Schema\Itineraries\TicketLink;
use AwardWallet\Schema\Itineraries\TransportLocation;
use AwardWallet\Schema\Itineraries\Vehicle;
use AwardWallet\Schema\Parser\Common\Itinerary as ParsedItinerary;
use AwardWallet\Schema\Parser\Common\TrainSegment;

class Train extends Itinerary
{

    protected function initItinerary(ParsedItinerary $parsed, Extra $extra): OutputItinerary
    {
        return new \AwardWallet\Schema\Itineraries\Train();
    }

    protected function convertItinerary(ParsedItinerary $parsed, OutputItinerary $it, Extra $extra): OutputItinerary
    {
        /** @var \AwardWallet\Schema\Parser\Common\Train $parsed */
        /** @var \AwardWallet\Schema\Itineraries\Train $it */
        $it->travelers = Util::names($parsed->getTravellers(), $parsed->getAreNamesFull(), null);
        Util::setNumbers($it->travelers ?? [], $parsed->getAccountNumbers() ?? [], $parsed->getTicketNumbers() ?? []);
        $it->confirmationNumbers = Util::confirmations($parsed);
        if ($parsed->getTicketNumbers())
            $it->ticketNumbers = Util::numbers($parsed->getTicketNumbers(), $parsed->getAreTicketsMasked());
        $it->segments = [];
        $idx = 0;
        foreach($parsed->getSegments() as $s) {
            $it->segments[] = $this->convertSegment($s, $extra);
            if ($s->getAssignedSeats()) {
                Util::setSeats($it->travelers ?? [], $s->getAssignedSeats(), $idx);
            }
            $idx++;
        }
        return $it;
    }

    protected function convertSegment(TrainSegment $parsed, Extra $extra) {
        $seg = new \AwardWallet\Schema\Itineraries\TrainSegment();
        $seg->scheduleNumber = $parsed->getNumber();
        $seg->departure = $this->convertPoint($parsed->getDepName(), $parsed->getDepAddress(), $parsed->getDepCode(), $parsed->getDepDate(), $extra);
        $seg->arrival = $this->convertPoint($parsed->getArrName(), $parsed->getArrAddress(), $parsed->getArrCode(), $parsed->getArrDate(), $extra);
        $seg->serviceName = $parsed->getServiceName();
        if ($parsed->gettrainModel() || $parsed->gettrainType()) {
            $seg->trainInfo = new Vehicle();
            $seg->trainInfo->model = $parsed->gettrainModel();
            $seg->trainInfo->type = $parsed->gettrainType();
        }
        $seg->car = $parsed->getCarNumber();
        if ($parsed->getSeats())
            $seg->seats = array_values($parsed->getSeats());
        $seg->traveledMiles = $parsed->getMiles();
        $seg->cabin = $parsed->getCabin();
        $seg->bookingCode = $parsed->getBookingCode();
        $seg->duration = $parsed->getDuration();
        if ($parsed->getMeals()) {
            $seg->meal = implode(", ", array_unique($parsed->getMeals()));
        }
        $seg->smoking = $parsed->getSmoking();
        if (null !== $parsed->getStops())
            $seg->stops = $parsed->getStops();
        if (!empty($parsed->getLinks())) {
            $seg->ticketLinks = [];
            foreach($parsed->getLinks() as list($link, $name)) {
                $tlink = new TicketLink();
                $tlink->link = $link;
                $tlink->name = $name;
                $seg->ticketLinks[] = $tlink;
            }
        }
        return $seg;
    }

    protected function convertPoint($name, $address, $code, $date, Extra $extra) : TransportLocation {
        $point = new TransportLocation();
        $point->localDateTime = Util::date($date);
        $point->address = Util::emptyAddress([$address, $name, $code]);
        $point->name = $name ?? $address ?? $code;
        foreach([$code, $address, $name] as $key) {
            if ($key && $geo = $extra->data->getGeo($key)) {
                $point->address = Util::address($key, $geo, $point->localDateTime);
                if ($geo->name)
                    $point->name = $geo->name;
                break;
            }
        }
        $point->stationCode = $code;
        return $point;
    }
}