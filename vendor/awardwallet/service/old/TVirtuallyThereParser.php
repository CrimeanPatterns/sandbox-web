<?

use AwardWallet\Common\Parsing\Html;

class TVirtuallyThereParser {

    /** @var DatabaseHelper */
    private $db;
    
    public $sPassword = null;

    public function __construct(DatabaseHelper $db)
    {
        $this->db = $db;
    }

	function SetPassword($s) {
		$this->sPassword = $s;
	}

    // return array of reservations, or string error message
    function ParseVirtuallyIt($url){
        $http = new HttpBrowser("none", new CurlDriver());

        /*
		if ($_SERVER['HTTP_HOST'] == 'awardwallet.docker') {
			$http->LogMode = 'html';
		}
		*/

    //	$http->debug = true;
        $http->GetURL($url);
        //$http->body = file_get_contents("/mnt/projects/wget1.html");
        //$doc->saveHTMLFile("/mnt/projects/wget-xml.html");
        if($http->ParseForm("travellerVerificationForm")){
            if (!isset($this->sPassword))
                return "Please enter your e-mail address or the password provided by your travel arranger";
            $http->Form["travelerEmailAddress"] = $this->sPassword;
            $http->Form["action"] = "travellerVerification";
            unset($http->Form["travelerEmailAddressRememberMe"]);
            $http->PostForm();          
        }
        
        $error = $http->FindSingleNode("//div[@id='loginError']");
            if(isset($error))
                return $error;
        
        if(!preg_match("/ \- Your Itinerary/ims", $http->Response['body'])){
            return "This page does not appear to be an itinerary"; /*checked*/
        }
        $arHotels = $this->ParseHotels($http->XPath, $http);
        $arCars = $this->ParseCars($http->XPath, $http);
        $arTrips = $this->ParseTrips($http->XPath, $http);

		if (empty($arHotels) && empty($arCars) && empty($arTrips)) {
			$this->ParseNew($http, $arHotels, $arCars, $arTrips);
		}

        return array(
            "Trips" => $arTrips,
            "Cars" => $arCars,
            "Hotels" => $arHotels,
        );
    }
    
    function CheckConfirmationNumber($url, &$it){        
        $allResult = $this->ParseVirtuallyIt($url);        
        $it=array();
        $isAlreadyIssetKind = false;
        if (is_array($allResult)){
            foreach($allResult as $arr){
                if (count($arr) > 0) {
                    if ($isAlreadyIssetKind) DieTrace("2 or more kinds reservstions on Provider (VirtualThere)");					
                    $isAlreadyIssetKind = true;
                    if (count($arr) > 1) DieTrace("2 or more providers on Provider (VirtualThere)");
                    foreach($arr as $r){
                        if (isset($r['ProviderID'])) unset($r['ProviderID']);
                        $it = $r;
                        break;
                    }
                }
            }
        }
        else
            return $allResult;
        return null;
    }

	function ParseNew(HttpBrowser $http, &$arHotels, &$arCars, &$arTrips) {
		$arHotels = array();
		$arCars = array();
		$arTrips = array();

		$nodes = $http->XPath->query('//div[@class="segment" and contains(@id, "segment")]');
		$http->Log("Found {$nodes->length} segments");

		foreach ($nodes as $node) {
			if ($http->FindSingleNode('.//div[@class="hotelInfo"]', $node)) {
				$arHotels[] = $this->ParseNewHotel($http, $node);
			}
            elseif (count($http->FindNodes('.//th[@class="carVendorInfo"]', $node)) > 0) {
                $arCars[] = $this->ParseNewCar($http, $node);
            }
			elseif ($http->FindSingleNode('.//th[@class="air"]', $node)) {
				$arTrips[] = $this->ParseNewTrip($http, $node);
            }
		}
	}

