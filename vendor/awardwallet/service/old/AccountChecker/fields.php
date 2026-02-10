<?
//namespace EmailAdmin;

global $accountCheckerFields;
global $checkClean;

$checkClean = function($value, $it, $seg, $name){
	if (is_array($value)) {
		$values = $value;
	} else {
		$values = [$value];
	}

	foreach ($values as $v) {
		if (is_array($v)) {
			// TODO Implement array-in-array values check
			continue;
		}

		if ($v && preg_match("#([\r\f\v\n;\*\#@\{\}\[\]_~`\^])#", $v, $m)){
			$symbol = $m[1];
			$symbol = preg_replace("#\r#", '\r', $symbol);
			$symbol = preg_replace("#\f#", '\f', $symbol);
			$symbol = preg_replace("#\v#", '\v', $symbol);
			$symbol = preg_replace("#\n#", '\n', $symbol);
			return "$name is invalid: field contains invalid symbol \"{$symbol}\"#";
		}
	}
};

$checkRentalLocation = function($value, $it, $seg, $name){
	global $checkClean;
	$value = str_replace($value, "#", '');
	return $checkClean($value, $it, $seg, $name);
};

$checkConfNo = function($value, $it, $seg, $name){
	if ($value == CONFNO_UNKNOWN)
		return;

	if (!$value){
		return "$name is empty: required field";
	}

	if (!preg_match("#^[\dA-Za-z\-]+$#", $value)){
		return "$name is invalid: value should match #[\dA-Za-z\-]#";
	}
};

$checkNotEmpty = function($value, $it, $seg, $name){
	if (!$value){
		return "$name is empty: required field";
	}
};

$checkDepCode = function($value, $it, $seg){
	if ($value == TRIP_CODE_UNKNOWN) return;

	if (!$value && isset($it['DepName']) && !$it['DepName']){
		return "DepCode is empty: DepName or DepCode should be specified#";
	}
	if ($value && !preg_match("#^[A-Z]{3}$#", $value)){
		return "DepCode is invalid: should match #^[A-Z]{3}$#";
	}
};

$checkArrCode = function($value, $it, $seg){
	if ($value == TRIP_CODE_UNKNOWN) return;

	if (!$value && isset($it['ArrName']) && !$it['ArrName']){
		return "ArrCode is empty: ArrName or ArrCode should be specified#";
	}
	if ($value && !preg_match("#^[A-Z]{3}$#", $value)){
		return "ArrCode is invalid: should match #^[A-Z]{3}$#";
	}
};

$checkDepDate = function($value, $it, $seg){
};

$checkArrDate = function($value, $it, $seg){
};

