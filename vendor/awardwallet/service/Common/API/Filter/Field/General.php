<?php


namespace AwardWallet\Common\API\Filter\Field;


use AwardWallet\Common\API\Filter\BaseField;
use AwardWallet\Schema\Itineraries\Flight;
use AwardWallet\Schema\Itineraries\CarRental;
use AwardWallet\Schema\Itineraries\Ferry;
use AwardWallet\Schema\Itineraries\Event;
use AwardWallet\Schema\Itineraries\Bus;
use AwardWallet\Schema\Itineraries\Train;
use AwardWallet\Schema\Itineraries\Transfer;
use AwardWallet\Schema\Itineraries\HotelReservation;
use AwardWallet\Schema\Itineraries\Parking;

class General extends BaseField
{

    public function filterCancelled(): bool
    {
        return false;
    }

    public function getRequiredFields()
    {
        return [
            HotelReservation::class => [
                'address',
                'checkInDate',
                'checkOutDate',
                'hotelName',
            ],
            Flight::class => [
                'segments',
                'segments[].departure.airportCode',
                'segments[].departure.name',
                'segments[].departure.localDateTime',
                'segments[].arrival.airportCode',
                'segments[].arrival.name',
                'segments[].arrival.localDateTime',
                'segments[].marketingCarrier.airline',
                'segments[].marketingCarrier.flightNumber',
            ],
            CarRental::class => [
                'pickup',
                'pickup.localDateTime',
                'pickup.address',
                'dropoff',
                'dropoff.localDateTime',
                'dropoff.address',
            ],
            Bus::class => [
                'segments',
                'segments[].departure.localDateTime',
                'segments[].departure.name',
                'segments[].arrival.localDateTime',
                'segments[].arrival.name',
            ],
            Train::class => [
                'segments',
                'segments[].departure.localDateTime',
                'segments[].departure.name',
                'segments[].arrival.localDateTime',
                'segments[].arrival.name',
            ],
            Transfer::class => [
                'segments',
                'segments[].departure.localDateTime',
                'segments[].departure.name',
                'segments[].arrival.localDateTime',
                'segments[].arrival.name',
            ],
            Ferry::class => [
                'segments',
                'segments[].departure.localDateTime',
                'segments[].departure.name',
                'segments[].arrival.localDateTime',
                'segments[].arrival.name',
            ],
            Event::class => [
                'startDateTime',
                'eventName',
            ],
            Parking::class => [
                'address',
                'startDateTime',
                'endDateTime',
            ],
        ];
    }

}