    function ParseNewCar(HttpBrowser $http, $root){
        // Result
        $result = ["Kind" => "L"];
        // Pickup time
        $pickupDate = $http->FindSingleNode('.//th[@class="departingInfo"]/div[@class="date"]', $root);
        $pickupTime = $http->FindSingleNode('.//div[contains(@id, "PickUpDate")]', $root);
		$result['PickupDatetime'] = strtotime($pickupDate.' '.$pickupTime);
        // DropOff time
        $dropoffDate = $http->FindSingleNode('.//th[@class="arrivingInfo"]/div[@class="date"]', $root);
        $dropoffTime = $http->FindSingleNode('.//div[contains(@id, "DropOffDate")]', $root);
        $result['DropoffDatetime'] = strtotime($dropoffDate.' '.$dropoffTime);
        // ConfirmationNumber
        $result['Number'] = $http->FindSingleNode('.//td[contains(@id, "ConfirmationNumber")]', $root);
        // PickupLocation
        $address = $http->FindNodes('.//div[contains(@id, "PickUpLocation")]/p/text()', $root);
		$result['PickupLocation'] = implode(', ', array_filter($address));
        // PickupPhone
		$result['PickupPhone'] = $http->FindSingleNode('.//p[contains(@id, "PickUpPhoneNumber")]', $root, false, '/Phone ([\d ,\.\-+]+)/ims');
        // DropoffLocation
		$address = $http->FindNodes('.//div[contains(@id, "DropOffLocation")]/p/text()', $root);
		$result['DropoffLocation'] = implode(', ', array_filter($address));
        // DropoffPhone
		$result['DropoffPhone'] = $http->FindSingleNode('.//p[contains(@id, "DropOffPhoneNumber")]', $root, false, '/Phone ([\d ,\.\-+]+)/ims');
        // Status
        $result['Status'] = $http->FindSingleNode('.//td[contains(@id, "Status")]', $root);

		$result['CarType'] = $http->FindSingleNode('.//td[contains(@class, "vendorDetail")]//div[@class="info"]', $root);

		$result['TotalCharge'] = $http->FindSingleNode('.//td[contains(text(), "Approx Total Price")]/following-sibling::td[1]', $root);

		// TVirtuallyThereParser extra
		$result['ProviderName'] = $http->FindSingleNode('.//th[@class="carVendorInfo"]/div[@class="centeredContent"]/h2[@class="carVendorName"]/img/@alt', $root);
		if (isset($result['ProviderName'])) {
            $arProviders = $this->db->getProvidersBy(["Kind" => PROVIDER_KIND_CAR_RENTAL]);
            foreach ($arProviders as $provider) {
                $name = $provider['Name'];
                if (stripos($result['ProviderName'], $name) !== false || stripos($name, $result['ProviderName']) !== false) {
                    $result['ProviderName'] = $name;
                    $result['ProviderID'] = $provider['ProviderID'];
                }
            }
		}

        // Return
        return $result;
    }

    function ParseNewHotel(HttpBrowser $http, $root){
        // Result
        $result = ["Kind" => "R"];
        // HotelName
        $result['HotelName'] = $http->FindSingleNode('.//h3[contains(@id, "HotelNameHOTEL")]', $root);

        // Address
        $result['Address'] = $http->FindSingleNode('.//input[contains(@id, "hotelAddress")]/@value', $root);
        // Phone
        $result['Phone'] = $http->FindSingleNode('.//address[contains(@id, "HotelAddress")]/text()[3]', $root);
        // Fax
        $result['Fax'] = $http->FindSingleNode('.//address[contains(@id, "HotelAddress")]/text()[4]', $root);

        // CheckInDate/CheckOutDate
        $checkInDate_day = $http->FindSingleNode('.//div[contains(@id, "CheckInDate")]', $root);
		$result['CheckInDate'] = strtotime($checkInDate_day);
        $checkOutDate_day = $http->FindSingleNode('.//div[contains(@id, "CheckOutDate")]', $root);
        $result['CheckOutDate'] = strtotime($checkOutDate_day);
        // ConfirmationNumber
        $result['ConfirmationNumber'] = $http->FindSingleNode('.//td[contains(@id, "ConfirmationNumber")]', $root);
        // Rooms
        $result['Rooms'] = $http->FindSingleNode('.//td[contains(@id, "NoOfRooms")]', $root);
        // Rate
        $result['Rate'] = $http->FindSingleNode('.//td[contains(@id, "BasicRate")]', $root);
        // Status
        $result['Status'] = $http->FindSingleNode('.//td[contains(@id, "StatusCode")]', $root);
        // Guests
        $result['Guests'] = $http->FindSingleNode('.//td[contains(@id, "NoOfGuests")]', $root);
        // RoomType
        $result['RoomType'] = $http->FindSingleNode('.//td[contains(@id, "RoomType")]/text()[1]', $root);
        // RoomTypeDescription
        $result['RoomTypeDescription'] = $http->FindSingleNode('.//td[contains(@id, "RoomType")]/text()[2]', $root);

		$result['CancellationPolicy'] = $http->FindSingleNode('.//td[contains(@id, "CancellationInformation")]', $root);

		// TVirtuallyThereParser extra
		if (isset($result['HotelName'])) {
            $arProviders = $this->db->getProvidersBy(["Kind" => PROVIDER_KIND_HOTEL]);
            foreach ($arProviders as $provider) {
                $name = $provider['Name'];
                if (stripos($result['HotelName'], $name) !== false || stripos($name, $result['HotelName']) !== false) {
                    $result['ProviderName'] = $name;
                    $result['ProviderID'] = $provider['ProviderID'];
                }
            }
		}

        // Return
        return $result;
    }

