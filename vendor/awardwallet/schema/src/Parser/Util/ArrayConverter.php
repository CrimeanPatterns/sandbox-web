<?php

namespace AwardWallet\Schema\Parser\Util;


use AwardWallet\Schema\Parser\Common\AwardRedemption;
use AwardWallet\Schema\Parser\Common\BaseSegment;
use AwardWallet\Schema\Parser\Common\BoardingPass;
use AwardWallet\Schema\Parser\Common\Bus;
use AwardWallet\Schema\Parser\Common\CardPromo;
use AwardWallet\Schema\Parser\Common\Cruise;
use AwardWallet\Schema\Parser\Common\CruiseSegment;
use AwardWallet\Schema\Parser\Common\Event;
use AwardWallet\Schema\Parser\Common\Ferry;
use AwardWallet\Schema\Parser\Common\Flight;
use AwardWallet\Schema\Parser\Common\FlightSegment;
use AwardWallet\Schema\Parser\Common\Hotel;
use AwardWallet\Schema\Parser\Common\Itinerary;
use AwardWallet\Schema\Parser\Common\Rental;
use AwardWallet\Schema\Parser\Common\Statement;
use AwardWallet\Schema\Parser\Common\Train;
use AwardWallet\Schema\Parser\Common\Transfer;
use AwardWallet\Schema\Parser\Component\Field\Field;
use AwardWallet\Schema\Parser\Component\Master;
use AwardWallet\Schema\Parser\Email\Email;

class ArrayConverter {

	protected static $kinds = [
		'RecordLocator' => 'T',
		'ConfirmationNumber' => 'R',
		'Number' => 'L',
		'ConfNo' => 'E',
	];

	const TRIP_CODE_UNKNOWN = 'UnknownCode';
	const CONFNO_UNKNOWN = 'UnknownNumber';
	const MISSING_DATE = -1;
	const FLIGHT_NUMBER_UNKNOWN = 'UnknownFlightNumber';
    const AIRLINE_UNKNOWN = 'UnknownAirlineName';


