<?php

namespace AwardWallet\Engine\testprovider\Itineraries;

use AwardWallet\Engine\testprovider\Success;
use AwardWallet\Schema\Itineraries\Flight;
use AwardWallet\Schema\Itineraries\HotelReservation;

class CommonSchema extends Success
{
    public function Parse()
    {
        $this->SetBalance(10);
    }

    public function ParseItineraries()
    {
        switch ($this->AccountFields['Login2']) {
            case Flight::class:
                $this->flightItem();

                break;

            case HotelReservation::class:
                $this->hotelReservationItem();

                break;
        }

        return [];
    }

    public function testParsingEntities()
    {
        $flight = $this->itinerariesMaster->createFlight();
    }

    public function hotelReservationItem()
    {
        $reservation = $this->itinerariesCollection->addReservation();
        $reservation->setHotelName("Courtyard Santa Clarita Valencia")
                    ->setCheckOutDate("2017-07-12T12:00:00")
                    ->setCheckInDate("2017-07-09T15:00:00")
                    ->setPhone("+1-661-257-3220")
                    ->setGuestCount(1)
                    ->setRoomsCount(1)
                    ->setCancellationPolicy("Please note that a change in the length or dates may result in a rate change...");

        $reservation->providerDetails
                        ->setStatus("booked")
                        ->setCode("marriott")
                        ->setName("Marriott");

        $reservation->totalPrice
                        ->setTotal(721.7)
                        ->setTax(25.90)
                        ->setCurrencyCode("USD");

        $reservation->address
                        ->setText('28523 Westinghouse Place Valencia California 91355 USA')
                        ->setTimezone(-28800)
                        ->setStateName("California")
                        ->setLng(-118.601668)
                        ->setLat(34.443049000000002)
                        ->setCountryName("United States")
                        ->setCity('Santa Clarita')
                        ->setAddressLine('28523 Westinghouse Place')
                        ->setPostalCode("91355");
    }

    public function flightItem()
    {
        $flight = $this->itinerariesCollection->addFlight();
        $flight->providerDetails
                    ->setReservationDate("2017-06-15T00:00:00")
                    ->setCode("mileageplus")
                    ->setName("United Airlines")
                    ->setStatus("confirmed")
                    ->setAccountNumbers(["12345678", "87654321"]);

        $flight->totalPrice
                    ->setTotal(1248.46)
                    ->setCost(1118.96)
                    ->setTax(129.50)
                    ->setCurrencyCode("USD")
                    ->fees->add()
                        ->setName('Some Fee')
                        ->setCharge(5.22);

        $flight->travelers->add()
                    ->setFullName("JOHN SMITH");

        $flight->travelAgency
                    ->phones->add()
                        ->setNumber("1-877-261-3523");
        $flight->travelAgency
                    ->confirmationNumbers->add()
                        ->setNumber("11223344556677");
        $flight->travelAgency
                    ->providerDetails
                        ->setCode("expedia")
                        ->setName("Expedia.com")
                        ->setEarnedAwards("86 points");

        $flight->issuingCarrier
                    ->setConfirmationNumber("ABCDEF")
                    ->setTicketNumbers(["0161122334455", "0165544332211"])
                    ->phones->add()
                        ->setNumber("+1-800-421-4655");
        $flight->issuingCarrier
                    ->airline
                        ->setIcao("UAL")
                        ->setIata("UA")
                        ->setName("United Airlines");

        $segment = $flight->segments->add();
        $segment->setTraveledMiles("7,806 m")
                ->setCabin("United Economy")
                ->setBookingClass("K")
                ->setDuration("14 hr 55 mn")
                ->setMeal("Dinner")
                ->setSeats(["25D"])
                ->aircraft
                    ->setIataCode("228")
                    ->setName("Boeing 777-200")
                    ->setTurboProp(false)
                    ->setJet(true)
                    ->setWideBody(true)
                    ->setRegional(false);

        $segment->departure
                    ->setAirportCode("EWR")
                    ->setName("New York/Newark, NJ, US")
                    ->setLocalDateTime("2016-02-16T20:15:00")
                    ->address
                        ->setText("Newark (Newark Liberty International Airport), NJ")
                        ->setTimezone(-14400)
                        ->setStateName("New Jersey")
                        ->setLng(-74.1744624)
                        ->setLat(40.6895314)
                        ->setCountryName("United States")
                        ->setCity("Newark")
                        ->setAddressLine("3 Brewster Road")
                        ->setPostalCode("07114");

        $segment->arrival
                    ->setAirportCode("BOM")
                    ->setName("Mumbai, IN")
                    ->setLocalDateTime("2016-02-17T21:40:00")
                    ->address // only for test schemaFlight.json
                        ->setText("Mumbai (Chhatrapati Shivaji International Airport), India")
                        ->setTimezone(19800)
                        ->setStateName("")
                        ->setLng(72.8656144)
                        ->setLat(19.0895595)
                        ->setCountryName("India")
                        ->setCity("Mumbai")
                        ->setAddressLine("Vile Parle East Vile Parle")
                        ->setPostalCode("400099");

        $segment->wetleaseCarrier
                    ->setName("Lufthansa CityLine")
                    ->setIata("CL")
                    ->setIcao("CLH");

        $segment->operatingCarrier
                    ->setFlightNumber("4456")
                    ->phones->add()
                        ->setNumber("+1-866-266-5588");
        $segment->operatingCarrier
                    ->airline
                        ->setName("Lufthansa")
                        ->setIata("LH")
                        ->setIcao("DLH");

        $segment->marketingCarrier
                    ->setIsCodeshare(true)
                    ->setConfirmationNumber("ABCDEF")
                    ->setFlightNumber("48")
                    ->phones->add()
                        ->setNumber("+1-800-421-4655");
        $segment->marketingCarrier
                    ->airline
                        ->setName("United Airlines")
                        ->setIata("UA")
                        ->setIcao("UAL");
    }
}