    function ParseNewTrip(HttpBrowser $http, $root) {
        // Result
        $result = array();

		// todo: alert

        // Return
        return $result;
    }

    function ParseHotels($xpath, $http){
        $hotels = $xpath->query("//div[@id='HotelInfo']");
        $http->log("hotels found: ".$hotels->length);
        $hotelDetails = $xpath->query("//div[@id='CarSeg']");
        $http->log("details found: ".$hotelDetails->length);
        $extras = $xpath->query("//table[@class='flightDetails'][tr/th[contains(text(), 'Room(s)')]]");
        $http->log("extras found: ".$extras->length);
        $arHotels = array();
        $arProviders = $this->db->getProvidersBy(["Kind" => PROVIDER_KIND_HOTEL]);
        for($n = 0; $n < $hotels->length; $n++){
            $http->log("hotel $n");
            $hotel = $hotels->item($n);
            $arInfo = array();
            // provider and hotelname
            $info = $xpath->query("dl/dd/h3", $hotel);
            if($info->length > 0){
                $s = Html::cleanXMLValue($info->item(0)->nodeValue);
                $arInfo['HotelName'] = ucwords(strtolower($s));
                foreach ($arProviders as $provider) {
                    if (stripos($s, $provider['Name']) !== false)
                        $arInfo['ProviderID'] = $provider['ProviderID'];
                }
                if(!isset($arInfo['ProviderID'])){
                    if(stripos($s, "hyatt") !== false)
                        $arInfo['ProviderID'] = 10;
                }
                if(!isset($arInfo['ProviderID'])){
                    if(stripos($s, "embassy suites") !== false)
                        $arInfo['ProviderID'] = 22;
                }
            }
            else
                $http->log("hotel name not found");
            if(!isset($arInfo['ProviderID'])){
                $arInfo['ProviderName'] = ucwords(strtolower($s));
                $http->log("provider not recognized: $s");
            }
            // address and phone/fax
            $info = $xpath->query("dl/dd/address", $hotel);
            if($info->length > 0){
                $s = Html::cleanXMLValue($info->item(0)->nodeValue);
                $http->log(StrToHex($s));
                if(preg_match('/Fax\s+(.+)$/ims', $s, $matches)){
                    $arInfo['Fax'] = trim($matches[1]);
                    $s = str_replace($matches[0], "", $s);
                }
                if(preg_match('/Phone\s+(.+)$/ims', $s, $matches)){
                    $arInfo['Phone'] = trim($matches[1]);
                    $s = str_replace($matches[0], "", $s);
                }
                $arInfo['Address'] = trim($s);
            }
            else
                $http->log("hotel address not found");
            if($hotelDetails->length == $hotels->length){
                $details = $hotelDetails->item($n);
                foreach(array("CheckIn" => "departingInfo1", "CheckOut" => "ChkOut") as $sPrefix => $sCode){
                    // dates
                    $info = $xpath->query("div[@class='{$sCode}']/dl/dd", $details);
                    $s = Html::cleanXMLValue($info->item(0)->nodeValue);
                    $s = trim(preg_replace("/[^\s\d\w\,'.\-]/ims", " ", $s));
                    $http->log("{$sCode} date: ".StrToHex($s));
                    $arInfo["{$sPrefix}Date"] = strtotime($s);
                    if($arInfo["{$sPrefix}Date"] === false)
                        unset($arInfo["{$sPrefix}Date"]);
                }
            }
            // extra
            if($extras->length == $hotels->length){
                $extra = $extras->item($n);
                $info = $xpath->query("tr/th[contains(text(), 'Confirmation')]/following::td[1]", $extra);
                $arInfo['ConfirmationNumber'] = Html::cleanXMLValue($info->item(0)->nodeValue);
                $arInfo['ConfirmationNumber'] = preg_replace("/^HY00/ims", "", $arInfo['ConfirmationNumber']);
                $info = $xpath->query("tr/th[contains(text(), 'Room(s)')]/following::td[1]", $extra);
                $arInfo['Rooms'] = Html::cleanXMLValue($info->item(0)->nodeValue);
                $info = $xpath->query("tr/th[contains(text(), 'Basic Rate')]/following::td[1]", $extra);
                $arInfo['BasicRate'] = Html::cleanXMLValue($info->item(0)->nodeValue);
                $info = $xpath->query("tr/th[contains(text(), 'Guarantee')]/following::td[1]", $extra);
                $arInfo['Guarantee'] = Html::cleanXMLValue($info->item(0)->nodeValue);
                $info = $xpath->query("tr/th[contains(text(), 'Status')]/following::td[1]", $extra);
                $arInfo['Status'] = Html::cleanXMLValue($info->item(0)->nodeValue);
                $info = $xpath->query("tr/th[contains(text(), 'Room Type')]/following::td[1]", $extra);
                $arInfo['RoomType'] = Html::cleanXMLValue($info->item(0)->nodeValue);
                $info = $xpath->query("tr/th[contains(text(), 'Number of Guests')]/following::td[1]", $extra);
                $arInfo['NumberOfGuests'] = Html::cleanXMLValue($info->item(0)->nodeValue);
                $info = $xpath->query("tr/th[contains(text(), 'Cancellation')]/following::td[1]", $extra);
                $arInfo['CancellationInformation'] = Html::cleanXMLValue($info->item(0)->nodeValue);
            }
            // store
            $arHotels[] = $arInfo;
        }
        return $arHotels;
    }
    
