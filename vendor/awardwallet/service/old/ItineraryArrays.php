<?php

namespace AwardWallet\ItineraryArrays;

use ArrayObject;

/**
 * Class CarRental
 * @package ItineraryArrays
 *
 * @property 'L' $Kind
 * @property string $Number
 * @property int $PickupDatetime
 * @property string $PickupLocation
 * @property int $DropoffDatetime
 * @property string $DropoffLocation
 * @property string $PickupPhone
 * @property string $PickupFax
 * @property string $PickupHours
 * @property string $DropoffFax
 * @property string $RentalCompany
 * @property string $CarType
 * @property string $CarModel
 * @property string $CarImageUrl
 * @property string $RenterName
 * @property string $PromoCode
 * @property float $TotalCharge
 * @property string $Currency
 * @property float $TotalTaxAmount
 * @property string $AccountNumbers
 * @property string $Status
 * @property string $ServiceLevel
 * @property bool $Cancelled
 * @property array $PricedEquips
 * @property float $Discount
 * @property array $Discounts
 * @property array $Fees
 * @property int $ReservationDate
 * @property bool $NoItineraries
 */
class CarRental extends ArrayObject {};


/**
 * Class AirTrip
 * @package ItineraryArrays
 *
 * @property 'T' $Kind
 * @property string $RecordLocator
 * @property array $Passengers
 * @property string $AccountNumbers
 * @property bool $Cancelled
 * @property float $TotalCharge
 * @property float $BaseFare
 * @property string $Currency
 * @property float $Tax
 * @property string $Status
 * @property int $ReservationDate
 * @property bool $NoItineraries
 * @property int $TripCategory
 * @property array $TripSegments
 */
class AirTrip extends ArrayObject {};


/**
 * Class AirTripSegment
 * @package ItineraryArrays
 *
 * @property string $FlightNumber
 * @property string $DepCode
 * @property string $DepName
 * @property int $DepDate
 * @property string $ArrCode
 * @property string $ArrName
 * @property int $ArrDate
 * @property string $AirlineName
 * @property string $Aircraft
 * @property float $TraveledMiles
 * @property string $Class
 * @property string $Cabin
 * @property string $BookingClass
 * @property string $Duration
 * @property string $Meal
 * @property string $Seats
 * @property bool $Smoking
 * @property int $Stops
 * @property string $DepartureTerminal
 * @property string $ArrivalTerminal
 */
class AirTripSegment extends ArrayObject {};

/**
 * Class Hotel
 * @package ItineraryArrays
 * @property 'R' $Kind
 * @property string $ConfirmationNumber
 * @property string $HotelName
 * @property string $2ChainName
 * @property int $CheckInDate
 * @property int $CheckOutDate
 * @property string $Address
 * @property array $DetailedAddress
 * @property string $Phone
 * @property string $Fax
 * @property array $GuestNames
 * @property int $Guests
 * @property int $Kids
 * @property int $Rooms
 * @property string $Rate
 * @property string $RateType
 * @property string $CancellationPolicy
 * @property string $RoomType
 * @property string $RoomTypeDescription
 * @property int $Cost
 * @property int $Taxes
 * @property float $Total
 * @property string $Currency
 * @property string $AccountNumbers
 * @property string $Status
 * @property bool $Cancelled
 * @property int $ReservationDate
 * @property bool $NoItineraries
 */
class Hotel extends ArrayObject {};


/**
 * Class Restaurant
 * @package ItineraryArrays
 * @property 'E' $Kind
 * @property string $ConfNo
 * @property string $Name
 * @property int $StartDate
 * @property int $EndDate
 * @property string $Address
 * @property string $Phone
 * @property string $DinerName
 * @property int $Guests
 * @property float $TotalCharge
 * @property string $Currency
 * @property float $Tax
 * @property string $AccountNumbers
 * @property string $Status
 * @property bool $Cancelled
 * @property int $ReservationDate
 * @property bool $NoItineraries
 */
class Restaurant extends ArrayObject {};

/**
 * Class CruiseTrip
 * @package ItineraryArrays
 * @property 'T' $Kind
 * @property TRIP_CATEGORY_CRUISE $TripCategory
 * @property string $RecordLocator
 * @property array $Passengers
 * @property string $AccountNumbers
 * @property bool $Cancelled
 * @property string $ShipName
 * @property string $ShipCode
 * @property string $CruiseName
 * @property string $Deck
 * @property string $RoomNumber
 * @property string $RoomClass
 * @property string $Status
 * @property array $TripSegment
 */
class CruiseTrip extends ArrayObject {};

/**
 * Class CruiseTripSegment
 * @package ItineraryArrays
 * @property string $Port
 * @property int $DepDate
 * @property int $ArrDate
 */
class CruiseTripSegment extends ArrayObject {};

/**
 * Class TrainTrip
 * @package ItineraryArrays
 * @property 'T' $Kind
 * @property TRIP_CATEGORY_TRAIN $TripCategory
 * @property string $RecordLocator
 * @property array $Passengers
 * @property string $AccountNumbers
 * @property bool $Cancelled
 * @property float $TotalCharge
 * @property float $BaseFare
 * @property string $Currency
 * @property float $Tax
 * @property string $Status
 * @property int $ReservationDate
 * @property bool $NoItineraries
 */
class TrainTrip extends ArrayObject {}