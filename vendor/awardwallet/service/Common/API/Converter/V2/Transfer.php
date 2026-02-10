<?php


namespace AwardWallet\Common\API\Converter\V2;


use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Schema\Itineraries\Car;
use AwardWallet\Schema\Itineraries\Itinerary as OutputItinerary;
use AwardWallet\Schema\Itineraries\TransferLocation;
use AwardWallet\Schema\Parser\Common\Itinerary as ParsedItinerary;
use AwardWallet\Schema\Parser\Common\TransferSegment;

class Transfer extends Itinerary
{

    protected function initItinerary(ParsedItinerary $parsed, Extra $extra): OutputItinerary
    {
        return new \AwardWallet\Schema\Itineraries\Transfer();
    }

    protected function convertItinerary(ParsedItinerary $parsed, OutputItinerary $it, Extra $extra): OutputItinerary
    {
        /** @var \AwardWallet\Schema\Parser\Common\Transfer $parsed */
        /** @var \AwardWallet\Schema\Itineraries\Transfer $it */
        $it->travelers = Util::names($parsed->getTravellers(), $parsed->getAreNamesFull(), null);
        $it->confirmationNumbers = Util::confirmations($parsed);
        $it->segments = [];
        foreach($parsed->getSegments() as $s)
            $it->segments[] = $this->convertSegment($s, $extra);
        return $it;
    }

    protected function convertSegment(TransferSegment $parsed, Extra $extra) {
        $seg = new \AwardWallet\Schema\Itineraries\TransferSegment();
        $seg->departure = $this->convertPoint($parsed->getDepName(), $parsed->getDepAddress(), $parsed->getDepCode(), $parsed->getDepDate(), $extra);
        $seg->arrival = $this->convertPoint($parsed->getArrName(), $parsed->getArrAddress(), $parsed->getArrCode(), $parsed->getArrDate(), $extra);
        if ($parsed->getCarType() || $parsed->getCarModel() || $parsed->getCarImageUrl()) {
            $seg->vehicleInfo = new Car();
            $seg->vehicleInfo->type = $parsed->getCarType();
            $seg->vehicleInfo->model = $parsed->getCarModel();
            $seg->vehicleInfo->imageUrl = $parsed->getCarImageUrl();
        }
        $seg->adults = $parsed->getAdults();
        $seg->kids = $parsed->getKids();
        $seg->traveledMiles = $parsed->getMiles();
        $seg->duration = $parsed->getDuration();
        return $seg;
    }

    protected function convertPoint($name, $address, $code, $date, Extra $extra) : TransferLocation {
        $point = new TransferLocation();
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
        $point->airportCode = $code;
        return $point;
    }
}