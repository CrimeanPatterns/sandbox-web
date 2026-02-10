<?php


namespace AwardWallet\Common\API\Converter\V2;


use AwardWallet\Common\Geo\Geo;
use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Schema\Itineraries\Aircraft;
use AwardWallet\Schema\Itineraries\Airline;
use AwardWallet\Schema\Itineraries\IssuingCarrier;
use AwardWallet\Schema\Itineraries\MarketingCarrier;
use AwardWallet\Schema\Itineraries\OperatingCarrier;
use AwardWallet\Schema\Itineraries\Person;
use AwardWallet\Schema\Itineraries\TripLocation;
use AwardWallet\Schema\Parser\Common\FlightSegment;
use AwardWallet\Schema\Itineraries\Itinerary as OutputItinerary;
use AwardWallet\Schema\Parser\Common\Itinerary as ParsedItinerary;

class Flight extends Itinerary {

	protected function initItinerary(ParsedItinerary $parsed, Extra $extra): OutputItinerary {
		return new \AwardWallet\Schema\Itineraries\Flight();
	}

	protected function convertItinerary(ParsedItinerary $parsed, OutputItinerary $it, Extra $extra): OutputItinerary {
		/** @var \AwardWallet\Schema\Parser\Common\Flight $parsed */
		/** @var \AwardWallet\Schema\Itineraries\Flight $it */
		$it->travelers = Util::names($parsed->getTravellers(), $parsed->getAreNamesFull(), null);
		if ($parsed->getInfants()) {
            if (null === $it->travelers) {
                $it->travelers = [];
            }
		    $it->travelers = array_merge($it->travelers, Util::names($parsed->getInfants(), $parsed->getAreNamesFull(), Person::TYPE_INFANT));
        }
        Util::setNumbers($it->travelers ?? [], $parsed->getAccountNumbers() ?? [], $parsed->getTicketNumbers() ?? []);
		if ($parsed->getIssuingConfirmation() || $parsed->getIssuingAirlineName() || $parsed->getTicketNumbers()) {
			$it->issuingCarrier = new IssuingCarrier();
			$it->issuingCarrier->ticketNumbers = Util::numbers($parsed->getTicketNumbers(), $parsed->getAreTicketsMasked());
			$it->issuingCarrier->airline = $this->convertAirline($parsed->getIssuingAirlineName(), $extra);
			$it->issuingCarrier->confirmationNumber = $parsed->getIssuingConfirmation();
			$it->issuingCarrier->phoneNumbers = $this->convertAirlinePhones($parsed, $parsed->getIssuingAirlineName());
		}
		$it->segments = [];
        $idx = 0;
		foreach($parsed->getSegments() as $s) {
            $it->segments[] = $this->convertSegment($s, $parsed, $extra);
            if ($s->getAssignedSeats()) {
                Util::setSeats($it->travelers ?? [], $s->getAssignedSeats(), $idx);
            }
            $idx++;
        }
		if ($parsed->getCancelled() && count($parsed->getSegments()) === 0
            && (empty($it->issuingCarrier) || empty($it->issuingCarrier->confirmationNumber))
            && !empty($parsed->getConfirmationNumbers())) {
		    if (!isset($it->issuingCarrier))
		        $it->issuingCarrier = new IssuingCarrier();
		    $it->issuingCarrier->confirmationNumber = array_values($parsed->getConfirmationNumbers())[0][0];
        }
		return $it;
	}