    function ParseCars($xpath, $http){
        $cars = $xpath->query("//table[@class='flight'][thead/tr/th[@class='carInfo2']]");
        $http->log("cars found: ".$cars->length);
        $carDetails = $xpath->query("//table[@class='flightDetails'][tr/th[contains(text(), 'Car(s)')]]");
        $http->log("details found: ".$carDetails->length);
        $loyaltyDetails = $xpath->query("//table[thead/tr/th[contains(text(), 'Rate Plan')]]");
        $http->log("lyalties found: ".$loyaltyDetails->length);
        $arCars = array();
        $arProviders = $this->db->getProvidersBy(["Kind" => PROVIDER_KIND_CAR_RENTAL]);
        for($n = 0; $n < $cars->length; $n++){
            $http->log("car $n");
            $car = $cars->item($n);
            $arInfo = array();
            // provider
            $info = $xpath->query("thead/tr/th[@class='carInfo2']//img/@title", $car);
            if ($info->length == 1) {
                $s = Html::cleanXMLValue($info->item(0)->nodeValue);
                foreach ($arProviders as $provider) {
                    if (stripos($s, $provider['Name']) !== false)
                        $arInfo['ProviderID'] = $provider['ProviderID'];
                }

                $arInfo['ProviderName'] = ucwords(strtolower($s));
            }
            if(!isset($arInfo['ProviderID'])){
                $http->log("can't recognize provider");
            }
            foreach(array("Pickup" => "departing", "Dropoff" => "arriving") as $sPrefix => $sCode){
                // dates
                $info = $xpath->query("thead/tr/th[@class='{$sCode}Info']/dl/dt", $car);
                $s = Html::cleanXMLValue($info->item(0)->nodeValue);
                $s = trim(preg_replace("/[^\s\d\w\,'.\-]/ims", " ", $s));
                $http->log("{$sCode} date: ".StrToHex($s));
                $arInfo["{$sPrefix}Datetime"] = strtotime($s);
                if($arInfo["{$sPrefix}Datetime"] === false)
                    unset($arInfo["{$sPrefix}Datetime"]);
                $info = $xpath->query("tbody/tr/td[@class='{$sCode}Detail']/dl/dd[2]", $car);
                if($info->length > 0)
                    $arInfo["{$sPrefix}Hours"] = Html::cleanXMLValue($info->item(0)->nodeValue);
                else{
                    $http->log("{$sPrefix}Hours not found");
                    $arInfo["{$sPrefix}Hours"] = "Unknown";
                }
                // locations
                $info = $xpath->query("tbody/tr/td[@class='{$sCode}Detail']/dl/dd[1]", $car);
                if($info->length > 0)
                    $arInfo["{$sPrefix}Location"] = Html::cleanXMLValue($info->item(0)->nodeValue);
                else
                    $http->log("failed to find $sPrefix location");
            }
            // extra
            $info = $xpath->query("tbody/tr/td[@class='flightInfoDetails']/dl/dd[1]", $car);
            if($info->length > 0)
                $arInfo['Vehicle'] = Html::cleanXMLValue($info->item(0)->nodeValue);
            if($cars->length == $carDetails->length){
                $details = $carDetails->item($n);
                $info = $xpath->query("tr/th[contains(text(), 'No.of Days')]/following::td[1]", $details);
                if($info->length > 0)
                    $arInfo['Days'] = Html::cleanXMLValue($info->item(0)->nodeValue);
                $info = $xpath->query("tr/th[contains(text(), 'Status')]/following::td[1]", $details);
                if($info->length > 0)
                    $arInfo['Status'] = Html::cleanXMLValue($info->item(0)->nodeValue);
                $info = $xpath->query("tr/th[contains(text(), 'Phone Number')]/following::td[1]", $details);
                if($info->length > 0)
                    $arInfo['Phone'] = Html::cleanXMLValue($info->item(0)->nodeValue);
                $info = $xpath->query("tr/th[contains(text(), 'Confirmation')]/following::td[1]", $details);
                if($info->length > 0)
                    $arInfo['Number'] = Html::cleanXMLValue($info->item(0)->nodeValue);
                $info = $xpath->query("tr/th[contains(text(), 'Confirmation')]/following::td[1]", $details);
                if($info->length > 0)
                    $arInfo['Number'] = Html::cleanXMLValue($info->item(0)->nodeValue);
            }
            if($cars->length == $loyaltyDetails->length){
                $loyalty = $loyaltyDetails->item($n);
                $info = $xpath->query("tbody/tr/td[em[contains(text(), 'Approx Total Price')]]/following::td[1]/em", $loyalty);
                if($info->length > 0)
                    $arInfo['EstimatedTotal'] = Html::cleanXMLValue($info->item(0)->nodeValue);
                else
                    $http->log("total not found");
            }
            // store
            $arCars[] = $arInfo;
        }
        return $arCars;
    }