	/**
	 * @param array $data
	 * @param Email $email
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public static function convertEmail(array $data, Email $email) {
		$data = array_merge(['parsedData' => [], 'providerCode' => null, 'emailType' => 'Unknown'], $data);
		if (!is_array($data['parsedData']))
		    $data['parsedData'] = [];
		if (!empty($data['providerCode']))
			$email->setProviderCode($data['providerCode']);
		if (!empty($data['emailType']))
			$email->setType($data['emailType']);
		if (isset($data['parsedData']['TotalCharge'])) {
			$t = $data['parsedData']['TotalCharge'];
			if (isset($t['Amount']) && strlen($t['Amount']) > 0)
				$email->obtainPrice()->setTotal(self::deformatNumber($t['Amount']));
			if (!empty($t['Currency']))
				if (preg_match('/^[A-Z]+$/', $t['Currency']) > 0)
					$email->obtainPrice()->setCurrencyCode($t['Currency']);
				else
					$email->obtainPrice()->setCurrencySign($t['Currency']);
			if (isset($t['SpentAwards']) && strlen($t['SpentAwards']) > 0)
				$email->obtainPrice()->setSpentAwards($t['SpentAwards']);
		}
		if (!empty($data['parsedData']['userEmail']))
			$email->setUserEmail($data['parsedData']['userEmail'], true);
		self::convertMaster($data['parsedData'], $email);

	}

	/**
	 * @param array $data
	 * @param Master $master
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public static function convertMaster(array $data, Master $master) {
		$data = array_merge(['Itineraries' => [], 'BoardingPass' => [], 'Properties' => null, 'Activity' => null], $data);
        foreach(['Itineraries', 'BoardingPass'] as $k)
            if (!is_array($data[$k]))
                $data[$k] = [];
        foreach ($data['Itineraries'] as $it) {
            if (!is_array($it))
                continue;
            if (isset($it['NoItineraries']) && ($it['NoItineraries'] === true || $it['NoItineraries'] === "true")) {
                $master->setNoItineraries(true);
                continue;
            }
            self::solveKind($it);
            switch ($it['Kind']) {
                case 'T':
                    if (empty($it['TripCategory'])) {
                        $it['TripCategory'] = 1;
                    }
                    switch ($it['TripCategory']) {
                        case 1: // AIR
                            $f = $master->createFlight();
                            self::convertFlight($it, $f);
                            break;
                        case 2: // BUS
                            $b = $master->createBus();
                            self::convertBus($it, $b);
                            break;
                        case 3: // TRAIN
                            $t = $master->createTrain();
                            self::convertTrain($it, $t);
                            break;
                        case 4: // CRUISE
                            $c = $master->createCruise();
                            self::convertCruise($it, $c);
                            break;
                        case 5: // FERRY
                            $c = $master->createFerry();
                            self::convertFerry($it, $c);
                            break;
                        case 6: // TRANSFER
                            $t = $master->createTransfer();
                            self::convertTransfer($it, $t);
                            break;
                    }
                    break;
                case 'R':
                    $h = $master->createHotel();
                    self::convertHotel($it, $h);
                    break;
                case 'L':
                    $r = $master->createRental();
                    self::convertRental($it, $r);
                    break;
                case 'E':
                    $e = $master->createEvent();
                    self::convertEvent($it, $e);
            }
        }
		foreach($data['BoardingPass'] as $bp) {
			$new = $master->createBoardingPass();
			self::convertBoardingPass($bp, $new);
		}
		if (!empty($data['Properties']) && is_array($data['Properties'])) {
			$st = $master->createStatement();
			self::convertStatement($data['Properties'], $st);
		}
		if (!empty($data['Activity']) && is_array($data['Activity'])) {
			if (!isset($st))
				$st = $master->createStatement();
			$st->setActivityArray($data['Activity']);
		}
        if (isset($data['awardRedemption'])) {
            foreach($data['awardRedemption'] as $ar) {
                $arNew = $master->createAwardRedemption();
                self::convertAwardRedemption($ar, $arNew);
            }
        }
	}

	protected static function convertStatement($data, Statement $st)
    {
        foreach($data as $code => $value) {
            switch($code) {
                case 'Balance':
                    $st->setBalance($value);
                    break;
                case 'BalanceDate':
                    $st->setBalanceDate($value);
                    break;
                case 'AccountExpirationDate':
                    if ($value !== false)
                        $st->setExpirationDate($value);
                    break;
                case 'Login':
                    $st->setLogin($value);
                    break;
                case 'PartialLogin':
                    if ($s = self::filterPartialString($value))
                        $st->setLogin($s)->masked();
                    break;
                case 'SubAccounts':
                    foreach($value as $subacc)
                        $st->addSubAccount($subacc);
                    break;
                case 'DetectedCards':
                    foreach($value as $card)
                        $st->addDetectedCard($card);
                    break;
                default:
                    $st->addProperty($code, $value);
                    break;
            }
        }
    }

    private static function filterPartialString($value)
    {
        if ($value[strlen($value)-1] === '$')
            return substr($value, 0, -1);
        return null;
    }

	/**
	 * @param $data
	 * @param BoardingPass $bp
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	protected static function convertBoardingPass($data, BoardingPass $bp) {
		if (!empty($data['DepCode']))
			$bp->setDepCode($data['DepCode']);
		if (!empty($data['DepDate']) && is_numeric($data['DepDate']))
			$bp->setDepDate(intval($data['DepDate']));
		if (!empty($data['RecordLocator']) && $data['RecordLocator'] !== self::CONFNO_UNKNOWN)
			$bp->setRecordLocator($data['RecordLocator']);
		if (!empty($data['FlightNumber']))
			$bp->setFlightNumber($data['FlightNumber']);
		if (!empty($data['BoardingPassURL']))
			$bp->setUrl($data['BoardingPassURL']);
		if (!empty($data['AttachmentFileName']))
			$bp->setAttachmentName($data['AttachmentFileName']);
		if (!empty($data['Passengers']))
			if (is_array($data['Passengers']))
				$bp->setTraveller(array_shift($data['Passengers']));
			elseif (is_string($data['Passengers']))
				$bp->setTraveller($data['Passengers']);
	}

	/**
	 * @param $data
	 * @param Cruise $it
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	protected static function convertCruise($data, Cruise $it) {
		$data = array_merge(['TripSegments' => []], $data);
		self::convertItinerary($data, $it, 'RecordLocator');
		if (!empty($data['Passengers']))
			foreach(self::explodeString($data['Passengers']) as $name)
				if (!empty($name))
					$it->addTraveller($name);
		self::convertPrice($data, $it, 'TotalCharge', 'BaseFare', 'Tax');
		if (!empty($data['ShipName']))
			$it->setShip($data['ShipName']);
		if (!empty($data['ShipCode']))
			$it->setShipCode($data['ShipCode']);
		if (!empty($data['CruiseName']))
			$it->setDescription($data['CruiseName']);
		if (!empty($data['Deck']))
			$it->setDeck($data['Deck']);
		if (!empty($data['RoomNumber']))
			$it->setRoom($data['RoomNumber']);
		if (!empty($data['RoomClass']))
			$it->setClass($data['RoomClass']);
		if (!empty($data['VoyageNumber']))
		    $it->setVoyageNumber($data['VoyageNumber']);
		/** @var CruiseSegment $prev */
		$prev = null;
		foreach($data['TripSegments'] as $segment) {
			$segment = array_merge(['DepName' => null, 'DepDate' => null, 'ArrName' => null, 'ArrDate' => null], $segment);
			if (!isset($prev)) {
				$prev = $it->addSegment();
				$prev->setName($segment['DepName']);
			}
			$prev->setAboard(intval($segment['DepDate']));
			$new = $it->addSegment();
			$new->setAshore(intval($segment['ArrDate']));
			$new->setName($segment['ArrName']);
			$prev = $new;
		}
	}

	/**
	 * @param $data
	 * @param Event $it
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	protected static function convertEvent($data, Event $it) {
		self::convertItinerary($data, $it, 'ConfNo');
		self::convertPrice($data, $it, 'TotalCharge', null, 'Tax');
		if (!empty($data['EventType']))
			$it->setEventType($data['EventType']);
		else
			$it->setEventType(Event::TYPE_EVENT);
		if (!empty($data['Name']))
			$it->setName($data['Name']);
		if (!empty($data['StartDate']))
			if ((int)$data['StartDate'] === self::MISSING_DATE)
				$it->setNoStartDate(true);
			else
				$it->setStartDate($data['StartDate']);
		if (!empty($data['EndDate'])) {
			if ((int)$data['EndDate'] === self::MISSING_DATE)
				$it->setNoEndDate(true);
			else
				$it->setEndDate($data['EndDate']);
		}
		else
			$it->setNoEndDate(true);
		if (!empty($data['Address']))
			$it->setAddress($data['Address']);
		if (!empty($data['Phone']))
			$it->setPhone($data['Phone']);
		if (!empty($data['DinerName']) && is_array($data['DinerName']) && count($data['DinerName']) === 1 && ($name = array_shift($data['DinerName'])) && is_string($name))
			$data['DinerName'] = $name;
		if (!empty($data['DinerName']) && is_string($data['DinerName']))
			$it->addTraveller($data['DinerName']);
		if (!empty($data['Guests']))
			$it->setGuestCount($data['Guests']);
	}

	/**
	 * @param $data
	 * @param Bus $it
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	protected static function convertBus($data, Bus $it) {
		$data = array_merge(['TripSegments' => []], $data);
		self::convertItinerary($data, $it, 'RecordLocator');
		if (!empty($data['Passengers']))
			foreach(self::explodeString($data['Passengers']) as $name)
				if (!empty($name))
					$it->addTraveller($name);
		self::convertPrice($data, $it, 'TotalCharge', 'BaseFare', 'Tax');
		if (!empty($data['TicketNumbers']))
			foreach (self::explodeString($data['TicketNumbers']) as $tck)
				if (!empty($tck))
					$it->addTicketNumber($tck, preg_match('/XXX|\*\*/i', $tck) > 0);
		foreach($data['TripSegments'] as $segment) {
			$seg = $it->addSegment();
			self::convertSegment($segment, $seg);
			if (!empty($segment['FlightNumber'])) {
				if ($segment['FlightNumber'] === self::FLIGHT_NUMBER_UNKNOWN)
					$seg->setNoNumber(true);
				else
					$seg->setNumber($segment['FlightNumber']);
			}

			if (!empty($segment['Type']))
				$seg->setBusType($segment['Type']);
			if (!empty($segment['Vehicle']))
				$seg->setBusModel($segment['Vehicle']);
		}
	}

	/**
	 * @param $data
	 * @param Train $it
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	protected static function convertTrain($data, Train $it) {
		$data = array_merge(['TripSegments' => []], $data);
		self::convertItinerary($data, $it, 'RecordLocator');
		if (!empty($data['Passengers']))
			foreach(self::explodeString($data['Passengers']) as $name)
				if (!empty($name))
					$it->addTraveller($name);
		self::convertPrice($data, $it, 'TotalCharge', 'BaseFare', 'Tax');
		if (!empty($data['TicketNumbers']))
			foreach (self::explodeString($data['TicketNumbers']) as $tck)
				if (!empty($tck))
					$it->addTicketNumber($tck, preg_match('/XXX|\*\*/i', $tck) > 0);
		foreach($data['TripSegments'] as $segment) {
			$seg = $it->addSegment();
			self::convertSegment($segment, $seg);
			if (!empty($segment['FlightNumber'])) {
				if ($segment['FlightNumber'] === self::FLIGHT_NUMBER_UNKNOWN)
					$seg->setNoNumber(true);
				else
					$seg->setNumber($segment['FlightNumber']);
			}
			else
				$seg->setNoNumber(true);
			if (!empty($segment['Type']))
				$seg->setTrainType($segment['Type']);
			if (!empty($segment['Vehicle']))
				$seg->setTrainModel($segment['Vehicle']);
		}
	}

    /**
     * @param $data
     * @param Ferry $it
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    protected static function convertFerry($data, Ferry $it) {
        $data = array_merge(['TripSegments' => []], $data);
        self::convertItinerary($data, $it, 'RecordLocator');
        if (!empty($data['Passengers']))
            foreach(self::explodeString($data['Passengers']) as $name)
                if (!empty($name))
                    $it->addTraveller($name);
        self::convertPrice($data, $it, 'TotalCharge', 'BaseFare', 'Tax');
        if (!empty($data['TicketNumbers']))
            foreach (self::explodeString($data['TicketNumbers']) as $tck)
                if (!empty($tck))
                    $it->addTicketNumber($tck, preg_match('/XXX|\*\*/i', $tck) > 0);

        self::convertPrice($data, $it, 'TotalCharge', 'BaseFare', 'Tax');

        foreach($data['TripSegments'] as $segment) {
            $seg = $it->addSegment();
            self::convertSegment($segment, $seg);

            if (!empty($data['ShipName'])) {
                if (!empty($data['ShipCode']))
                    $seg->setVessel($data['ShipName'] . '-' . $data['ShipCode']);
                else
                    $seg->setVessel($data['ShipName']);
            } elseif (!empty($segment['FlightNumber'])) {
                if ($segment['FlightNumber'] !== self::FLIGHT_NUMBER_UNKNOWN) {
                    $seg->setVessel($segment['FlightNumber']);
                }
            }
            if (!empty($segment['Vehicle'])) {
                $v = $seg->addVehicle();
                $v->setType($segment['Vehicle']);
            }

            if (!empty($data['Deck'])) {
                if (!empty($data['RoomNumber'])) {
                    if (!empty($data['RoomClass']))
                        $seg->setAccommodations([$data['Deck'] . '-' . $data['RoomNumber'].', '.$data['RoomClass']]);
                    else
                        $seg->setAccommodations([$data['Deck'] . '-' . $data['RoomNumber']]);
                } else {
                    if (!empty($data['RoomClass']))
                        $seg->setAccommodations([$data['Deck'] .', '.$data['RoomClass']]);
                    else
                        $seg->setAccommodations($data['Deck']);
                }
            } else {
                if (!empty($data['RoomNumber'])) {
                    if (!empty($data['RoomClass']))
                        $seg->setAccommodations([$data['RoomNumber'].', '.$data['RoomClass']]);
                    else
                        $seg->setAccommodations([$data['RoomNumber']]);
                }
            }
        }
    }

    /**
	 * @param $data
	 * @param Transfer $it
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	protected static function convertTransfer($data, Transfer $it) {
		$data = array_merge(['TripSegments' => []], $data);
		self::convertItinerary($data, $it, 'RecordLocator');
		if (!empty($data['Passengers']))
			foreach(self::explodeString($data['Passengers']) as $name)
				if (!empty($name))
					$it->addTraveller($name);
		self::convertPrice($data, $it, 'TotalCharge', 'BaseFare', 'Tax');
		foreach($data['TripSegments'] as $segment) {
			$seg = $it->addSegment();
			self::convertSegment($segment, $seg);
			if (!empty($segment['Type']))
				$seg->setCarType($segment['Type']);
			if (!empty($segment['Vehicle']))
				$seg->setCarModel($segment['Vehicle']);
		}
	}

	/**
	 * @param $data
	 * @param Rental $it
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	protected static function convertRental($data, Rental $it) {
		self::convertItinerary($data, $it, 'Number');
		if (!empty($data['PickupLocation']))
			$it->setPickUpLocation($data['PickupLocation']);
		if (!empty($data['PickupDatetime']))
			if ((int)$data['PickupDatetime'] === self::MISSING_DATE)
				$it->setNoPickUpDate(true);
			else
				$it->setPickUpDateTime(intval($data['PickupDatetime']));
		if (!empty($data['PickupPhone']))
			$it->setPickUpPhone($data['PickupPhone']);
		if (!empty($data['PickupFax']))
			$it->setPickUpFax($data['PickupFax']);
		if (!empty($data['PickupHours']))
			$it->setPickUpOpeningHours([$data['PickupHours']]);
		if (!empty($data['DropoffLocation']))
			$it->setDropoffLocation($data['DropoffLocation']);
		if (!empty($data['DropoffDatetime']))
			if ((int)$data['DropoffDatetime'] === self::MISSING_DATE)
				$it->setNoDropOffDate(true);
			else
				$it->setDropoffDateTime(intval($data['DropoffDatetime']));
		if (!empty($data['DropoffPhone']))
			$it->setDropoffPhone($data['DropoffPhone']);
		if (!empty($data['DropoffFax']))
			$it->setDropoffFax($data['DropoffFax']);
		if (!empty($data['DropoffHours']))
			$it->setDropoffOpeningHours([$data['DropoffHours']]);
		if (!empty($data['RentalCompany']))
			$it->setCompany($data['RentalCompany']);
		if (!empty($data['CarType']))
			$it->setCarType($data['CarType']);
		if (!empty($data['CarModel']))
			$it->setCarModel($data['CarModel']);
		if (!empty($data['CarImageUrl']))
			$it->setCarImageUrl($data['CarImageUrl']);
		if (!empty($data['RenterName']) && is_array($data['RenterName']) && count($data['RenterName']) === 1 && ($name = array_shift($data['RenterName'])) && is_string($name))
			$data['RenterName'] = $name;
		if (!empty($data['RenterName']) && is_string($data['RenterName']))
			$it->addTraveller($data['RenterName']);
		self::convertPrice($data, $it, 'TotalCharge', 'BaseFare', 'TotalTaxAmount');
		// skip PricedEquips, Discounts
	}

	/**
	 * @param $data
	 * @param Hotel $it
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	protected static function convertHotel($data, Hotel $it) {
		self::convertItinerary($data, $it, 'ConfirmationNumber');
		if (!empty($data['HotelName']))
			$it->setHotelName($data['HotelName']);
		if (!empty($data['Address']))
			if (trim($data['Address']) === $it->getHotelName())
				$it->setNoAddress(true);
			else
				$it->setAddress($data['Address']);
		if (!empty($data['2ChainName']))
			$it->setChainName($data['2ChainName']);
		if (!empty($data['CheckInDate']))
			if ((int)$data['CheckInDate'] === self::MISSING_DATE)
				$it->setNoCheckInDate(true);
			else
				$it->setCheckInDate(intval($data['CheckInDate']));
		if (!empty($data['CheckOutDate']))
			if ((int)$data['CheckOutDate'] === self::MISSING_DATE)
				$it->setNoCheckOutDate(true);
			else
				$it->setCheckOutDate(intval($data['CheckOutDate']));
		if (!empty($data['DetailedAddress'])) {
			$da = $data['DetailedAddress'];
			if (!empty($da['AddressLine']))
				$it->obtainDetailedAddress('')->setAddressLine($da['AddressLine']);
			if (!empty($da['CityName']))
				$it->obtainDetailedAddress('')->setCity($da['CityName']);
			if (!empty($da['PostalCode']))
				$it->obtainDetailedAddress('')->setZip($da['PostalCode']);
			if (!empty($da['StateProv']))
				$it->obtainDetailedAddress('')->setState($da['StateProv']);
			if (!empty($da['Country']))
				$it->obtainDetailedAddress('')->setCountry($da['Country']);
		}
		if (!empty($data['Phone']))
			$it->setPhone($data['Phone']);
		if (!empty($data['Fax']))
			$it->setFax($data['Fax']);
		if (!empty($data['GuestNames']))
			foreach(self::explodeString($data['GuestNames']) as $name)
				if (!empty($name))
					$it->addTraveller($name);
        if (isset($data['Guests']) && (!is_string($data['Guests'] || strlen($data['Guests']) > 0)))
			$it->setGuestCount($data['Guests'], true);
        if (isset($data['Kids']) && (!is_string($data['Kids'] || strlen($data['Kids']) > 0)))
			$it->setKidsCount($data['Kids'], true);
		if (isset($data['Rooms']) && (!is_string($data['Rooms'] || strlen($data['Rooms']) > 0)))
			$it->setRoomsCount($data['Rooms']);
		$rates = !empty($data['Rate']) ? self::explodeString($data['Rate'], '|') : [];
		$rateTypes = !empty($data['RateType']) ? self::explodeString($data['RateType'], '|') : [];
		$roomTypes = !empty($data['RoomType']) ? self::explodeString($data['RoomType'], '|') : [];
		$roomDescs = !empty($data['RoomTypeDescription']) ? self::explodeString($data['RoomTypeDescription'], '|') : [];
		$run = [];
		foreach([$rates, $roomTypes, $rateTypes, $roomDescs] as $arr)
		    if (count($arr) > 0) {
		        $run = $arr;
		        break;
            }

		foreach($run as $i => $rate) {
			$r = $it->addRoom();
			if (count($run) === count($rates))
			    $r->setRate($rates[$i], true, true);
			if (count($run) === count($rateTypes))
				$r->setRateType($rateTypes[$i], true, true);
			if (count($run) === count($roomTypes))
				$r->setType($roomTypes[$i], true, true);
			if (count($run) === count($roomDescs))
				$r->setDescription($roomDescs[$i], true, true);
		}
		if (!empty($data['CancellationPolicy'])) {
            $it->setCancellation($data['CancellationPolicy']);
            if (preg_match('/\bno[tn][- ]?refundable\b/i', $data['CancellationPolicy']) > 0)
                $it->setNonRefundable(true);
        }
		self::convertPrice($data, $it, 'Total', 'Cost', 'Taxes');
	}

	/**
	 * @param $data
	 * @param Flight $it
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	protected static function convertFlight($data, Flight $it) {
		$data = array_merge(['TripSegments' => []], $data);
		self::convertItinerary($data, $it, 'RecordLocator');
		if (!empty($data['Passengers']))
			foreach(self::explodeString($data['Passengers']) as $name)
				if (!empty($name))
					$it->addTraveller($name);
		self::convertPrice($data, $it, 'TotalCharge', 'BaseFare', 'Tax');
		if (!empty($data['TicketNumbers']))
			foreach (self::explodeString($data['TicketNumbers']) as $tck)
				if (!empty($tck))
					$it->addTicketNumber($tck, preg_match('/XXX|\*\*/i', $tck) > 0);
		foreach($data['TripSegments'] as $segment) {
			$seg = $it->addSegment();
			self::convertFlightSegment($segment, $seg);
		}
	}

	/**
	 * @param $data
	 * @param FlightSegment $seg
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	protected static function convertFlightSegment($data, FlightSegment $seg) {
		self::convertSegment($data, $seg);
		if (!empty($data['FlightNumber']))
			if ($data['FlightNumber'] === self::FLIGHT_NUMBER_UNKNOWN)
				$seg->setNoFlightNumber(true);
			elseif (preg_match('/^(?<a>[A-Z][A-Z\d]|[A-Z\d][A-Z])\s*(?<fn>\d{1,5})$/', $data['FlightNumber'], $m) > 0) {
				$seg->setAirlineName($m['a']);
				$seg->setFlightNumber($m['fn']);
			}
			else
				$seg->setFlightNumber($data['FlightNumber']);
		if (!empty($data['DepartureTerminal']))
			$seg->parseDepTerminal($data['DepartureTerminal']);
		if (!empty($data['ArrivalTerminal']))
			$seg->parseArrTerminal($data['ArrivalTerminal']);
		if (!empty($data['AirlineName']))
            if ($data['AirlineName'] === self::AIRLINE_UNKNOWN)
                $seg->setNoAirlineName(true);
            else
                $seg->setAirlineName($data['AirlineName']);
		if (!empty($data['Operator']))
			$seg->setOperatedBy($data['Operator']);
		if (!empty($data['Aircraft']))
			$seg->setAircraft($data['Aircraft']);
		if (!empty($data['Status']))
		    $seg->setStatus($data['Status']);
		if (isset($data['Cancelled']))
		    $seg->setCancelled(!!$data['Cancelled']);
	}

	/**
	 * @param $data
	 * @param Itinerary $it
	 * @param $confNoKey
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	protected static function convertItinerary($data, Itinerary $it, $confNoKey) {
		$main = null;
		if (!empty($data[$confNoKey]))
			if ($data[$confNoKey] === self::CONFNO_UNKNOWN)
				$it->setNoConfirmationNumber(true);
			else {
				$it->addConfirmationNumber(trim($data[$confNoKey]), null, !empty($data['ConfirmationNumbers']));
				$main = $data[$confNoKey];
			}
		if (!empty($data['ReservationDate']) && is_numeric($data['ReservationDate'])) {
			if ($data['ReservationDate'] % 60 !== 0 && $data['ReservationDate'] > strtotime('2010-01-01'))
				$data['ReservationDate'] -= $data['ReservationDate'] % 60;
			$it->setReservationDate(intval($data['ReservationDate']));
		}
		if (!empty($data['TripNumber']))
			$it->obtainTravelAgency()->addConfirmationNumber(trim($data['TripNumber']));
		if (!empty($data['ConfirmationNumbers']))
			foreach(self::explodeString($data['ConfirmationNumbers']) as $conf)
				if (!empty($conf) && (!isset($main) || $main !== $conf))
					$it->addConfirmationNumber(trim($conf));
		if (!empty($data['AccountNumbers']))
			foreach(self::explodeString($data['AccountNumbers']) as $acc)
				if (!empty($acc))
					$it->addAccountNumber($acc, preg_match('/XXX|\*\*/i', $acc) > 0);
		if (!empty($data['Cancelled']))
			$it->setCancelled(true);
		if (!empty($data['SpentAwards']))
			$it->obtainPrice()->setSpentAwards($data['SpentAwards']);
		if (!empty($data['EarnedAwards']))
			$it->setEarnedAwards($data['EarnedAwards']);
		if (!empty($data['Status']))
			$it->setStatus($data['Status']);
	}

	/**
	 * @param $data
	 * @param BaseSegment $seg
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	protected static function convertSegment($data, BaseSegment $seg) {
		if (!empty($data['DepCode']))
			if ($data['DepCode'] === self::TRIP_CODE_UNKNOWN)
				$seg->setNoDepCode(true);
			else
				$seg->setDepCode($data['DepCode']);
		if (!empty($data['DepDate']))
			if ((int)$data['DepDate'] === self::MISSING_DATE)
				$seg->setNoDepDate(true);
			else
				$seg->setDepDate(intval($data['DepDate']));
		if (!empty($data['DepName']))
			$seg->setDepName($data['DepName']);
		if (!empty($data['ArrCode']))
			if ($data['ArrCode'] === self::TRIP_CODE_UNKNOWN)
				$seg->setNoArrCode(true);
			else
				$seg->setArrCode($data['ArrCode']);
		if (!empty($data['ArrDate']))
			if ((int)$data['ArrDate'] === self::MISSING_DATE)
				$seg->setNoArrDate(true);
			else
				$seg->setArrDate(intval($data['ArrDate']));
        if (!empty($data['DatesAreStrict']))
            $seg->setDatesStrict(true);
		if (!empty($data['DepAddress']))
			$seg->setDepAddress($data['DepAddress']);
		if (!empty($data['ArrAddress']))
			$seg->setArrAddress($data['ArrAddress']);
		if (!empty($data['ArrName']))
			$seg->setArrName($data['ArrName']);
		if (!empty($data['Status']))
			$seg->setStatus($data['Status']);
		if (!empty($data['Seats']))
			foreach(self::explodeString($data['Seats']) as $seat) {
				if (!empty($seat) && is_string($seat) && (!($seg instanceof FlightSegment) && preg_match(Field::BASIC_REGEXP, $seat) > 0 || preg_match('/^[A-Z\d\-\\\\\/]{1,7}$/', $seat) > 0))
					$seg->addSeat($seat);
			}
		if (isset($data['Stops']) && preg_match('/^\d+$/', $data['Stops']) > 0)
			$seg->setStops($data['Stops']);
		if (isset($data['Smoking']))
			$seg->setSmoking((boolean)$data['Smoking']);
		if (!empty($data['TraveledMiles']))
			$seg->setMiles($data['TraveledMiles']);
		if (!empty($data['Cabin']))
			$seg->setCabin($data['Cabin']);
		if (!empty($data['BookingClass']))
			$seg->setBookingCode($data['BookingClass']);
		if (!empty($data['Duration']))
			$seg->setDuration($data['Duration']);
		if (!empty($data['Meal']))
			$seg->addMeal($data['Meal']);
	}

	/**
	 * @param $data
	 * @param Itinerary $it
	 * @param $totalKey
	 * @param $costKey
	 * @param $taxKey
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	protected static function convertPrice($data, Itinerary $it, $totalKey, $costKey, $taxKey) {
		if (isset($totalKey) && isset($data[$totalKey]) && strlen($data[$totalKey]) > 0)
			$it->obtainPrice()->setTotal(self::deformatNumber($data[$totalKey]));
		if (isset($costKey) && isset($data[$costKey]) && strlen($data[$costKey]) > 0)
			$it->obtainPrice()->setCost(self::deformatNumber($data[$costKey]));
		if (isset($taxKey) && isset($data[$taxKey]) && strlen($data[$taxKey]) > 0)
			$it->obtainPrice()->addFee('Tax', self::deformatNumber($data[$taxKey]));
		if (!empty($data['Discount']))
			$it->obtainPrice()->setDiscount(self::deformatNumber($data['Discount']));
		if (!empty($data['Fees']) && is_array($data['Fees']))
			foreach ($data['Fees'] as $fee)
				if (!empty($fee['Name']) && isset($fee['Charge']) && strlen($fee['Charge']) > 0)
					$it->obtainPrice()->addFee($fee['Name'], self::deformatNumber($fee['Charge']));
		if (!empty($data['Currency']))
			if (preg_match('/^[A-Z]+$/', $data['Currency']) > 0)
				$it->obtainPrice()->setCurrencyCode($data['Currency']);
			else
				$it->obtainPrice()->setCurrencySign($data['Currency']);
	}

    /**
     * @param $data
     * @param AwardRedemption $ar
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    protected static function convertAwardRedemption($data, AwardRedemption $ar) {
        if (!empty($data['dateIssued']))
            $ar->setDateIssued($data['dateIssued']);
        if (!empty($data['milesRedeemed']))
            $ar->setMilesRedeemed($data['milesRedeemed']);
        if (!empty($data['recipient']))
            $ar->setRecipient($data['recipient']);
        if (!empty($data['description']))
            $ar->setDescription($data['description']);
        if (!empty($data['accountNumber']))
            $ar->setAccountNumber($data['accountNumber']);
    }

    protected static function explodeString($string, $symbol = ',') {
		if (is_array($string))
			return array_filter($string, 'is_scalar');
		return array_map('trim', explode($symbol, $string));
	}

	protected static function deformatNumber($number) {
		$comma = strpos($number, ',');
		$point = strpos($number, '.');
		if ($comma !== false && $point !== false)
			$number = str_replace($comma < $point ? ',' : '.', '', $number);
		elseif ($comma !== false && preg_match('/^\d{1,3}(,\d{3})+$/', $number) > 0)
			$number = str_replace(',', '', $number);
		elseif ($comma !== false && preg_match('/^\d+,\d{2}$/', $number) > 0)
			$number = str_replace(',', '.', $number);
		if (preg_match('/(?<r>^(\d+\.\d{2})\b|(\d+\.\d{2})\b$)/', $number, $m) > 0)
			$number = $m['r'];

		return $number;
	}

	protected static function solveKind(&$it) {
		if (!isset($it['Kind'])) {
		    $it['Kind'] = null;
            foreach (self::$kinds as $f => $k) {
                if (!empty($it[$f])) {
                    $it['Kind'] = $k;
                }
                break;
            }
        }
	}

}
