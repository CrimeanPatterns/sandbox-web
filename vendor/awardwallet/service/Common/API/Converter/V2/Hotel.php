<?php


namespace AwardWallet\Common\API\Converter\V2;


use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Schema\Itineraries\HotelReservation;
use AwardWallet\Schema\Itineraries\Itinerary as OutputItinerary;
use AwardWallet\Schema\Itineraries\Room;
use AwardWallet\Schema\Parser\Common\Itinerary as ParsedItinerary;


class Hotel extends Itinerary {


	protected function initItinerary(ParsedItinerary $parsed, Extra $extra): OutputItinerary {
		return new HotelReservation();
	}

	protected function convertItinerary(ParsedItinerary $parsed, OutputItinerary $it, Extra $extra): OutputItinerary {
		/** @var \AwardWallet\Schema\Parser\Common\Hotel $parsed */
		/** @var \AwardWallet\Schema\Itineraries\HotelReservation $it */
		$it->confirmationNumbers = Util::confirmations($parsed);
		$it->hotelName = $parsed->getHotelName();
		$it->chainName = $parsed->getChainName();
        $it->checkInDate = Util::date($parsed->getCheckInDate());
        $it->checkOutDate = Util::date($parsed->getCheckOutDate());
		if ($parsed->getHotelName() && $geo = $extra->data->getGeo($parsed->getHotelName())) {
            $it->address = Util::address($parsed->getAddress() ?? $parsed->getHotelName(), $geo, $it->checkInDate);
        }
        if (empty($it->address) && $parsed->getAddress() && $geo = $extra->data->getGeo($parsed->getAddress())) {
            $it->address = Util::address($parsed->getAddress(), $geo, $it->checkInDate);
        }
        if (empty($it->address)) {
            $it->address = Util::emptyAddress([$parsed->getAddress()]);
        }
		$it->phone = $parsed->getPhone();
		$it->fax = $parsed->getFax();
		$it->guests = Util::names($parsed->getTravellers(), $parsed->getAreNamesFull(), null);
		$it->guestCount = $parsed->getGuestCount();
		$it->kidsCount = $parsed->getKidsCount();
		$it->roomsCount = $parsed->getRoomsCount();
		$it->cancellationDeadline = Util::date($parsed->getDeadline());
		$it->isNonRefundable = $parsed->getNonRefundable();
		$it->cancellationNumber = $parsed->getCancellationNumber();
		$it->rooms = [];
		foreach($parsed->getRooms() as $room) {
			$new = new Room();
			$new->type = $room->getType();
			$new->description = $room->getDescription();
			$new->rateType = $room->getRateType();
			if (!empty($room->getRates()))
			    $new->rate = implode('|', $room->getRates());
			else
			    $new->rate = $room->getRate();
			$it->rooms[] = $new;
		}
		if (count($it->rooms) === 0)
			$it->rooms = null;
		$it->freeNights = $parsed->getFreeNights();
		return $it;
	}
}