	protected function convertSegment(FlightSegment $parsed, \AwardWallet\Schema\Parser\Common\Flight $parsedFlight, Extra $extra): \AwardWallet\Schema\Itineraries\FlightSegment {
		$sch = $extra->solverData->getSchedule($parsed->getId());
		$seg = new \AwardWallet\Schema\Itineraries\FlightSegment();
		// dep arr
		$seg->departure = $this->convertPoint($parsed->getDepCode(), $parsed->getDepName(), $parsed->getDepAddress(), $parsed->getDepDate(), $parsed->getDepTerminal(), $extra);
		$seg->arrival = $this->convertPoint($parsed->getArrCode(), $parsed->getArrName(), $parsed->getArrAddress(), $parsed->getArrDate(), $parsed->getArrTerminal(), $extra);
		// marketing
		$seg->marketingCarrier = new MarketingCarrier();
		$seg->marketingCarrier->confirmationNumber = $parsed->getConfirmation();
		$seg->marketingCarrier->flightNumber = $parsed->getFlightNumber();
		$seg->marketingCarrier->isCodeshare = $sch ? $sch->isIsCodeshare() : null;
		$seg->marketingCarrier->airline = $this->convertAirline($parsed->getAirlineName(), $extra);
		$seg->marketingCarrier->phoneNumbers = $this->convertAirlinePhones($parsedFlight, $parsed->getAirlineName());
		// operating
		if ($parsed->getCarrierAirlineName() || $parsed->getCarrierConfirmation() || $parsed->getCarrierFlightNumber()) {
			$seg->operatingCarrier = new OperatingCarrier();
			$seg->operatingCarrier->confirmationNumber = $parsed->getCarrierConfirmation();
			$seg->operatingCarrier->flightNumber = $parsed->getCarrierFlightNumber();
			$seg->operatingCarrier->airline = $this->convertAirline($parsed->getCarrierAirlineName(), $extra);
			$seg->operatingCarrier->phoneNumbers = $this->convertAirlinePhones($parsedFlight, $parsed->getCarrierAirlineName());
			if (null === $seg->marketingCarrier->isCodeshare
				&& $seg->operatingCarrier->airline && $seg->operatingCarrier->airline->iata
				&& $seg->marketingCarrier->airline && $seg->marketingCarrier->airline->iata
				&& $seg->operatingCarrier->airline->iata !== $seg->marketingCarrier->airline->iata)
				$seg->marketingCarrier->isCodeshare = true;
		}
		if ($parsed->getIsWetlease() && $parsed->getOperatedBy())
			$seg->wetleaseCarrier = $this->convertAirline($parsed->getOperatedBy(), $extra);
		// extras
        if ($parsed->getSeats())
            $seg->seats = array_values($parsed->getSeats());
		$seg->aircraft = $this->convertAircraft($parsed->getAircraft(), $parsed->getRegistrationNumber(), $extra);
		$seg->traveledMiles = $parsed->getMiles();
		if ($seg->departure && $seg->departure->address && null !== $seg->departure->address->lat && null !== $seg->departure->address->lng
           && $seg->arrival && $seg->arrival->address && null !== $seg->arrival->address->lat && null !== $seg->arrival->address->lng) {
		    $seg->calculatedTraveledMiles = round(Geo::distance($seg->departure->address->lat, $seg->departure->address->lng, $seg->arrival->address->lat, $seg->arrival->address->lng), 0, PHP_ROUND_HALF_DOWN);
        }
		$seg->cabin = $parsed->getCabin();
		$seg->bookingCode = $parsed->getBookingCode();
		$seg->duration = $parsed->getDuration();
		if ($seg->departure && $seg->departure->localDateTime && $seg->departure->address && null !== $seg->departure->address->timezone
            && $seg->arrival && $seg->arrival->localDateTime && $seg->arrival->address && null !== $seg->arrival->address->timezone) {
		    $depUtc = strtotime($seg->departure->localDateTime) - $seg->departure->address->timezone;
		    $arrUtc = strtotime($seg->arrival->localDateTime) - $seg->arrival->address->timezone;
		    if ($arrUtc > $depUtc)
		        $seg->calculatedDuration = ($arrUtc - $depUtc) / 60;
        }
        if ($parsed->getMeals()) {
            $seg->meal = implode(", ", array_unique($parsed->getMeals()));
        }
		$seg->smoking = $parsed->getSmoking();
		$seg->status = $parsed->getStatus();
		$seg->cancelled = $parsed->getCancelled();
		$seg->stops = $parsed->getStops();
		$seg->flightStatsMethodUsed = $extra->solverData->getFsCall($parsed->getId());
		return $seg;
	}

	protected function convertAirlinePhones(\AwardWallet\Schema\Parser\Common\Flight $parsed, $airline) {
		if (!$airline || !array_key_exists($airline, $parsed->getAirlinePhones()) || !($phones = $parsed->getAirlinePhones()[$airline]))
			return null;
		return Util::phones($phones);
	}

	protected function convertPoint($code, $name, $address, $date, $terminal, Extra $extra) : TripLocation {
		$point = new TripLocation();
        $point->localDateTime = Util::date($date);
		$point->address = Util::emptyAddress([$name, $address, $code]);
		$point->name = $name ?? $address;
		foreach([$code, $address, $name] as $key) {
			if ($key && $geo = $extra->data->getGeo($key)) {
				$point->address = Util::address($key, $geo, $point->localDateTime);
                if ($geo->name)
                    $point->name = $geo->name;
				break;
			}
		}
		$point->airportCode = $code;
		$point->terminal = $terminal;
		return $point;
	}

	protected function convertAirline($name, Extra $extra) {
		if (!$name)
			return null;
		$r = new Airline();
		if ($airline = $extra->data->getAirline($name)) {
			$r->name = $airline->name;
			$r->iata = $airline->iata;
			$r->icao = $airline->icao;
		}
		else
			$r->name = $name;
		return $r;
	}

	protected function convertAircraft($name, $regNum, Extra $extra) {
		if (!$name)
			return null;
		$r = new Aircraft();
		if ($a = $extra->data->getAircraft($name)) {
			$r->name = $a->name;
			$r->iataCode = $a->iataCode;
			$r->regional = $a->regional;
			$r->wideBody = $a->wideBody;
			$r->jet = $a->jet;
			$r->turboProp = $a->turboProp;
		}
		else
			$r->name = $name;
        $r->registrationNumber = $regNum;
		return $r;
	}
}