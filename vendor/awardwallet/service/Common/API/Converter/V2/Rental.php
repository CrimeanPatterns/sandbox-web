<?php


namespace AwardWallet\Common\API\Converter\V2;


use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Schema\Itineraries\Car;
use AwardWallet\Schema\Itineraries\CarRental;
use AwardWallet\Schema\Itineraries\CarRentalDiscount;
use AwardWallet\Schema\Itineraries\CarRentalLocation;
use AwardWallet\Schema\Itineraries\Fee;
use AwardWallet\Schema\Itineraries\Itinerary as OutputItinerary;
use AwardWallet\Schema\Itineraries\Person;
use AwardWallet\Schema\Parser\Common\Itinerary as ParsedItinerary;

class Rental extends Itinerary {

	protected function initItinerary(ParsedItinerary $parsed, Extra $extra): OutputItinerary {
		return new CarRental();
	}

	protected function convertItinerary(ParsedItinerary $parsed, OutputItinerary $it, Extra $extra): OutputItinerary {
		/** @var \AwardWallet\Schema\Parser\Common\Rental $parsed */
		/** @var \AwardWallet\Schema\Itineraries\CarRental $it */
		$it->confirmationNumbers = Util::confirmations($parsed);
        $it->pickup = $this->convertPoint(
			$parsed->getPickUpLocation(),
			$parsed->getPickUpDateTime(),
            $parsed->getPickUpOpeningHours(),
			$parsed->getPickUpPhone(),
			$parsed->getPickUpFax(),
			$extra
		);
		$it->dropoff = $this->convertPoint(
			$parsed->getDropOffLocation(),
			$parsed->getDropOffDateTime(),
            $parsed->getDropOffOpeningHours(),
			$parsed->getDropOffPhone(),
			$parsed->getDropOffFax(),
			$extra
		);
		$car = new Car();
		$car->type = $parsed->getCarType();
		$car->model = $parsed->getCarModel();
		$car->imageUrl = $parsed->getCarImageUrl();
		if ($car->type || $car->model || $car->imageUrl)
			$it->car = $car;
		if (count($parsed->getDiscounts()) > 0) {
			$it->discounts = [];
			foreach($parsed->getDiscounts() as $pair) {
				$new = new CarRentalDiscount();
				$new->code = $pair[0];
				$new->name = $pair[1];
				$it->discounts[] = $new;
			}
		}
		if (count($parsed->getEquipment()) > 0) {
			$it->pricedEquipment = [];
			foreach($parsed->getEquipment() as $pair) {
				$new = new Fee();
				$new->name = $pair[0];
				$new->charge = $pair[1];
				$it->pricedEquipment[] = $new;
			}
		}
		if (count($parsed->getTravellers()) > 0) {
			$t = $parsed->getTravellers()[0];
			$it->driver = new Person();
			$it->driver->name = $t[0];
			$it->driver->full = $t[1] ?? $parsed->getAreNamesFull();
		}
		$it->rentalCompany = $parsed->getCompany();
		return $it;
	}

	protected function convertPoint($loc, $date, $hours, $phone, $fax, Extra $extra): CarRentalLocation {
		$r = new CarRentalLocation();
		$r->address = Util::emptyAddress([$loc]);
        $r->localDateTime = Util::date($date);
		if ($loc && ($geo = $extra->data->getGeo($loc)))
			$r->address = Util::address($loc, $geo, $r->localDateTime);
		$r->openingHours = $hours !== null ? implode('|', $hours) : null;
		$r->phone = $phone;
		$r->fax = $fax;
		return $r;
	}
}