	function ParseTrips(DOMXPath $xpath, HttpBrowser $http) {
        // parse flights
        $flights = $xpath->query("//div[contains(@id,'segmentAIR')]/table[@class='segmentInfo']");
        if ($flights->length === 0) {
            // check new variant
            $hrefs = array_unique($http->FindNodes("//div[@id='tripInfo']/following::div[normalize-space()!=''][1][count(.//text()[normalize-space()!=''])=1]//a/@href"));
            if (empty($hrefs)) {
                $hrefs = array_unique($http->FindNodes("//div[@id='tripInfo']/following::div[normalize-space()!=''][1]//a[contains(.,'View eTicket Receipt')]/@href"));
            }
            if (count($hrefs) > 0) {
                return $this->ParseTrips_2($xpath, $http, $hrefs);
            }
        }
        $flightDetails = $xpath->query("//table[@class='segmentDetails']");
        $loyaltyDetails = $xpath->query("//table[contains(@class, 'passengerDetails')]");
        $http->log("flights found: ".$flights->length);
        $http->log("flights details found: ".$flightDetails->length);
        $http->log("loyalties found: ".$loyaltyDetails->length);
        $arTrips = array();
        for($n = 0; $n < $flights->length; $n++){
            $http->log("flight $n");
            $arSegment = array();
            $flight = $flights->item($n);
            // base date
            $info = $xpath->query("thead/tr/th[@class='dateInfo']/h2/em", $flight);
            if($info->length > 0){
                $http->log("base date string: ".$info->item(0)->nodeValue);
                $baseDate = strtotime(Html::cleanXMLValue($info->item(0)->nodeValue));
                $http->log("base date: ".date(DATE_FORMAT, $baseDate));
            }
            else{
                $http->log("base date not found");
                $baseDate = null;
            }
            // departure and destination
            $info = $xpath->query("thead/tr/th[@class='departingInfo']/div[@class='cityCode']", $flight);
            $arSegment['DepCode'] = Html::cleanXMLValue($info->item(0)->nodeValue);
            $info = $xpath->query("thead/tr/th[@class='departingInfo']/div[@class='airportName']", $flight);
            $arSegment['DepName'] = Html::cleanXMLValue($info->item(0)->nodeValue);
            $info = $xpath->query("thead/tr/th[@class='arrivingInfo']/div[@class='cityCode']", $flight);
            $arSegment['ArrCode'] = Html::cleanXMLValue($info->item(0)->nodeValue);
            $info = $xpath->query("thead/tr/th[@class='arrivingInfo']/div[@class='airportName']", $flight);
            $arSegment['ArrName'] = Html::cleanXMLValue($info->item(0)->nodeValue);
            $info = $xpath->query("tbody/tr/td[@class='departingDetail']/div[contains(@id,'DepartingTerminalValueAIR')]/text()[1]", $flight);
            if ($info->length > 0) {
                $departureTerminal = Html::cleanXMLValue($info->item(0)->nodeValue);
                $arSegment['DepartureTerminal'] = $departureTerminal;
                if ($depTerm = $http->FindPreg("/^TERMINAL\s*(\d+)$/", false, $departureTerminal)) {
                    $arSegment['DepartureTerminal'] = $depTerm;
                }
            }
            $info = $xpath->query("tbody/tr/td[@class='arrivingDetail']/div[contains(@id,'ArrivingTerminalValueAIR')]/text()[1]", $flight);
            if ($info->length > 0) {
                $arrivalTerminal = Html::cleanXMLValue($info->item(0)->nodeValue);
                $arSegment['ArrivalTerminal'] = $arrivalTerminal;
                if ($arrTerm = $http->FindPreg("/^TERMINAL\s*(\d+)$/", false, $arrivalTerminal)) {
                    $arSegment['ArrivalTerminal'] = $arrTerm;
                }
            }
            // times
            if(isset($baseDate)){
                // depart
                $info = $xpath->query("tbody/tr/td[@class='departingDetail']/div[contains(@id,'DepartingAtTimeValueAIR')]/text()[1]", $flight);
                if($info->length > 0){
                    $d = strtotime(Html::cleanXMLValue($info->item(0)->nodeValue), $baseDate);
                    if($d === false){
                        $http->log("failed to parse time: ".Html::cleanXMLValue($info->item(0)->nodeValue));
                    }
                    else{
                        $http->log("depart date: ".date(DATE_TIME_FORMAT, $d));
                        $arSegment['DepDate'] = $d;
                    }
                }
                else
                    $http->log("dep date not found");
                // arrive
                $info = $xpath->query("tbody/tr/td[@class='arrivingDetail']/div[contains(@id,'ArrivingAtTimeValueAIR')]/text()[1]", $flight);
                if($info->length > 0){
                    $d = strtotime(Html::cleanXMLValue($info->item(0)->nodeValue), $baseDate);
                    if($d === false){
                        $http->log("failed to parse time: ".Html::cleanXMLValue($info->item(0)->nodeValue));
                    }
                    else{
                        $http->log("arrive date: ".date(DATE_TIME_FORMAT, $d));
						if (isset($arSegment['DepDate']) && $arSegment['DepDate'] > $d)
							$d = strtotime('+1 day', $d);
                        $arSegment['ArrDate'] = $d;
                    }
                }
                else
                    $http->log("arr date not found");
            }
            // provider
            $info = $xpath->query("tbody/tr/td[@class='flightDetail']/div[@class='airlineName']", $flight);
            if($info->length == 1){
                $sProvider = Html::cleanXMLValue($info->item(0)->nodeValue);
                $http->log("provider: $sProvider");
                $sProvider = str_ireplace('continental airlines', 'continental', $sProvider);
                $sProvider = str_ireplace('delta air lines inc', 'delta air lines personal', $sProvider);
				$sProvider = str_ireplace('utair aviation', 'utair', $sProvider);
                $providerInfo = $this->db->getProvidersBy(["Name" => $sProvider], true);
                if (empty($providerInfo)) {
                    $http->log("can't recognize provider");
                    $arSegment['ProviderName'] = ucwords(strtolower($sProvider));
                }
                else{
                    $arSegment['ProviderID'] = $providerInfo['ProviderID'];
                    $http->log('provider recognized as: '.$providerInfo['Code']);
                }
            }
            else
                $http->log("provider not found");
            // race
            $info = $xpath->query("tbody/tr/td[@class='flightDetail']/div[@class='flightNumber']", $flight);
            if ($info->length == 1) {
                $flightNumber = Html::cleanXMLValue($info->item(0)->nodeValue);
                $arSegment['AirlineName'] = $http->FindPreg('/^\s*(\w+)/', false, $flightNumber);
                $arSegment['FlightNumber'] = $http->FindPreg('/(\d+)\s*$/', false, $flightNumber);
            }
            // flight details
            if($flights->length <= $flightDetails->length){
                $details = $flightDetails->item($n);
                // reservation code
                $info = $xpath->query("//div[@id='tripInfo_reservationCode']/div[@class='descr']");
                $sCode = Html::cleanXMLValue($info->item(0)->nodeValue);
                $http->log("res code: $sCode");
                // ext props
                $info = $xpath->query("tr/th[contains(text(), 'Status')]/following::td[1]", $details);
                if($info->length > 0)
                    $arSegment['Status'] = Html::cleanXMLValue($info->item(0)->nodeValue);
                $info = $xpath->query("tr/th[contains(text(), 'Aircraft')]/following::td[1]", $details);
                if($info->length > 0)
                    $arSegment['Aircraft'] = Html::cleanXMLValue($info->item(0)->nodeValue);
                $info = $xpath->query("tr/th[contains(text(), 'Meals')]/following::td[1]", $details);
                if($info->length > 0)
                    $arSegment['Meal'] = Html::cleanXMLValue($info->item(0)->nodeValue);
                $info = $xpath->query("tr/th[contains(text(), 'Smoking')]/following::td[1]", $details);
                if($info->length > 0)
                    $arSegment['Smoking'] = (Html::cleanXMLValue($info->item(0)->nodeValue) == 'Yes') ? true : false;
                $info = $xpath->query("tr/th[contains(text(), 'Duration')]/following::td[1]", $details);
                if($info->length > 0)
                    $arSegment['Duration'] = Html::cleanXMLValue($info->item(0)->nodeValue);
                $info = $xpath->query("tr/th[contains(text(), 'Class')]/following::td[1]", $details);
                if($info->length > 0)
                    $arSegment['Cabin'] = Html::cleanXMLValue($info->item(0)->nodeValue);
                $info = $xpath->query("tr/th[contains(text(), 'Stops')]/following::td[1]", $details);
                if($info->length > 0)
                    $arSegment['Stops'] = Html::cleanXMLValue($info->item(0)->nodeValue);
                $info = $xpath->query("tr/th[contains(text(), 'Distance')]/following::td[1]", $details);
                if($info->length > 0)
                    $arSegment['TraveledMiles'] = Html::cleanXMLValue($info->item(0)->nodeValue);
            }
            else{
                $sCode = null;
                $http->log("res code not found");
            }
            // loyalty
            if($flights->length <= $loyaltyDetails->length){
                $loyalty = $loyaltyDetails->item($n);

                $info = $xpath->query("tbody/tr/td[@class='passengerName']", $loyalty);
                $temp = array();
				for ($i = 0; $i < $info->length; $i++)
					$temp[] = Html::cleanXMLValue($info->item($i)->nodeValue);
				if(count($temp) > 0)
                    $arSegment['Passengers'] = implode(', ', $temp);

                $info = $xpath->query("tbody/tr/td[position() = 2]", $loyalty);
				$temp = array();
				for ($i = 0; $i < $info->length; $i++) {
					$seat = Html::cleanXMLValue($info->item($i)->nodeValue);
					if ($seat != 'Check-In Required')
						$temp[] = Html::cleanXMLValue($info->item($i)->nodeValue);
				}
				if(count($temp) > 0)
					$arSegment['Seats'] = implode(', ', $temp);

                $info = $xpath->query("tbody/tr/td[position() = 3]", $loyalty);
				$temp = array();
				for ($i = 0; $i < $info->length; $i++)
					$temp[] = Html::cleanXMLValue($info->item($i)->nodeValue);
				if(count($temp) > 0)
					$arSegment['AccountNumbers'] = implode(', ', $temp);
            }
            // log
            $http->log("parsed flight: ".nl2br(print_r($arSegment, true)));
            // combine into trips
            if(isset($arSegment['ProviderID']) || isset($arSegment['ProviderName'])){
                if(isset($arSegment['ProviderID']))
                    $sKey = $arSegment['ProviderID']."_".$sCode;
                else
                    $sKey = $arSegment['ProviderName']."_".$sCode;
				$sKey = $sCode;
                if(!isset($arTrips[$sKey])){
                    $arTrip = [
                        "Kind"               => "T",
                        "RecordLocator"      => $sCode,
                        "TripSegments"       => [],
                    ];
                    if(isset($arSegment['ProviderID']))
                        $arTrip['ProviderID'] = $arSegment['ProviderID'];
                    $arTrips[$sKey] = $arTrip;
                }
                unset($arSegment['Code']);
                unset($arSegment['ProviderID']);
                unset($arSegment['ProviderName']);
                if (!empty($arSegment['Passengers'])) {
                    $arTrips[$sKey]['Passengers'][] = $arSegment['Passengers'];
                    $arTrips[$sKey]['Passengers'] = array_unique($arTrips[$sKey]['Passengers']);
                }
                unset($arSegment['Passengers']);
                $arTrips[$sKey]['TripSegments'][] = $arSegment;
            }
        }
        return $arTrips;
    }

