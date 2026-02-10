<?php

namespace AwardWallet\Common\API\Converter\V2;

use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Schema\Itineraries\Itinerary as OutputItinerary;
use AwardWallet\Schema\Itineraries\TransportLocation;
use AwardWallet\Schema\Itineraries\VehicleExt;
use AwardWallet\Schema\Parser\Common\FerrySegment;
use AwardWallet\Schema\Parser\Common\Itinerary as ParsedItinerary;

class Ferry extends Itinerary
{
    protected function initItinerary(ParsedItinerary $parsed, Extra $extra): OutputItinerary
    {
        return new \AwardWallet\Schema\Itineraries\Ferry();
    }

    protected function convertItinerary(ParsedItinerary $parsed, OutputItinerary $it, Extra $extra): OutputItinerary
    {
        /** @var \AwardWallet\Schema\Parser\Common\Ferry $parsed */
        /** @var \AwardWallet\Schema\Itineraries\Ferry $it */
        $it->travelers = Util::names($parsed->getTravellers(), $parsed->getAreNamesFull(), null);
        $it->confirmationNumbers = Util::confirmations($parsed);
        if ($parsed->getTicketNumbers()) {
            $it->ticketNumbers = Util::numbers($parsed->getTicketNumbers(), $parsed->getAreTicketsMasked());
        }
        $it->segments = [];
        foreach ($parsed->getSegments() as $s) {
            $it->segments[] = $this->convertSegment($s, $extra);
        }
        return $it;
    }

    protected function convertSegment(FerrySegment $parsed, Extra $extra)
    {
        $seg = new \AwardWallet\Schema\Itineraries\FerrySegment();
        $seg->departure = $this->convertPoint($parsed->getDepName(), $parsed->getDepAddress(), $parsed->getDepCode(),
            $parsed->getDepDate(), $extra);
        $seg->arrival = $this->convertPoint($parsed->getArrName(), $parsed->getArrAddress(), $parsed->getArrCode(),
            $parsed->getArrDate(), $extra);

        $seg->vehicles = [];
        foreach ($parsed->getVehicles() as $vehicle) {
            $new = new VehicleExt();
            $new->type = $vehicle->getType();
            $new->model = $vehicle->getModel();
            $new->length = $vehicle->getLength();
            $new->height = $vehicle->getHeight();
            $new->width = $vehicle->getWidth();
            $seg->vehicles[] = $new;
        }
        if (count($seg->vehicles) === 0) {
            $seg->vehicles = null;
        }

        $seg->trailers = [];
        foreach ($parsed->getTrailers() as $trailer) {
            $new = new VehicleExt();
            $new->type = $trailer->getType();
            $new->model = $trailer->getModel();
            $new->length = $trailer->getLength();
            $new->height = $trailer->getHeight();
            $new->width = $trailer->getWidth();
            $seg->trailers[] = $new;
        }
        if (count($seg->trailers) === 0) {
            $seg->trailers = null;
        }

        if ($parsed->getAccommodations()) {
            $seg->accommodations = array_values($parsed->getAccommodations());
        }
        $seg->carrier = $parsed->getCarrier();
        $seg->vessel = $parsed->getVessel();
        $seg->traveledMiles = $parsed->getMiles();
        $seg->duration = $parsed->getDuration();
        if ($parsed->getMeals()) {
            $seg->meal = implode(", ", array_unique($parsed->getMeals()));
        }
        $seg->cabin = $parsed->getCabin();

        $seg->adultsCount = $parsed->getAdults();
        $seg->kidsCount = $parsed->getKids();
        $seg->pets = $parsed->getPets();

        return $seg;
    }

    protected function convertPoint($name, $address, $code, $date, Extra $extra): TransportLocation
    {
        $point = new TransportLocation();
        $point->localDateTime = Util::date($date);
        $point->address = Util::emptyAddress([$address, $name, $code]);
        $point->name = $name ?? $address;
        foreach ([$address, $name] as $key) {
            if ($key && $geo = $extra->data->getGeo($key)) {
                $point->address = Util::address($key, $geo, $point->localDateTime);
                if ($geo->name) {
                    $point->name = $geo->name;
                }
                break;
            }
        }
        $point->stationCode = $code;
        return $point;
    }

}