$accountCheckerFields = array(
	'T' => array(

		// segments
		'FlightNumber' => [
			$checkClean,
			function($value, $it, $seg){
				if (!$value){
					return "FlightNumber is invalid: required field";
				}
			}
		],

		'DepCode' => [$checkClean, $checkDepCode],

		'DepName' => [
			function($value, $it, $seg){
				if (isset($seg['DepCode']) && !$value && !preg_match("#[A-Z]{3}#", $seg['DepCode'])){
					return "DepDate/ArrDate: DepCode or DepName should be specified";
				}
			}
		],

		'DepDate' => [$checkClean, $checkDepDate],

		'ArrCode' => [$checkClean, $checkDepCode],

		'ArrName' => [
			function($value, $it, $seg){
				if (isset($seg['ArrCode']) && !$value && !preg_match("#^[A-Z]{3}$#", $seg['ArrCode'])){
					return "ArrName is empty: ArrName or ArrCode should be specified#";
				}
			}
		],

		'ArrDate' => [$checkClean, $checkArrDate],

		'AirlineName' => [$checkClean],
		'Aircraft' => [$checkClean],
		'TraveledMiles' => [$checkClean],
		'Class' => [$checkClean],
		'Cabin' => [$checkClean],
		'BookingClass' => [$checkClean],
		'Seats' => [],
		'Duration' => [],
		'Meal' => [$checkClean],
		'Smoking' => [$checkClean],
		'Stops' => [$checkClean],
    'Status' => [$checkClean],
    'Passengers' => [],
    'AccountNumbers' => [$checkClean],

		// basic
		'Kind' => [$checkClean],
		'RecordLocator' => [$checkConfNo, $checkClean],
		'Passengers' => [],
		'AccountNumbers' => [$checkClean],
		'Cancelled' => [$checkClean],
		'TotalCharge' => [$checkClean],
		'BaseFare' => [$checkClean],
		'Currency' => [$checkClean],
		'Tax' => [$checkClean],
		'Status' => [$checkClean],
		'ReservationDate' => [$checkClean],
		'NoItineraries' => [$checkClean],
		'TripCategory' => [$checkClean],
		'KioskCheckinCode' => [$checkClean],
		'KioskCheckinCodeFormat' => [$checkClean],
    'ConfirmationNumbers' => [$checkClean],
	),
	'B' => array(

		// segments
		'DepCode' => [$checkClean],
		'DepName' => [$checkClean],
		'DepAddress' => [$checkClean],
		'DepDate' => [$checkDepDate],
		'ArrCode' => [$checkClean],
		'ArrName' => [$checkClean],
		'ArrDate' => [$checkArrDate],
		'ArrAddress' => [$checkClean],
		'Type' => [$checkClean],
		'TraveledMiles' => [$checkClean],
		'Class' => [$checkClean],
		'Cabin' => [$checkClean],
		'BookingClass' => [$checkClean],
		'Seats' => [],
		'Duration' => [],
		'Meal' => [$checkClean],
		'Smoking' => [$checkClean],
		'Stops' => [$checkClean],
    'Status' => [$checkClean],
    'Passengers' => [$checkClean],
    'AccountNumbers' => [$checkClean],

		// basic
		'Kind' => [$checkClean],
		'RecordLocator' => [$checkConfNo, $checkClean],
		'Passengers' => [$checkClean],
		'AccountNumbers' => [$checkClean],
		'Cancelled' => [$checkClean],
		'TotalCharge' => [$checkClean],
		'BaseFare' => [$checkClean],
		'Currency' => [$checkClean],
		'Tax' => [$checkClean],
		'Status' => [$checkClean],
		'ReservationDate' => [$checkClean],
		'NoItineraries' => [$checkClean],
		'TripCategory' => [$checkClean],
		'KioskCheckinCode' => [$checkClean],
		'KioskCheckinCodeFormat' => [$checkClean],
	),
	'C' => array(
		// segments
		'DepName' => [$checkClean],
		'DepDate' => [$checkDepDate],
		'ArrName' => [$checkClean],
		'ArrDate' => [$checkArrDate],

		// basic
		'Kind' => [$checkClean],
		'RecordLocator' => [$checkConfNo, $checkClean],
		'Passengers' => [$checkClean],
		'AccountNumbers' => [$checkClean],
		'Cancelled' => [$checkClean],
		'ShipName' => [$checkClean],
		'ShipCode' => [$checkClean],
		'CruiseName' => [$checkClean],
		'Deck' => [$checkClean],
		'RoomNumber' => [$checkClean],
		'RoomClass' => [$checkClean],
		'Status' => [$checkClean],
		'TotalCharge' => [$checkClean],
		'BaseFare' => [$checkClean],
		'Currency' => [$checkClean],
		'Tax' => [$checkClean],
		'ReservationDate' => [$checkClean],
		'NoItineraries' => [$checkClean],
		'TripCategory' => [$checkClean],
	),
	'R' => array(
		'Kind' => [$checkClean],
		'ConfirmationNumber' => [$checkConfNo, $checkClean],
		'ConfirmationNumbers' => [$checkClean],
		'HotelName' => [$checkNotEmpty],
		'2ChainName' => [$checkClean],
		'CheckInDate' => [$checkNotEmpty,$checkClean],
		'CheckOutDate' => [$checkNotEmpty,$checkClean],
		'Address' => [$checkNotEmpty],
		'DetailedAddress' => [],
		'Phone' => [$checkClean],
		'Fax' => [$checkClean],
		'GuestNames' => [$checkClean],
		'Guests' => [$checkClean],
		'Kids' => [$checkClean],
		'Rooms' => [$checkClean],
		'Rate' => [$checkClean],
		'RateType' => [$checkClean],
		'CancellationPolicy' => [],
		'RoomType' => [],
		'RoomTypeDescription' => [],
		'Cost' => [$checkClean],
		'Taxes' => [$checkClean],
		'Total' => [$checkClean],
		'Currency' => [$checkClean],
		'AccountNumbers' => [$checkClean],
		'HotelCategory' => [$checkClean],
		'Status' => [$checkClean],
		'Cancelled' => [$checkClean],
		'ReservationDate' => [$checkClean],
		'NoItineraries' => [$checkClean],
	),
	'L' => array(
		'Kind' => [$checkClean],
		'Number' => [$checkConfNo, $checkClean],
		'PickupLocation' => [$checkRentalLocation],
		'PickupDatetime' => [$checkNotEmpty,$checkClean],
		'DropoffLocation' => [$checkRentalLocation],
		'DropoffDatetime' => [$checkNotEmpty,$checkClean],
		'PickupPhone' => [],
		'PickupFax' => [],
		'PickupHours' => [],
		'DropoffPhone' => [],
		'DropoffHours' => [],
		'DropoffFax' => [],
		'RentalCompany' => [],
		'CarType' => [$checkClean],
		'CarModel' => [],
		'CarImageUrl' => [],
		'RenterName' => [],
		'PromoCode' => [$checkClean],
		'TotalCharge' => [$checkClean],
		'Currency' => [$checkClean],
		'TotalTaxAmount' => [$checkClean],
		'AccountNumbers' => [$checkClean],
		'Status' => [$checkClean],
		'ServiceLevel' => [$checkClean],
		'Cancelled' => [$checkClean],
		'PricedEquips' => [$checkClean],
		'Discount' => [$checkClean],
		'Discounts' => [$checkClean],
		'Fees' => [$checkClean],
		'ReservationDate' => [$checkClean],
		'NoItineraries' => [$checkClean],
	),
	'E' => array(
		'Kind' => [$checkClean],
		'ConfNo' => [$checkConfNo, $checkClean],
		'Name' => [],
		'StartDate' => [$checkClean],
		'EndDate' => [$checkClean],
		'Address' => [],
		'Phone' => [$checkClean],
		'DinerName' => [$checkClean],
		'Guests' => [$checkClean],
		'TotalCharge' => [$checkClean],
		'Currency' => [$checkClean],
		'Tax' => [$checkClean],
		'AccountNumbers' => [$checkClean],
		'Status' => [$checkClean],
		'Cancelled' => [$checkClean],
		'ReservationDate' => [$checkClean],
		'NoItineraries' => [$checkClean],
	),
);