    private function ParseTrips_2(DOMXPath $xpath, HttpBrowser $http, array $hrefs): array
    {
        $arTrips = array();
        foreach ($hrefs as $href) {
            $http->NormalizeURL($href);
            $http->GetURL($href);
            $tickets = array_values(array_unique($http->FindNodes("//td[starts-with(@id,'ticket-number-')]/a/@href")));
            foreach ($tickets as $num => $ticket) {
                $http->Log("ticket " . $num);
                $http->NormalizeURL($ticket);
                $http->GetURL($ticket);

                $recLoc = $http->FindSingleNode("//*[self::th or self::td][normalize-space()='Booking Reference']/following-sibling::*[self::th or self::td][1]");
                if (!empty($arTrips) && ($j = $this->findRL($recLoc, $arTrip)) !== -1) {
                    $i = $j;
                } else {
                    $arTrips[] = ["Kind" => "T"];
                    $i = count($arTrips) - 1;
                }
                $arTrips[$i]['RecordLocator'] = $recLoc;
                $arTrips[$i]['Passengers'][] = $http->FindSingleNode("//div[normalize-space()='Prepared For']/following-sibling::div[1]");
                $arTrips[$i]['ReservationDate'] = strtotime($http->FindSingleNode("//*[self::th or self::td][normalize-space()='Issue Date']/following-sibling::*[self::th or self::td][1]"));
                $arTrips[$i]['AccountNumbers'][] = $http->FindSingleNode("//*[self::th or self::td][normalize-space()='Frequent Flyer Number']/following-sibling::*[self::th or self::td][1]");
                $arTrips[$i]['TicketNumbers'][] = $http->FindSingleNode("//*[self::th or self::td][normalize-space()='Ticket Number']/following-sibling::*[self::th or self::td][1]");
                if (count($tickets) === 1) {
                    $total = $http->FindSingleNode("//*[self::th or self::td][normalize-space()='Total/Transaction Currency']/following-sibling::*[self::th or self::td][1]");
                    $cost = $http->FindSingleNode("//*[self::th or self::td][normalize-space()='Fare']/following-sibling::*[self::th or self::td][1]");
                    $arTrips[$i]['BaseFare'] = $http->FindPreg("/\s*(\d+(?:\.\d+)?)$/", false, $cost);
                    $arTrips[$i]['TotalCharge'] = $http->FindPreg("/\s*(\d+(?:\.\d+)?)$/", false, $total);
                    $arTrips[$i]['Currency'] = $http->FindPreg("/^[A-Z]{3}/", false, $total);
                    $fees = $http->FindNodes("//*[self::th or self::td][normalize-space()='Fare']/ancestor::tr[1]/following-sibling::tr[./*[self::th or self::td][1][not(contains(.,'Total/Transaction Currency'))]]/*[self::th or self::td][2]");
                    foreach ($fees as $fee) {
                        if (preg_match("/^[A-Z]{3}\s*(\d[\.\d]+)\s+(.+)/", $fee, $m)) {
                            $arTrips[$i]['Fees'][] = [
                                'Name' => $m[2],
                                'Charge' => $m[1]
                            ];
                        } else {
                            unset($arTrips[$i]['Fees']);
                            break;
                        }
                    }
                } else {
                    $http->Log('check collected total sums', LOG_LEVEL_ALERT);
                }

                $segments = $http->XPath->query("//h2[normalize-space()='Itinerary Details']/following-sibling::table[1]/descendant::tr[not(contains(.,'Travel Date'))]/../tr");
                $http->Log("Found " . $segments->length . " segments");
                foreach ($segments as $segment) {
                    $seg = [];
                    $dates = $http->FindNodes("./td[1]//text()[normalize-space()]", $segment);
                    $dateDep = $dateArr = false;
                    if (count($dates) === 2) {
                        $dateDep = strtotime($http->FindPreg("/(\d+\w+\d+)/", false, $dates[0]));
                        $dateArr = strtotime($http->FindPreg("/(\d+\w+\d+)/", false, $dates[1]));
                    } elseif (count($dates) === 1) {
                        $dateDep = $dateArr = strtotime($http->FindPreg("/(\d+\w+\d+)/", false, $dates[0]));
                    }
                    $flight = $http->FindSingleNode("./td[2]//text()[normalize-space()][2]", $segment);
                    $seg['AirlineName'] = $http->FindPreg("/^([A-Z\d][A-Z]|[A-Z][A-Z\d])\s*\d+$/", false, $flight);
                    $seg['FlightNumber'] = $http->FindPreg("/^(?:[A-Z\d][A-Z]|[A-Z][A-Z\d])\s*(\d+)$/", false, $flight);
                    $dep = $http->FindNodes("./td[3]//text()[normalize-space()]", $segment);
                    if (count($dep) === 3 && $dep[1] === 'Time') {
                        $seg['DepCode'] = TRIP_CODE_UNKNOWN;
                        $seg['DepName'] = $dep[0];
                        $seg['DepDate'] = strtotime($dep[2], $dateDep);
                    }
                    $arr = $http->FindNodes("./td[4]//text()[normalize-space()]", $segment);
                    if (count($arr) === 3 && $arr[1] === 'Time') {
                        $seg['ArrCode'] = TRIP_CODE_UNKNOWN;
                        $seg['ArrName'] = $arr[0];
                        $seg['ArrDate'] = strtotime($arr[2], $dateArr);
                    }
                    $seat = $http->FindSingleNode("./td[5]//div[contains(normalize-space(),'Seat Number')]/span[last()]",
                        $segment, false, "/^\d+[A-z]$/");
                    if (!empty($seat)) {
                        $seg['Seats'][] = $seat;
                    }

                    $arTrips[$i]['TripSegments'][] = $seg;
                }
            }
        }
        return $arTrips;
    }

    private function findRL($rl, $its)
    {
        foreach ($its as $i => $it) {
            if ($it['RecordLocator'] === $rl) {
                return $i;
            }
        }
        return -1;
    }

}

?>
